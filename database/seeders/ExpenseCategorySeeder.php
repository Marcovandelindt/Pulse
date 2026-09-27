<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Drinks',   'icon' => '🍔', 'color' => '#f59e0b'],
            ['name' => 'Groceries',        'icon' => '🛒', 'color' => '#10b981'],
            ['name' => 'Gaming',           'icon' => '🎮', 'color' => '#6366f1'],
            ['name' => 'Subscriptions',    'icon' => '📺', 'color' => '#8b5cf6'],
            ['name' => 'Transport',        'icon' => '🚗', 'color' => '#3b82f6'],
            ['name' => 'Health',           'icon' => '💊', 'color' => '#ef4444'],
            ['name' => 'Clothing',         'icon' => '👕', 'color' => '#ec4899'],
            ['name' => 'Electronics',      'icon' => '💻', 'color' => '#0ea5e9'],
            ['name' => 'Going out',        'icon' => '🎉', 'color' => '#f97316'],
            ['name' => 'Other',            'icon' => '📦', 'color' => '#6b7280'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
