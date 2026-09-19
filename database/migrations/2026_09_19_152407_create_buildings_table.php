<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 120);
            $table->timestamps();
            $table->unique(['faculty_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
