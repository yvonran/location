<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->string('model');
            $table->unsignedTinyInteger('seats');
            $table->string('registration_number')->unique();
            $table->unsignedSmallInteger('year');
            $table->boolean('has_air_conditioning')->default(false);
            $table->enum('status', ['available', 'maintenance', 'out_of_service'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
