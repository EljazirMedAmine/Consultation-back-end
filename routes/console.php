<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cleanup:temp-files')
    ->daily()
    ->description('Nettoie les fichiers temporaires de plus de 24 heures');

// Si vous souhaitez une exécution à une heure spécifique
// Schedule::command('cleanup:temp-files')->dailyAt('02:00');
