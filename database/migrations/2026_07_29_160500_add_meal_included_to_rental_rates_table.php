<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_rates', function (Blueprint $table) {
            $table->boolean('meal_included')->default(false)->after('daily_rate');
            $table->decimal('meal_price', 12, 2)->nullable()->after('meal_included');
        });
    }

    public function down(): void
    {
        Schema::table('rental_rates', function (Blueprint $table) {
            $table->dropColumn(['meal_included', 'meal_price']);
        });
    }
};
