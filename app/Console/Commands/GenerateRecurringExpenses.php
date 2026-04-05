<?php

namespace App\Console\Commands;

use App\Models\RecurringExpense;
use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Firestore;

class GenerateRecurringExpenses extends Command
{
    protected $signature = 'recurring:generate';
    protected $description = 'Generate expenses from recurring expense templates into Firestore';

    public function handle(Firestore $firestore)
    {
        $db = $firestore->database();
        $recurringExpenses = RecurringExpense::dueForGeneration()->get();
        $generated = 0;

        foreach ($recurringExpenses as $recurring) {
            if (!$recurring->shouldGenerate()) {
                continue;
            }

            // Respect days_of_week if set
            if (!empty($recurring->days_of_week)) {
                $todayDow = (int) now()->format('w'); // 0=Sun, 6=Sat
                if (!in_array($todayDow, $recurring->days_of_week)) {
                    $this->line("Skipping '{$recurring->description}' — not an active day.");
                    continue;
                }
            }

            $db->collection('expenses')->add([
                'user_id'        => $recurring->user_id,
                'category_id'    => $recurring->category_id ?? '',
                'amount'         => (float) $recurring->amount,
                'type'           => $recurring->type,
                'description'    => ($recurring->description ?? '') . ' (Auto-generated)',
                'date'           => now()->toDateString(),
                'payment_method' => $recurring->payment_method ?? '',
                'created_at'     => now()->toDateTimeString(),
            ]);

            $recurring->update(['last_generated' => now()]);
            $generated++;
            $this->info("Generated: {$recurring->description}");
        }

        $this->info("Total generated: {$generated}");
        return 0;
    }
}
