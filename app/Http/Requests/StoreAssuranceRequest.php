<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssuranceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'assurance_compagnie_id' => 'required|exists:assurance_compagnies,id',
            'reference' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'type_couverture' => 'required|in:responsabilite_civile,tiers,tous_risques',
            'type_assurance' => 'required|in:individuelle,flotte',
            'is_international' => 'boolean',
            'vehicules' => 'array',
            'vehicules.*' => 'exists:vehicules,id',
            'garanties' => 'array',
            'garanties.*' => 'exists:garanties,id',
            'documents' => 'nullable|array',
            'documents.*.path' => 'required_with:documents|string',
            'documents.*.name' => 'nullable|string',
            'documents.*.description' => 'nullable|string',
            'documents.*.type' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'assurance_compagnie_id.required' => 'La compagnie d\'assurance est obligatoire.',
            'assurance_compagnie_id.exists' => 'La compagnie d\'assurance sélectionnée n\'existe pas.',
            'reference.required' => 'La référence du contrat est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'type_couverture.required' => 'Le type de couverture est obligatoire.',
            'type_couverture.in' => 'Le type de couverture doit être: responsabilité civile, tiers ou tous risques.',
            'type_assurance.required' => 'Le type d\'assurance est obligatoire.',
            'type_assurance.in' => 'Le type d\'assurance doit être: individuelle ou flotte.',
            'vehicules.*.exists' => 'Un ou plusieurs véhicules sélectionnés n\'existent pas.',
            'garanties.*.exists' => 'Une ou plusieurs garanties sélectionnées n\'existent pas.',
            'documents.array' => 'Les documents doivent être fournis sous forme de tableau.',
            'documents.*.path.required_with' => 'Le chemin du document est obligatoire.',
        ];
    }
}
