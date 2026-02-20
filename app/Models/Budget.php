<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    public function getSpentAmount()
    {
        $query = Expense::query()
            ->where('user_id', $this->user_id)
            ->where('type', 'expense')
            ->whereBetween('date', [$this->start_date, $this->end_date]);

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        return $query->sum('amount');
    }

    public function getRemainingAmount()
    {
        return $this->amount - $this->getSpentAmount();
    }

    public function getPercentageUsed()
    {
        if ($this->amount == 0) {
            return 0;
        }
        return ($this->getSpentAmount() / $this->amount) * 100;
    }
}
