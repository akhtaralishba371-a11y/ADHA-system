<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clinicians', function (Blueprint $table) {
            $table->string('professional_title')->nullable()->after('display_name');
            $table->string('photo_path')->nullable()->after('bio');
            $table->boolean('is_public')->default(true)->after('is_active');

            $table->index(['is_active', 'is_public'], 'clinicians_public_directory_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinicians', function (Blueprint $table) {
            $table->dropIndex('clinicians_public_directory_index');
            $table->dropColumn(['professional_title', 'photo_path', 'is_public']);
        });
    }
};
