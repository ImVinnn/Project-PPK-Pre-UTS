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
        Schema::create('damage_reports', function (Blueprint $table) {
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
            $table->enum('category', Status::REPORT_CATEGORIES);
            $table->string('other_category', 100)->nullable();
            $table->text('description');
            $table->string('photo_path', 255);
            $table->enum('status', Status::REPORT_STATUSES)
                ->default(Status::REPORT_NEW);
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->index(['facility_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE damage_reports ADD CONSTRAINT damage_reports_other_category_check CHECK ((category = 'lainnya' AND other_category IS NOT NULL) OR (category <> 'lainnya' AND other_category IS NULL))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
