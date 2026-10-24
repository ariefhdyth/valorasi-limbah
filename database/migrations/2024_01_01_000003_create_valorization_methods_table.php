<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valorization_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('product_name');
            $table->decimal('default_conversion_rate', 5, 4)->default(0);
            $table->decimal('product_price_per_kg', 12, 2)->default(0);
            $table->integer('duration_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valorization_methods');
    }
};
