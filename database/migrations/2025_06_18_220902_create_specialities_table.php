<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('specialities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insertion des spécialités médicales de base
        DB::table('specialities')->insert([
            ['name' => 'Médecine générale', 'description' => 'Médecine générale et familiale'],
            ['name' => 'Cardiologie', 'description' => 'Spécialité des maladies du cœur et des vaisseaux'],
            ['name' => 'Dermatologie', 'description' => 'Spécialité des maladies de la peau'],
            ['name' => 'Endocrinologie', 'description' => 'Spécialité des maladies hormonales'],
            ['name' => 'Gastro-entérologie', 'description' => 'Spécialité des maladies digestives'],
            ['name' => 'Gynécologie', 'description' => 'Spécialité de la santé féminine'],
            ['name' => 'Hématologie', 'description' => 'Spécialité des maladies du sang'],
            ['name' => 'Neurologie', 'description' => 'Spécialité des maladies du système nerveux'],
            ['name' => 'Oncologie', 'description' => 'Spécialité des cancers'],
            ['name' => 'Ophtalmologie', 'description' => 'Spécialité des maladies des yeux'],
            ['name' => 'ORL', 'description' => 'Oto-rhino-laryngologie (oreille, nez, gorge)'],
            ['name' => 'Pédiatrie', 'description' => 'Spécialité médicale pour enfants'],
            ['name' => 'Pneumologie', 'description' => 'Spécialité des maladies respiratoires'],
            ['name' => 'Psychiatrie', 'description' => 'Spécialité des troubles mentaux'],
            ['name' => 'Radiologie', 'description' => 'Spécialité de l\'imagerie médicale'],
            ['name' => 'Rhumatologie', 'description' => 'Spécialité des maladies des articulations'],
            ['name' => 'Urologie', 'description' => 'Spécialité des voies urinaires'],
            ['name' => 'Chirurgie générale', 'description' => 'Chirurgie polyvalente'],
            ['name' => 'Chirurgie orthopédique', 'description' => 'Chirurgie des os et articulations'],
            ['name' => 'Anesthésiologie', 'description' => 'Spécialité de l\'anesthésie'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('specialities');
    }
};
