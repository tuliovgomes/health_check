<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('remember_token');
            $table->unsignedInteger('links_quota')->nullable()->after('plan');
            $table->string('stripe_status')->nullable()->after('links_quota');
        });

        // set default quota for existing users
        if (Schema::hasTable('users')) {
            \DB::table('users')->whereNull('links_quota')->update(['links_quota' => config('plans.plans.free.links_quota')]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'links_quota', 'stripe_status']);
        });
    }
};
