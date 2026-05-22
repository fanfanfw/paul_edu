<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseCategorySeeder extends Seeder
{
    /**
     * Seed course categories.
     */
    public function run(): void
    {
        foreach ([
            'Web Development',
            'Digital Marketing',
            'UI/UX Design',
            'Data Analytics',
            'Business',
            'Productivity',
        ] as $index => $name) {
            CourseCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
