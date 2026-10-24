<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valorization_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->foreignId('valorization_method_id')->constrained('valorization_methods')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('expected_harvest_date');
            $table->date('actual_harvest_date')->nullable();
            $table->decimal('input_organic_kg', 10, 2)->default(0);
            $table->decimal('estimated_output_kg', 10, 2)->default(0);
            $table->decimal('actual_output_kg', 10, 2)->nullable();
            $table->decimal('process_residue_kg', 10, 2)->default(0);
            $table->string('status')->default('processing'); // processing, harvested, cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valorization_batches');
    }
};
