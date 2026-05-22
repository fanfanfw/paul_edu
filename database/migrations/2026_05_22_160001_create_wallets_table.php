<?php

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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 100);
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('balance')->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
            $table->index('owner_type');
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
