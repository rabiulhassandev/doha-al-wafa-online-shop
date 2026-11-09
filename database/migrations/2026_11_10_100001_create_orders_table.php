<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amounts are whole dirhams. Customer, address and delivery area details are copied onto the
     * order, so it reads the same after the customer or the area changes.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Set straight after insert from the ID, e.g. "DW-10001".
            $table->string('number', 20)->nullable()->unique();
            // One per checkout page, so a double tap cannot place the order twice.
            $table->uuid('checkout_token')->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('channel', 20);
            $table->string('status', 30);
            $table->string('payment_method', 30);
            $table->string('payment_status', 20);
            $table->char('currency', 3)->default('QAR');

            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount_total')->default(0);
            $table->unsignedInteger('delivery_fee');
            $table->unsignedInteger('tax_total')->default(0);
            $table->unsignedInteger('grand_total');

            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 190)->nullable();

            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_zone_name_en', 80);
            $table->string('delivery_zone_name_ar', 80);
            $table->string('zone_number', 4);
            $table->string('street_number', 4);
            $table->string('building_number', 4);
            $table->string('unit', 60)->nullable();
            $table->string('landmark', 200)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->date('preferred_delivery_date')->nullable();
            $table->date('delivery_estimate_from');
            $table->date('delivery_estimate_to');
            $table->string('customer_note', 500)->nullable();
            $table->string('locale', 5);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index('placed_at');
            $table->index('customer_phone');
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
