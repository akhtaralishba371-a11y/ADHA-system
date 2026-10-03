<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Null means the clinician has not activated their invitation yet.
     * Existing staff users are backfilled so current logins keep working.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('invitation_accepted_at')->nullable()->after('remember_token');
        });

        DB::table('users')->whereNull('invitation_accepted_at')->update([
            'invitation_accepted_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('invitation_accepted_at');
        });
    }
};
