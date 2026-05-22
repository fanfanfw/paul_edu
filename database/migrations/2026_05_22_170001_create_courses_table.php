<?php

use App\Enums\CourseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users');
            $table->foreignId('category_id')->constrained('course_categories');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('status', 50)->default(CourseStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_by_mentor_at')->nullable();
            $table->timestamp('hidden_by_admin_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('mentor_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
