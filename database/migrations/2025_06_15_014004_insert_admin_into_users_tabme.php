<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->insert([
            'full_name' => 'Admin',
            'email' => 'consultnow@admin.com',
            'password' => Hash::make('Admin123!'),
            'telephone' => '0666124585',
            'status' => 'validated',
            'role_id' => DB::table('roles')->where('code', 'admin')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('email', 'consultnow@admin.com')->delete();
    }
};
