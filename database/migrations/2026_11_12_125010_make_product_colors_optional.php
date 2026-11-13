<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('product_colors')->whereNull('color_id')->exists()) {
            throw new RuntimeException('Assign a color to every product gallery before rolling back this migration.');
        }

        Schema::table('product_colors', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable(false)->change();
        });
    }
};
