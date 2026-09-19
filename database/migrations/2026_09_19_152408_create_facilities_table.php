<?php

use App\Support\Status;
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
        Schema::create('facilities', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('building_id')
                ->nullable()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 120);
            $table->enum('type', Status::FACILITY_TYPES);
            $table->unsignedInteger('capacity')->nullable();
            $table->string('location_detail', 200);
            $table->text('description')->nullable();
            $table->enum('status', Status::FACILITY_STATUSES)
                ->default(Status::FACILITY_ACTIVE);
            $table->timestamps();
            $table->index(['status', 'type']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE facilities ADD CONSTRAINT facilities_capacity_check CHECK ((type = 'alat' AND capacity IS NULL) OR (type <> 'alat' AND capacity > 0))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
