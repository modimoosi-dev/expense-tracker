<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expenseCategories = ['Food', 'Transportation', 'Utilities', 'Entertainment', 'Healthcare', 'Shopping', 'Education'];
        $incomeCategories = ['Salary', 'Freelance', 'Investment', 'Gift', 'Other'];
        $type = $this->faker->randomElement(['income', 'expense']);

        return [
            'name' => $type === 'expense'
                ? $this->faker->randomElement($expenseCategories)
                : $this->faker->randomElement($incomeCategories),
            'type' => $type,
            'color' => $this->faker->hexColor(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'name' => $this->faker->randomElement(['Salary', 'Freelance', 'Investment', 'Gift', 'Other']),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'name' => $this->faker->randomElement(['Food', 'Transportation', 'Utilities', 'Entertainment', 'Healthcare', 'Shopping', 'Education']),
        ]);
    }
}
