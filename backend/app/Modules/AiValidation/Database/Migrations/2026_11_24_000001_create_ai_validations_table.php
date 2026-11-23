<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('verdict', 10);
            // Null when the AI was unavailable and the report fell back to `flag`.
            $table->float('score')->nullable();
            $table->string('criticality', 10)->nullable();
            $table->json('reasons');
            $table->string('model', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(['report_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_validations');
    }
};
