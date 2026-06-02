<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 50)->unique();
            $table->string('business_name');
            $table->string('owner_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('zernio_profile_id')->nullable();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
