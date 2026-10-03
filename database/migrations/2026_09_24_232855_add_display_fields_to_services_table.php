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
        Schema::table('services', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('short_name');
            $table->string('duration_label', 80)->nullable()->after('duration_minutes');
            $table->unsignedInteger('in_person_price_pence')->nullable()->after('price_pence');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });

        $defaults = [
            'adult-adhd-assessment' => [
                'short_name' => 'ADHD assessment',
                'description' => 'A complete diagnostic assessment with a consultant psychiatrist.',
                'duration_label' => '75–90 mins',
                'in_person_price_pence' => 109500,
                'sort_order' => 1,
            ],
            'adult-autism-assessment' => [
                'short_name' => 'Autism assessment',
                'description' => 'A specialist-led assessment with a clear report and next steps.',
                'duration_label' => 'Up to 2 hrs',
                'sort_order' => 2,
            ],
            'adult-combined-assessment' => [
                'short_name' => 'Combined ADHD & autism',
                'description' => 'One joined-up pathway exploring overlapping and distinct traits.',
                'duration_label' => 'Up to 2 hrs',
                'sort_order' => 3,
            ],
            'adult-follow-up-appointment' => [
                'short_name' => 'Follow-up appointment',
                'description' => 'For existing patients continuing care with their clinical team.',
                'duration_label' => '30 mins',
                'sort_order' => 4,
            ],
            'child-adhd-assessment' => [
                'short_name' => 'Child ADHD assessment',
                'description' => 'A complete diagnostic assessment with a consultant psychiatrist.',
                'duration_label' => '90–120 mins',
                'sort_order' => 1,
            ],
            'child-autism-assessment' => [
                'short_name' => 'Child autism assessment',
                'description' => 'A specialist-led assessment with a clear report and next steps.',
                'duration_label' => 'Stage 1 assessment',
                'sort_order' => 2,
            ],
            'child-combined-assessment' => [
                'short_name' => 'Combined ADHD & autism',
                'description' => 'One joined-up pathway exploring overlapping and distinct traits.',
                'duration_label' => 'Up to 2.5 hrs',
                'sort_order' => 3,
            ],
            'child-follow-up-appointment' => [
                'short_name' => 'Follow-up appointment',
                'description' => 'For existing patients continuing care with their clinical team.',
                'duration_label' => '30 mins',
                'sort_order' => 4,
            ],
        ];

        foreach ($defaults as $slug => $values) {
            DB::table('services')->where('slug', $slug)->update($values);
        }

        foreach (DB::table('services')->whereNull('short_name')->orderBy('id')->get() as $service) {
            DB::table('services')->where('id', $service->id)->update([
                'short_name' => $service->name,
                'description' => 'A specialist appointment with the Jelly care team.',
                'duration_label' => $service->duration_minutes.' mins',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'short_name',
                'description',
                'duration_label',
                'in_person_price_pence',
                'sort_order',
            ]);
        });
    }
};
