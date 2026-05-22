<?php

use App\Enums\MaterialType;
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
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('lesson_id')->nullable()->constrained('course_lessons');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 50)->default(MaterialType::Pdf->value);
            $table->string('file_path', 500);
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'sort_order']);
            $table->index(['lesson_id', 'sort_order']);
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
