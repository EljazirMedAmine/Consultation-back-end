<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Assurance extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'assurance_compagnie_id',
        'reference',
        'date_debut',
        'date_fin',
        'type_couverture',
        'type_assurance',
        'is_international',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_international' => 'boolean',
    ];

    /**
     * Relation avec la compagnie d'assurance
     */
    public function assuranceCompagnie()
    {
        return $this->belongsTo(AssuranceCompagnie::class);
    }

    public function vehicules()
    {
        return $this->belongsToMany(Vehicule::class, 'assurance_vehicule');
    }

    public function garanties()
    {
        return $this->belongsToMany(Garantie::class, 'assurance_garantie');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('date_fin', '>=', now());
    }

    public function scopeExpire($query)
    {
        return $query->where('date_fin', '<', now());
    }

    public function scopeExpirantProchainement($query, $jours = 30)
    {
        return $query->whereBetween('date_fin', [now(), now()->addDays($jours)]);
    }

    // Accessors
    public function getEstActiveAttribute()
    {
        return $this->date_fin >= now();
    }

    public function getEstExpireAttribute()
    {
        return $this->date_fin < now();
    }
}
