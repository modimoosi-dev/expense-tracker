<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BudgetAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Budget $budget,
        public float $percentageUsed,
        public string $threshold, // '80' or '100'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->threshold === '100' ? 'exceeded' : 'at 80%';
        $categoryName = $this->budget->category?->name ?? 'Overall';

        return [
            'type'           => 'budget_alert',
            'threshold'      => $this->threshold,
            'budget_id'      => $this->budget->id,
            'budget_name'    => $this->budget->name,
            'category_name'  => $categoryName,
            'amount'         => $this->budget->amount,
            'percentage_used' => round($this->percentageUsed, 1),
            'message'        => "Budget \"{$this->budget->name}\" ({$categoryName}) is {$label} — {$this->percentageUsed}% used.",
        ];
    }
}
