<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Short links for social bios and campaigns, and where each order came from.
     */
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label', 100);
            // A store path with its query string, e.g. "/ar/products/classic-jelabiya?color=navy".
            $table->string('destination', 500);
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('coupon_code', 30)->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('user_agent');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium');
            $table->string('utm_content', 100)->nullable()->after('utm_campaign');
            $table->string('utm_term', 100)->nullable()->after('utm_content');
            // Which ad platform's click ID came with the visit ("meta", "tiktok", "snapchat", "google").
            $table->string('click_id_type', 20)->nullable()->after('utm_term');
            $table->string('click_id', 255)->nullable()->after('click_id_type');
            $table->string('referrer', 500)->nullable()->after('click_id');
            $table->string('landing_url', 500)->nullable()->after('referrer');
            $table->foreignId('short_link_id')->nullable()->after('landing_url')->constrained()->nullOnDelete();
            // Whether the shopper allowed ad tracking, which decides if the order is sent to ad platforms.
            $table->boolean('ad_tracking_allowed')->default(false)->after('short_link_id');
            // Browser IDs the ad platforms set (Meta "_fbp"/"_fbc", TikTok "_ttp"), to match server events.
            $table->json('tracking_ids')->nullable()->after('ad_tracking_allowed');

            $table->index(['utm_source', 'placed_at']);
            $table->index('utm_campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['utm_source', 'placed_at']);
            $table->dropIndex(['utm_campaign']);
            $table->dropConstrainedForeignId('short_link_id');
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                'click_id_type', 'click_id', 'referrer', 'landing_url', 'ad_tracking_allowed', 'tracking_ids',
            ]);
        });

        Schema::dropIfExists('short_links');
    }
};
