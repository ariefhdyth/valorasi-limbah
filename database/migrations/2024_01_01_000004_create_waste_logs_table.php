<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->index();
            $table->foreignId('collection_point_id')->constrained('collection_points')->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained('commodities')->cascadeOnDelete();
            $table->decimal('total_supply_kg', 10, 2)->default(0);
            $table->decimal('waste_weight_kg', 10, 2)->default(0);
            $table->decimal('organic_sortable_kg', 10, 2)->default(0);
            $table->decimal('inorganic_residue_kg', 10, 2)->default(0);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_logs');
    }
};
