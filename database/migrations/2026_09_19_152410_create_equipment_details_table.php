<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_details', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->foreignId('facility_id')
                ->primary()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('stock_total');
            $table->unsignedInteger('stock_unavailable')->default(0);
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE equipment_details ADD CONSTRAINT equipment_stock_check CHECK (stock_total >= 1 AND stock_unavailable <= stock_total)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_details');
    }
};
