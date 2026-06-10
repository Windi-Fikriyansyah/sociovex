// database/migrations/xxxx_add_is_ads_account_to_social_accounts.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAdsAccountToSocialAccounts extends Migration
{
    public function up()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->boolean('is_ads_account')->default(false)->after('status');
            $table->string('parent_account_id')->nullable()->after('is_ads_account'); // Untuk menyimpan parent account ID
            $table->json('ads_scope')->nullable()->after('parent_account_id'); // Untuk menyimpan scope ad accounts
        });
    }

    public function down()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_ads_account', 'parent_account_id', 'ads_scope']);
        });
    }
}