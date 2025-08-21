<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Vulnerabilities & Vulnerability Management',
                'description' => 'Advisories, CVEs, etc.',
                'color' => '#3B82F6', // Blue
            ],
            [
                'name' => 'Incident Response',
                'description' => 'Playbooks, case studies, timelines, lessons learned',
                'color' => '#10B981', // Green
            ],
            [
                'name' => 'Security Operations',
                'description' => 'Procedures, runbooks, dashboards',
                'color' => '#EF4444', // Red
            ],
            [
                'name' => 'Web Development',
                'description' => 'Frontend, backend, and full-stack web development',
                'color' => '#06B6D4', // Cyan
            ],
            [
                'name' => 'Tools & Resources',
                'description' => 'Development tools, utilities, and helpful resources',
                'color' => '#84CC16', // Lime
            ],
            [
                'name' => 'Tutorials',
                'description' => 'Step-by-step guides and educational content',
                'color' => '#EC4899', // Pink
            ],
            [
                'name' => 'Tools & Automation',
                'description' => 'Custom scripts, tooling, CLIs, CI/CD jobs, executables',
                'color' => '#F97316', // Orange
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
    }
}
