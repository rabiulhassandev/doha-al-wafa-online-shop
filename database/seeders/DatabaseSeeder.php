<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for local development.
     *
     * Production owners are created with "php artisan shop:create-owner".
     */
    public function run(): void
    {
        User::factory()->owner()->create([
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
        ]);

        User::factory()->staff()->create([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
        ]);

        $this->call([
            CatalogDefaultsSeeder::class,
            DeliveryZoneSeeder::class,
            ContentSeeder::class,
            DemoProductsSeeder::class,
        ]);
    }
}
