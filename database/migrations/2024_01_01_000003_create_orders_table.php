<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the orders table.
 *
 * The orders table is foreign-keyed to users. Additional columns
 * (line items, totals, status, etc.) will be added in the orders
 * feature branch migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            // Index for the most common query path: orders by user + status
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
