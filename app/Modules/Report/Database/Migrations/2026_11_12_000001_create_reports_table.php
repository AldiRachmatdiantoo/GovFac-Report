<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // Format GFR-YYYY-NNNNNN. Nullable agar bisa diturunkan dari id setelah insert.
            $table->string('code', 20)->nullable()->unique();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 100);
            $table->text('description');
            $table->string('status', 20)->default('ai_validation')->index();
            $table->string('criticality', 10)->nullable()->index();
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedInteger('likes_count')->default(0)->index();
            $table->timestamps();

            $table->index(['reporter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
