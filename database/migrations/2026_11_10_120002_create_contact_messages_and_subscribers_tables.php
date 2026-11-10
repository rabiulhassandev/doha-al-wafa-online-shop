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
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            // E.164, e.g. "+97455551234".
            $table->string('phone', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('message');
            $table->string('locale', 5);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['read_at', 'created_at']);
        });

        // People who agreed to receive news and offers, by WhatsApp number or email.
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('email', 190)->nullable()->unique();
            $table->string('locale', 5);
            $table->string('source', 30);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('contact_messages');
    }
};
