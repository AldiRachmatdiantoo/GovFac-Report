<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportSubmitted;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Services\CreateReportService;
use App\Modules\Report\Services\ReportPhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CreateReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    private function photo(string $name = 'original.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }

    private function payload(): array
    {
        return ['title' => 'Lampu jalan mati', 'description' => 'Lampu di depan taman mati.',
            'photos' => [$this->photo(), $this->photo('second.png')], 'tags' => ['#LampuJalan', 'lampujalan']];
    }

    public function test_user_can_submit_report_with_ordered_photos_and_normalized_tags(): void
    {
        Event::fake([ReportSubmitted::class]);
        $user = User::factory()->create();
        $this->mock(ReportStatusService::class)->shouldReceive('initialize')->once()
            ->withArgs(fn (Report $report, User $actor) => $report->reporter_id === $actor->id && $actor->is($user));

        $response = $this->actingAs($user, 'api')->post('/api/v1/reports', $this->payload(), ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.status', 'ai_validation')
            ->assertJsonPath('data.revision', 1)->assertJsonPath('data.tags', ['lampujalan'])
            ->assertJsonPath('data.reporter', ['id' => $user->id, 'name' => $user->name])
            ->assertJsonCount(2, 'data.photos')->assertJsonMissingPath('data.reporter.email');

        $report = Report::query()->sole();
        $this->assertSame(sprintf('GFR-%s-%06d', now()->year, $report->id), $report->code);
        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseCount('report_tag', 1);
        $this->assertDatabaseCount('report_status_histories', 0);
        foreach ($report->photos as $order => $photo) {
            Storage::disk('public')->assertExists($photo->path);
            $this->assertSame($order, $photo->sort_order);
            $this->assertSame('image/png', $photo->mime);
            $this->assertGreaterThan(0, $photo->size);
            $this->assertMatchesRegularExpression('~^reports/'.$report->id.'/[0-9a-f-]{36}\\.png$~', $photo->path);
            $this->assertStringContainsString($photo->path, $response->json('data.photos.'.$order.'.url'));
        }
        Event::assertDispatched(ReportSubmitted::class, fn ($event) => $event->report->is($report));
    }

    public static function invalidInputs(): array
    {
        return [
            'no photos' => ['photos', []], 'six photos' => ['photos', 'six'],
            'not an image' => ['photos', 'text'], 'gif' => ['photos', 'gif'],
            'over five MB' => ['photos', 'large'], 'long title' => ['title', str_repeat('a', 101)],
            'missing title' => ['title', ''], 'long description' => ['description', str_repeat('a', 2001)],
            'no tags' => ['tags', []], 'six tags' => ['tags', array_fill(0, 6, 'jalan')],
            'invalid tag' => ['tags', ['jalan-rusak']], 'long tag' => ['tags', [str_repeat('a', 31)]],
            'non-string tag' => ['tags', [42]],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected(string $field, mixed $value): void
    {
        $payload = $this->payload();
        if ($field === 'photos') {
            $value = match ($value) {
                'six' => array_map(fn () => $this->photo(), range(1, 6)),
                'text' => [UploadedFile::fake()->create('file.txt', 10, 'text/plain')],
                'gif' => [UploadedFile::fake()->createWithContent('file.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'))],
                'large' => [$this->photo()->size(5121)],
                default => $value,
            };
        }
        $payload[$field] = $value;
        $errorKey = in_array($field, ['photos', 'tags'], true) && count($value) === 1 ? $field.'.0' : $field;
        $this->actingAs(User::factory()->create(), 'api')->postJson('/api/v1/reports', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($errorKey);
        $this->assertDatabaseCount('reports', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_user_roles_are_forbidden(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->government()->create()] as $user) {
            $this->actingAs($user, 'api')->postJson('/api/v1/reports', $this->payload())->assertForbidden();
        }
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->postJson('/api/v1/reports', $this->payload())->assertUnauthorized();
    }

    public function test_five_photos_at_size_limit_are_accepted_and_server_fields_are_protected(): void
    {
        $this->mock(ReportStatusService::class)->shouldReceive('initialize')->once();
        $user = User::factory()->create();
        $payload = $this->payload();
        $payload['photos'] = array_map(fn () => $this->photo()->size(5120), range(1, 5));
        $payload['reporter_id'] = User::factory()->create()->id;
        $payload['status'] = 'approved';
        $payload['revision'] = 99;
        $this->actingAs($user, 'api')->postJson('/api/v1/reports', $payload)
            ->assertCreated()->assertJsonCount(5, 'data.photos')
            ->assertJsonPath('data.reporter.id', $user->id)
            ->assertJsonPath('data.status', 'ai_validation')->assertJsonPath('data.revision', 1);
    }

    public function test_failed_status_initialization_rolls_back_database_and_files(): void
    {
        Event::fake([ReportSubmitted::class]);
        $user = User::factory()->create();
        $this->mock(ReportStatusService::class)->shouldReceive('initialize')->once()
            ->andThrow(new RuntimeException('Initialization failed'));
        try {
            app(CreateReportService::class)->create($user, $this->payload());
            $this->fail('Expected initialization failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Initialization failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('report_photos', 0);
        $this->assertDatabaseCount('tags', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        Event::assertNotDispatched(ReportSubmitted::class);
    }

    public function test_failed_second_upload_cleans_up_first_photo(): void
    {
        $this->mock(ReportStatusService::class)->shouldNotReceive('initialize');
        $realStorage = new ReportPhotoStorage;
        $storage = $this->mock(ReportPhotoStorage::class);
        $storage->shouldReceive('store')->once()->ordered()->andReturnUsing(
            fn ($file, $id, $disk) => $realStorage->store($file, $id, $disk)
        );
        $storage->shouldReceive('store')->once()->ordered()->andThrow(new RuntimeException('Upload failed'));
        try {
            app(CreateReportService::class)->create(User::factory()->create(), $this->payload());
            $this->fail('Expected upload failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Upload failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('report_photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_event_waits_for_commit_and_is_discarded_on_rollback(): void
    {
        $events = [];
        Event::listen(ReportSubmitted::class, function ($event) use (&$events): void {
            $events[] = $event;
        });
        $this->mock(ReportStatusService::class)->shouldReceive('initialize')->twice();
        $user = User::factory()->create();
        DB::beginTransaction();
        app(CreateReportService::class)->create($user, $this->payload());
        $this->assertCount(0, $events);
        DB::commit();
        $this->assertCount(1, $events);
        DB::beginTransaction();
        app(CreateReportService::class)->create($user, $this->payload());
        DB::rollBack();
        $this->assertCount(1, $events);
    }
}
