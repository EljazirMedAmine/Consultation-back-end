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
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insérer les rôles par défaut
        DB::table('roles')->insert([
            ['name' => 'Patient', 'code' => 'patient', 'description' => 'Role pour les patients', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medecin', 'code' => 'medecin', 'description' => 'Role pour les medecins', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Admin', 'code' => 'admin', 'description' => 'Role pour les administrateurs', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
