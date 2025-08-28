<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();

            // Référence à l'utilisateur
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Référence à la spécialité
            $table->unsignedBigInteger('speciality_id');
            $table->foreign('speciality_id')->references('id')->on('specialities');

            // Informations personnelles supplémentaires
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('national_id')->nullable()->comment('Numéro d\'identification nationale');

            // Informations professionnelles
            $table->string('license_number')->unique()->comment('Numéro de licence médicale');
            $table->text('qualifications')->nullable()->comment('Diplômes et qualifications');
            $table->integer('years_of_experience')->nullable();

            // Informations de contact professionnel
            $table->string('office_phone')->nullable();
            $table->string('office_address')->nullable();
            $table->string('hospital_affiliation')->nullable();

            // Statut et vérification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('doctors');
    }
};
