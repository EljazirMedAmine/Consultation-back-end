<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssuranceRequest;
use App\Http\Requests\UpdateAssuranceRequest;
use App\Models\Assurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssuranceController extends Controller
{
    /**
     * Afficher la liste des assurances
     */
    /**
     * Afficher la liste des assurances avec DataTables
     */
    public function index(Request $request)
    {
        try {
            $query = Assurance::with(['vehicules', 'garanties']);

            $query = $this->applyFilters($query, $request);
            $table = \Yajra\DataTables\DataTables::of($query);
            $table->editColumn('compagnie', fn($assurance) => $assurance->compagnie)
                ->editColumn('reference', fn($assurance) => $assurance->reference)
                ->editColumn('type_couverture', fn($assurance) => $assurance->type_couverture)
                ->editColumn('type_assurance', fn($assurance) => $assurance->type_assurance)
                ->editColumn('date_debut', fn($assurance) => $assurance->date_debut->format('d/m/Y'))
                ->editColumn('date_fin', fn($assurance) => $assurance->date_fin->format('d/m/Y'))
                ->addColumn('statut', function($assurance) {
                    return $assurance->est_active ? 'Active' : 'Expirée';
                })
                ->addColumn('vehicules', function($assurance) {
                    return $assurance->vehicules->pluck('immatriculation')->implode(', ');
                })
                ->addColumn('jours_restants', function($assurance) {
                    if (!$assurance->est_active) return 'Expirée';
                    return now()->diffInDays($assurance->date_fin, false) . ' jours';
                })
                ->addColumn('actions', function($assurance) {
                    return $assurance->id;
                })
                ->filterColumn('compagnie', function($query, $keyword) {
                    $query->where('compagnie', 'like', "%{$keyword}%");
                })
                ->filterColumn('reference', function($query, $keyword) {
                    $query->where('reference', 'like', "%{$keyword}%");
                })
                ->filterColumn('type_couverture', function($query, $keyword) {
                    $query->where('type_couverture', 'like', "%{$keyword}%");
                })
                ->filterColumn('type_assurance', function($query, $keyword) {
                    $query->where('type_assurance', 'like', "%{$keyword}%");
                });

            return $table->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des assurances',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Applique les filtres à la requête.
     *
     * @param $query
     * @param Request $request
     * @return mixed
     */
    private function applyFilters($query, Request $request)
    {
        // Filtrer par compagnie
        if ($request->filled('compagnie')) {
            $query->where('compagnie', 'like', '%' . $request->input('compagnie') . '%');
        }

        // Filtrer par référence
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        // Filtrer par type d'assurance
        if ($request->filled('type_assurance')) {
            $query->where('type_assurance', $request->input('type_assurance'));
        }

        // Filtrer par type de couverture
        if ($request->filled('type_couverture')) {
            $query->where('type_couverture', $request->input('type_couverture'));
        }

        // Filtrer par statut (active, expirée)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expire();
            } elseif ($request->status === 'expiring_soon') {
                $query->expirantProchainement($request->input('jours', 30));
            }
        }

        // Filtrer par véhicule
        if ($request->filled('vehicule_id')) {
            $query->whereHas('vehicules', function($q) use ($request) {
                $q->where('vehicules.id', $request->vehicule_id);
            });
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('compagnie', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('type_couverture', 'like', "%{$search}%")
                    ->orWhere('type_assurance', 'like', "%{$search}%")
                    ->orWhereHas('vehicules', function($vehiculeQuery) use ($search) {
                        $vehiculeQuery->where('immatriculation', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
    /**
     * Créer une nouvelle assurance
     */
    public function store(StoreAssuranceRequest $request)
    {
        try {
            DB::beginTransaction();

            $assurance = Assurance::create($request->validated());

            if ($request->has('vehicules')) {
                $assurance->vehicules()->sync($request->input('vehicules'));
            }


            if ($request->has('garanties')) {
                $assurance->garanties()->sync($request->input('garanties'));
            }

            // Traiter les documents
            if ($request->has('documents')) {
                foreach ($request->input('documents', []) as $document) {
                    if (isset($document['path']) && Storage::exists($document['path'])) {
                        $assurance->addMediaFromDisk($document['path'])
                            ->usingName($document['name'] ?? null)
                            ->withCustomProperties([
                                'description' => $document['description'] ?? null,
                                'type' => $document['type'] ?? 'document'
                            ])
                            ->toMediaCollection('assurance_documents');
                        Storage::delete($document['path']);
                    }
                }
            }

            DB::commit();

            $assurance->load(['vehicules', 'garanties']);
            $assurance->documents = $assurance->getMedia('assurance_documents');

            return response()->json([
                'success' => true,
                'message' => 'Assurance créée avec succès',
                'data' => $assurance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la création de l\'assurance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une assurance
     */
    public function update(UpdateAssuranceRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $assurance = Assurance::findOrFail($id);
            $assurance->update($request->validated());
            if ($request->has('vehicules')) {
                $assurance->vehicules()->sync($request->input('vehicules'));
            }
            if ($request->has('garanties')) {
                $assurance->garanties()->sync($request->input('garanties'));
            }

            if ($request->has('delete_documents')) {
                foreach ($request->input('delete_documents', []) as $mediaId) {
                    $media = $assurance->media()->where('id', $mediaId)->first();
                    if ($media) {
                        $media->delete();
                    }
                }
            }
            if ($request->has('documents')) {
                foreach ($request->input('documents', []) as $document) {
                    if (isset($document['path']) && Storage::exists($document['path'])) {
                        $assurance->addMediaFromDisk($document['path'])
                            ->usingName($document['name'] ?? null)
                            ->withCustomProperties([
                                'description' => $document['description'] ?? null,
                                'type' => $document['type'] ?? 'document'
                            ])
                            ->toMediaCollection('assurance_documents');

                        Storage::delete($document['path']);
                    }
                }
            }

            DB::commit();
            $assurance->load(['vehicules', 'garanties']);
            $assurance->documents = $assurance->getMedia('assurance_documents');

            return response()->json([
                'success' => true,
                'message' => 'Assurance mise à jour avec succès',
                'data' => $assurance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la mise à jour de l\'assurance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une assurance spécifique
     */
    public function show($id)
    {
        try {
            $assurance = Assurance::with(['vehicules.media', 'garanties'])->findOrFail($id);

            // Récupérer les médias
            $assurance->documents = $assurance->getMedia('assurance_documents');

            return response()->json([
                'success' => true,
                'data' => $assurance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assurance non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    /**
     * Supprimer une assurance
     */
    public function destroy($id)
    {
        try {
            $assurance = Assurance::findOrFail($id);
            $assurance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assurance supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la suppression de l\'assurance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les assurances arrivant à expiration
     */
    public function getExpiringAssurances(Request $request)
    {
        try {
            $jours = $request->input('jours', 30);
            $assurances = Assurance::with(['vehicules'])
                ->expirantProchainement($jours)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assurances,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des assurances',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
