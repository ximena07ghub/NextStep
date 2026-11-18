<?php
namespace App\Http\Controllers;

use App\Models\CompanyReview;
use App\Models\User;
use Illuminate\Http\Request;

class StudentCompanyReviewController extends Controller
{
    public function store(Request $request, string $id)
    {
        $company = User::where('role', 'company')->where('_id', $id)->firstOrFail();
        $studentId = (string) $request->user('web')->getKey();
        $companyId = (string) $company->getKey();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review_description' => ['required', 'string', 'min:10', 'max:1500'],
        ], [
            'rating.required' => 'Selecciona una calificación.',
            'rating.between' => 'La calificación debe ser de 1 a 5 estrellas.',
            'review_description.required' => 'Escribe tu experiencia.',
            'review_description.min' => 'Tu experiencia debe tener al menos 10 caracteres.',
            'review_description.max' => 'Tu experiencia no debe superar los 1500 caracteres.',
        ]);

        // solo una valoración por estudiante y empresa
        if (CompanyReview::where('company_user_id', $companyId)->where('student_user_id', $studentId)->exists()) {
            return back()->withErrors(['review' => 'Ya calificaste a esta empresa.']);
        }

        CompanyReview::create([
            'company_user_id' => $companyId,
            'student_user_id' => $studentId,
            'rating' => (int) $data['rating'],
            'review_description' => $data['review_description'],
        ]);

        return redirect()->route('student.companies.show', $companyId)->with('success', 'Tu valoración se publicó correctamente.');
    }
}
