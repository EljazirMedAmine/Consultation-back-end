<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up()
    {
        if (!Schema::hasColumn('doctors', 'consultation_fee')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->decimal('consultation_fee', 8, 2)->nullable()->after('hospital_affiliation');
            });
        }
    }

    /**
     * Annule les migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('doctors', 'consultation_fee')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('consultation_fee');
            });
        }
    }
};
