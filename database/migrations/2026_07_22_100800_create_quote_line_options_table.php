<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_line_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_line_id')->constrained('quote_lines')->cascadeOnDelete();
            $table->foreignId('option_type_id')->constrained('option_types')->restrictOnDelete();
            $table->enum('mode', ['fixed', 'percentage']);
            $table->decimal('value', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_line_options');
    }
};
