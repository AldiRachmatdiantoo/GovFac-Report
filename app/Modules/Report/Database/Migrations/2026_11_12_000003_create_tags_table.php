<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            // Huruf kecil, tanpa tanda pagar.
            $table->string('name', 30)->unique();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('report_tag', function (Blueprint $table) {
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();

            $table->primary(['report_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_tag');
        Schema::dropIfExists('tags');
    }
};
