<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Each line keeps a copy of what was sold (names, color, size, SKU, photo and prices), so
     * order history survives later product edits or deletion.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_en', 150);
            $table->string('product_name_ar', 150);
            $table->string('color_name_en', 60);
            $table->string('color_name_ar', 60);
            $table->string('color_hex', 7);
            $table->string('size_name_en', 30);
            $table->string('size_name_ar', 30)->nullable();
            $table->string('sku', 60);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('compare_at_price')->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();

            $table->index('product_id');
            $table->index('product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
