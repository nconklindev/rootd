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
                'name' => 'CTFs',
                'description' => 'Hack a really hard machine on Hack the Box? Complete a Learning Path on TryHackMe? Or, did you do something else CTF related? Share it all here!',
                'color' => '#10B981', // Vibrant Emerald
            ],
            [
                'name' => 'Tools & Scripts',
                'description' => 'Share all the fancy tools and scripts you\'ve written here!',
                'color' => '#FBBF24', // Vibrant Amber
            ],
            [
                'name' => 'Tutorials',
                'description' => 'Have something you want to share on how to do something? Share it here.',
                'color' => '#F59E0B', // Vibrant Orange
            ],
            [
                'name' => 'Industry News',
                'description' => 'Share everything industry related from new vulnerabilities to articles and videos discussing Cybersecurity news.',
                'color' => '#06B6D4', // Vibrant Cyan
            ],
            [
                'name' => 'Engagements',
                'description' => 'Discuss all things engagement related here. If your team is involved in something, share it with others and discuss.',
                'color' => '#F472B6', // Vibrant Pink
            ],
            [
                'name' => 'Certifications & Career',
                'description' => 'Discuss everything career and certification related here. Share your recent accomplishments! Don\'t worry, it\'s okay to brag.',
                'color' => '#EF4444', // Vibrant Red
            ],
            [
                'name' => 'General',
                'description' => 'General Security work topic for anything that doesn\'t fit into the other categories',
                'color' => '#8B5CF6', // Vibrant Violet
            ],
            [
                'name' => 'Red Team Readout',
                'description' => 'All things Red Team. Where our Red Team members can discuss things happening in their organization and others can come and read and discuss.',
                'color' => '#F87171', // Vibrant Light Red
            ],
            [
                'name' => 'Blue Team Backyard',
                'description' => 'All things Blue Team. Where our Blue Team members can discuss things happening in their organization and others can come and read and discuss.',
                'color' => '#60A5FA', // Vibrant Light Blue
            ],
            [
                'name' => 'Home Lab',
                'description' => 'Created something cool at home on that dusty old PC you want to share with others? Tell us what you did!',
                'color' => '#34D399', // Vibrant Mint Green
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
    }
}
