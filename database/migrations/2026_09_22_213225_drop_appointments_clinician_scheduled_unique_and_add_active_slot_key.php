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
     * Before: unique(clinician_id, scheduled_for) blocked every pair forever, including
     * after cancellation, so the same clinician/time could not be rebooked.
     *
     * After: nullable unique active_slot_key = "{clinician_id}|{scheduled_for}" for
     * non-cancelled rows only (NULL when cancelled / unassigned). MySQL and SQLite
     * allow multiple NULL unique values, so cancelled history does not collide.
     * AppointmentSlotGuard still locks the clinician row and checks overlaps inside
     * a transaction; UniqueConstraintViolationException maps to a validation error
     * if two concurrent requests race past the pre-check.
     *
     * MySQL note: the old unique index also satisfied the clinician_id foreign key.
     * Drop the FK first, replace uniqueness, then restore the FK using the new
     * (clinician_id, scheduled_for, status) index.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['clinician_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['clinician_id', 'scheduled_for']);
            $table->string('active_slot_key', 64)->nullable()->after('cancelled_at');
            $table->unique('active_slot_key');
            $table->index(['clinician_id', 'scheduled_for', 'status'], 'appointments_clinician_schedule_status_index');
            $table->foreign('clinician_id')->references('id')->on('clinicians')->nullOnDelete();
        });

        DB::table('appointments')
            ->whereNotNull('clinician_id')
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->eachById(function (object $appointment): void {
                DB::table('appointments')->where('id', $appointment->id)->update([
                    'active_slot_key' => $appointment->clinician_id.'|'.$appointment->scheduled_for,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['clinician_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['active_slot_key']);
            $table->dropIndex('appointments_clinician_schedule_status_index');
            $table->dropColumn('active_slot_key');
            $table->unique(['clinician_id', 'scheduled_for']);
            $table->foreign('clinician_id')->references('id')->on('clinicians')->nullOnDelete();
        });
    }
};
