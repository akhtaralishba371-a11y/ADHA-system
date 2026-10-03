<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('appointments')->where('status', 'pending_payment')->update(['status' => 'pending_confirmation']);

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status', 32)->default('pending_confirmation')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status', 32)->default('pending_payment')->change();
        });
    }
};
