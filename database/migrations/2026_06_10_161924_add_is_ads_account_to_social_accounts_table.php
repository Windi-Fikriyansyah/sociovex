<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            // Cek apakah kolom sudah ada sebelum menambah
            if (!Schema::hasColumn('social_accounts', 'is_ads_account')) {
                $table->boolean('is_ads_account')->default(false)->after('status');
            }
            
            if (!Schema::hasColumn('social_accounts', 'parent_account_id')) {
                $table->foreignId('parent_account_id')
                    ->nullable()
                    ->after('is_ads_account')
                    ->constrained('social_accounts')
                    ->nullOnDelete();
            }
            
            if (!Schema::hasColumn('social_accounts', 'ads_scope')) {
                $table->json('ads_scope')->nullable()->after('parent_account_id');
            }
        });
    }

    public function down()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_account_id');
            $table->dropColumn(['is_ads_account', 'ads_scope']);
        });
    }
};
