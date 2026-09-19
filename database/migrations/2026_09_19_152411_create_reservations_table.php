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
        Schema::create('reservations', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('facility_id')
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('purpose', 255);
            $table->enum('status', Status::RESERVATION_STATUSES)
                ->default(Status::RESERVATION_PENDING);
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(
                ['facility_id', 'status', 'start_time', 'end_time'],
                'reservations_facility_status_time_index'
            );
            $table->index(
                ['user_id', 'status', 'start_time', 'end_time'],
                'reservations_user_status_time_index'
            );
            $table->index(['status', 'start_time']);
            $table->index('created_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE reservations ADD CONSTRAINT reservations_quantity_check CHECK (quantity >= 1)'
            );
            DB::statement(
                'ALTER TABLE reservations ADD CONSTRAINT reservations_time_range_check CHECK (end_time > start_time)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
