<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->unsignedInteger('check_interval')->default(1)->after('code')->comment('minutes');
            $table->timestamp('last_checked_at')->nullable()->after('check_interval');
        });

        // set default check_interval for existing rows if any
        if (Schema::hasTable('links')) {
            \DB::table('links')->whereNull('check_interval')->update(['check_interval' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['check_interval', 'last_checked_at']);
        });
    }
};
