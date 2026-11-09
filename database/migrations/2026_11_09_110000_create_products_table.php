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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('size_guide_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_en', 150);
            $table->string('name_ar', 150);
            $table->string('slug', 160)->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('details_en')->nullable();
            $table->text('details_ar')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('compare_at_price')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title_en', 70)->nullable();
            $table->string('meta_title_ar', 70)->nullable();
            $table->string('meta_description_en', 160)->nullable();
            $table->string('meta_description_ar', 160)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
