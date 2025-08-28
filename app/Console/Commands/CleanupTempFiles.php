<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTempFiles extends Command
{
    /**
     * La signature de la commande console.
     *
     * @var string
     */
    protected $signature = 'cleanup:temp-files {--hours=24 : Nombre d\'heures avant suppression}';

    /**
     * La description de la commande console.
     *
     * @var string
     */
    protected $description = 'Nettoie les fichiers temporaires plus anciens que la durée spécifiée';

    /**
     * Exécute la commande console.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Recherche des fichiers temporaires de plus de {$hours} heures...");

        $tempFolder = 'temp';
        if (!Storage::exists($tempFolder)) {
            $this->info("Le dossier temporaire n'existe pas. Création...");
            Storage::makeDirectory($tempFolder);
            $this->info("Dossier temporaire créé à : " . storage_path('app/'.$tempFolder));
        } else {
            $this->info("Dossier temporaire trouvé à : " . storage_path('app/'.$tempFolder));
        }

        // Liste des dossiers dans temp (collections)
        $directories = Storage::directories($tempFolder);
        $directories[] = $tempFolder;

        $count = 0;
        $totalSize = 0;

        foreach ($directories as $directory) {
            $files = Storage::files($directory);

            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp(Storage::lastModified($file));
                $hoursOld = $lastModified->diffInHours(now());

                if ($hoursOld >= $hours) {
                    try {
                        $size = Storage::size($file);
                        $totalSize += $size;
                        Storage::delete($file);
                        $count++;
                        $this->line("Supprimé: {$file} ({$hoursOld} heures, " . round($size / 1024, 2) . " KB)");
                    } catch (\Exception $e) {
                        $this->error("Erreur lors de la suppression de {$file}: {$e->getMessage()}");
                    }
                }
            }
        }

        if ($count == 0) {
            $this->info("Aucun fichier temporaire ancien trouvé.");
        } else {
            $totalSizeMB = round($totalSize / (1024 * 1024), 2);
            $this->info("Nettoyage terminé. {$count} fichiers supprimés ({$totalSizeMB} MB libérés).");
        }

        // Afficher le disque de stockage utilisé
        $this->info("Disque de stockage utilisé : " . config('filesystems.default'));
    }
}
