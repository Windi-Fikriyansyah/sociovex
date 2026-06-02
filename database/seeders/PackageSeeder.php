<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Basic',
                'max_social_accounts' => 1,
                'max_users' => 1,
                'max_ai_replies' => 0,
                'price' => 199000,
                'has_ai_reply' => false,
                'has_analytics' => false,
                'has_inbox' => false,
                'has_multi_user' => false,
            ],
            [
                'name' => 'Pro',
                'max_social_accounts' => 5,
                'max_users' => 3,
                'max_ai_replies' => 500,
                'price' => 399000,
                'has_ai_reply' => true,
                'has_analytics' => false,
                'has_inbox' => true,
                'has_multi_user' => false,
            ],
            [
                'name' => 'Agency',
                'max_social_accounts' => 10,
                'max_users' => 10,
                'max_ai_replies' => 2000,
                'price' => 999000,
                'has_ai_reply' => true,
                'has_analytics' => true,
                'has_inbox' => true,
                'has_multi_user' => true,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(['name' => $package['name']], $package);
        }
    }
}
