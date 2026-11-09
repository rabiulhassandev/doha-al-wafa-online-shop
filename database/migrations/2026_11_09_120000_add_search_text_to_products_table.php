<?php

use App\Support\SearchText;
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
        Schema::table('products', function (Blueprint $table) {
            $table->text('search_text')->nullable()->after('meta_description_ar');
        });

        DB::table('products')->orderBy('id')->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'search_text' => SearchText::normalize("{$product->name_en} {$product->name_ar} {$product->code}"),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('search_text');
        });
    }
};
