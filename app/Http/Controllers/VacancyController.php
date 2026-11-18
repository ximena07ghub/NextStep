<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_user_id' => 'required|string',
            'title' => 'required|string|max:255',
            'modality' => 'required|in:Remoto,Presencial,Híbrido', 
            'location' => 'required|string|max:255',
            'stay_duration' => 'required|in:3 meses,6 meses',
            'salary_fixed' => 'required|numeric|min:1', 
            'periodicity' => 'required|in:Por mes,Por quincena,Por semana', 
            'description' => 'required|string',
            'benefits' => 'required|array', 
        ]);

        $validated['status'] = 'published';
        
        $vacancy = Vacancy::create($validated);
        return response()->json($vacancy, 201);
    }

    public function index()
    {
        return response()->json(Vacancy::all(), 200);
    }

    public function update(Request $request, $id)
    {
        $vacancy = Vacancy::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'modality' => 'sometimes|required|in:Remoto,Presencial,Híbrido',
            'location' => 'sometimes|required|string|max:255',
            'stay_duration' => 'sometimes|required|in:3 meses,6 meses',
            'salary_fixed' => 'sometimes|required|numeric|min:1',
            'periodicity' => 'sometimes|required|in:Por mes,Por quincena,Por semana',
            'benefits' => 'sometimes|required|array',
            'status' => 'sometimes|required|string|in:published,closed'
        ]);

        $vacancy->update($validated);
        return response()->json($vacancy, 200);
    }

    public function destroy($id)
    {
        $vacancy = Vacancy::findOrFail($id);
        $vacancy->delete();
        return response()->json(['message' => 'Vacante eliminada'], 200);
    }
}