<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where(function ($q) {
                $q->where('currency', 'USD')->orWhereNull('currency');
            })
            ->update(['currency' => 'BWP']);
    }

    public function down(): void {}
};
