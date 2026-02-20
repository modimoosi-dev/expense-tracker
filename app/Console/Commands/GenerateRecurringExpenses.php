<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateRecurringExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate expenses from recurring expense templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recurringExpenses = \App\Models\RecurringExpense::dueForGeneration()->get();

        $generated = 0;

        foreach ($recurringExpenses as $recurring) {
            if ($recurring->shouldGenerate()) {
                $recurring->generateExpense();
                $generated++;
                $this->info("Generated expense from recurring template: {$recurring->description}");
            }
        }

        $this->info("Total expenses generated: {$generated}");

        return 0;
    }
}
