<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Adult ADHD assessment',
                'short_name' => 'ADHD assessment',
                'description' => 'A complete diagnostic assessment with a consultant psychiatrist.',
                'slug' => 'adult-adhd-assessment',
                'pathway' => 'adult',
                'duration_minutes' => 90,
                'duration_label' => '75–90 mins',
                'price_pence' => 69500,
                'in_person_price_pence' => 109500,
                'sort_order' => 1,
            ],
            [
                'name' => 'Adult autism assessment',
                'short_name' => 'Autism assessment',
                'description' => 'A specialist-led assessment with a clear report and next steps.',
                'slug' => 'adult-autism-assessment',
                'pathway' => 'adult',
                'duration_minutes' => 120,
                'duration_label' => 'Up to 2 hrs',
                'price_pence' => 139500,
                'sort_order' => 2,
            ],
            [
                'name' => 'Combined ADHD and autism assessment',
                'short_name' => 'Combined ADHD & autism',
                'description' => 'One joined-up pathway exploring overlapping and distinct traits.',
                'slug' => 'adult-combined-assessment',
                'pathway' => 'adult',
                'duration_minutes' => 120,
                'duration_label' => 'Up to 2 hrs',
                'price_pence' => 159500,
                'sort_order' => 3,
            ],
            [
                'name' => 'Adult follow-up appointment',
                'short_name' => 'Follow-up appointment',
                'description' => 'For existing patients continuing care with their clinical team.',
                'slug' => 'adult-follow-up-appointment',
                'pathway' => 'adult',
                'duration_minutes' => 30,
                'duration_label' => '30 mins',
                'price_pence' => 19500,
                'sort_order' => 4,
            ],
            [
                'name' => 'Child ADHD assessment',
                'short_name' => 'Child ADHD assessment',
                'description' => 'A complete diagnostic assessment with a consultant psychiatrist.',
                'slug' => 'child-adhd-assessment',
                'pathway' => 'child',
                'duration_minutes' => 90,
                'duration_label' => '90–120 mins',
                'price_pence' => 109500,
                'sort_order' => 1,
            ],
            [
                'name' => 'Child autism assessment',
                'short_name' => 'Child autism assessment',
                'description' => 'A specialist-led assessment with a clear report and next steps.',
                'slug' => 'child-autism-assessment',
                'pathway' => 'child',
                'duration_minutes' => 120,
                'duration_label' => 'Stage 1 assessment',
                'price_pence' => 129500,
                'sort_order' => 2,
            ],
            [
                'name' => 'Combined child ADHD and autism assessment',
                'short_name' => 'Combined ADHD & autism',
                'description' => 'One joined-up pathway exploring overlapping and distinct traits.',
                'slug' => 'child-combined-assessment',
                'pathway' => 'child',
                'duration_minutes' => 150,
                'duration_label' => 'Up to 2.5 hrs',
                'price_pence' => 169500,
                'sort_order' => 3,
            ],
            [
                'name' => 'Child follow-up appointment',
                'short_name' => 'Follow-up appointment',
                'description' => 'For existing patients continuing care with their clinical team.',
                'slug' => 'child-follow-up-appointment',
                'pathway' => 'child',
                'duration_minutes' => 30,
                'duration_label' => '30 mins',
                'price_pence' => 19500,
                'sort_order' => 4,
            ],
        ] as $service) {
            Service::query()->updateOrCreate(
                ['slug' => $service['slug']],
                $service + ['is_active' => true],
            );
        }
    }
}
