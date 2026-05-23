<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('mentor_id')->constrained('users');
            $table->string('course_title_snapshot');
            $table->unsignedBigInteger('course_price_snapshot')->default(0);
            $table->unsignedInteger('commission_rate_snapshot')->default(60);
            $table->unsignedBigInteger('mentor_amount')->default(0);
            $table->unsignedBigInteger('platform_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status', 50)->default(OrderStatus::Paid->value);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('course_id');
            $table->index('mentor_id');
            $table->index('status');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
