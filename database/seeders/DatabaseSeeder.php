<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create income categories
        $incomeCategories = [
            ['name' => 'Salary', 'type' => 'income', 'color' => '#10B981'],
            ['name' => 'Freelance', 'type' => 'income', 'color' => '#3B82F6'],
            ['name' => 'Investment', 'type' => 'income', 'color' => '#8B5CF6'],
            ['name' => 'Gift', 'type' => 'income', 'color' => '#EC4899'],
            ['name' => 'Other Income', 'type' => 'income', 'color' => '#6366F1'],
        ];

        // Create expense categories
        $expenseCategories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'color' => '#EF4444'],
            ['name' => 'Transportation', 'type' => 'expense', 'color' => '#F59E0B'],
            ['name' => 'Utilities', 'type' => 'expense', 'color' => '#14B8A6'],
            ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#F97316'],
            ['name' => 'Healthcare', 'type' => 'expense', 'color' => '#06B6D4'],
            ['name' => 'Shopping', 'type' => 'expense', 'color' => '#A855F7'],
            ['name' => 'Education', 'type' => 'expense', 'color' => '#84CC16'],
            ['name' => 'Housing', 'type' => 'expense', 'color' => '#64748B'],
        ];

        foreach (array_merge($incomeCategories, $expenseCategories) as $category) {
            \App\Models\Category::create($category);
        }

        // Create sample expenses and income
        $categories = \App\Models\Category::all();

        // Generate 30 random transactions
        for ($i = 0; $i < 30; $i++) {
            $category = $categories->random();
            \App\Models\Expense::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => rand(10, 5000),
                'type' => $category->type,
                'description' => $category->type === 'income'
                    ? 'Income from ' . $category->name
                    : 'Payment for ' . $category->name,
                'date' => now()->subDays(rand(0, 90)),
                'payment_method' => ['cash', 'credit_card', 'debit_card', 'bank_transfer'][rand(0, 3)],
            ]);
        }
    }
}
