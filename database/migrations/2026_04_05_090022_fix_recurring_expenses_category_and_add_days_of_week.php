<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recurring_expenses', function (Blueprint $table) {
            // Drop the foreign key so category_id can hold Firestore string IDs
            $table->dropForeign(['category_id']);
            $table->string('category_id', 128)->nullable()->change();

            // Multi-day weekly recurrence (e.g. Mon–Fri = [1,2,3,4,5])
            $table->json('days_of_week')->nullable()->after('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->dropColumn('days_of_week');
        });
    }
};
