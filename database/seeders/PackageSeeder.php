<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Single Pro plan — all features unlocked, no limits
        $packages = [
            [
                'name'               => 'Pro',
                'max_social_accounts' => 999,
                'max_users'          => 999,
                'max_ai_replies'     => 999999,
                'price'              => 399000,
                'has_ai_reply'       => true,
                'has_analytics'      => true,
                'has_inbox'          => true,
                'has_multi_user'     => true,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(['name' => $package['name']], $package);
        }

        // Remove legacy packages (Basic, Agency) that no longer exist
        Package::whereNotIn('name', collect($packages)->pluck('name'))->delete();
    }
}
