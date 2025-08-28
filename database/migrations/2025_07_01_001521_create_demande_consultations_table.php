<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('demande_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('requested_by')->constrained('users'); // Par exemple la personne qui fait la demande
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('requested_start_time');
            $table->dateTime('requested_end_time');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('demande_consultations');
    }
};

