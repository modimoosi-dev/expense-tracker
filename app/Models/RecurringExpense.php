<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'type',
        'description',
        'payment_method',
        'frequency',
        'start_date',
        'end_date',
        'last_generated',
        'day_of_month',
        'day_of_week',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_generated' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForGeneration($query)
    {
        return $query->active()
            ->where('start_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function shouldGenerate(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date > now()) {
            return false;
        }

        if ($this->end_date && $this->end_date < now()) {
            return false;
        }

        if (!$this->last_generated) {
            return true;
        }

        return $this->getNextGenerationDate() <= now();
    }

    public function getNextGenerationDate()
    {
        $lastDate = $this->last_generated ?? $this->start_date;

        return match($this->frequency) {
            'daily' => $lastDate->addDay(),
            'weekly' => $lastDate->addWeek(),
            'monthly' => $lastDate->addMonth(),
            'yearly' => $lastDate->addYear(),
            default => $lastDate,
        };
    }

    public function generateExpense(): Expense
    {
        $expense = Expense::create([
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description . ' (Auto-generated)',
            'date' => now(),
            'payment_method' => $this->payment_method,
            'reference' => 'REC-' . $this->id . '-' . now()->format('YmdHis'),
        ]);

        $this->update(['last_generated' => now()]);

        return $expense;
    }
}
