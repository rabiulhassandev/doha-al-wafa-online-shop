<?php

use App\Support\SearchText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['categories', 'collections', 'colors'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->text('search_name')->nullable();
            });

            DB::table($tableName)->select(['id', 'name_en', 'name_ar'])->eachById(function (object $record) use ($tableName): void {
                DB::table($tableName)->where('id', $record->id)->update([
                    'search_name' => SearchText::normalize("{$record->name_en} {$record->name_ar}"),
                ]);
            });
        }

        DB::table('products')->whereNull('search_text')->select(['id', 'name_en', 'name_ar', 'code'])
            ->eachById(function (object $product): void {
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
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('search_name');
            });
        }
    }
};
