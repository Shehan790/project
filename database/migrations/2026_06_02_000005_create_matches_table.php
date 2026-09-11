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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
            $table->foreignId('team1_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('team2_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('winner_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->integer('round_number');
            $table->integer('match_number_in_round');
            $table->dateTime('match_time')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'disputed', 'completed'])->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
