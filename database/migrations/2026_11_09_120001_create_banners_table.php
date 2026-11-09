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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 100)->nullable();
            $table->string('title_ar', 100)->nullable();
            $table->string('subtitle_en', 200)->nullable();
            $table->string('subtitle_ar', 200)->nullable();
            $table->string('button_label_en', 40)->nullable();
            $table->string('button_label_ar', 40)->nullable();
            $table->string('link', 255)->nullable();
            $table->string('image_path');
            $table->string('mobile_image_path')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
