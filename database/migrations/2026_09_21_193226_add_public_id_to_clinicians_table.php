<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clinicians', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->after('id');
        });

        DB::table('clinicians')->orderBy('id')->eachById(function (object $clinician): void {
            DB::table('clinicians')->where('id', $clinician->id)->update(['public_id' => (string) Str::uuid()]);
        });

        Schema::table('clinicians', function (Blueprint $table) {
            $table->unique('public_id', 'clinicians_public_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinicians', function (Blueprint $table) {
            $table->dropUnique('clinicians_public_id_unique');
            $table->dropColumn('public_id');
        });
    }
};
