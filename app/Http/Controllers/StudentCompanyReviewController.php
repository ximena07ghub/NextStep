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
            'review_title' => ['required', 'string', 'min:3', 'max:120'],
        ], [
            'rating.required' => 'Selecciona una calificación.',
            'rating.between' => 'La calificación debe ser de 1 a 5 estrellas.',
            'review_title.required' => 'Escribe un título para tu valoración.',
            'review_title.min' => 'El título debe tener al menos 3 caracteres.',
            'review_title.max' => 'El título no debe superar los 120 caracteres.',
        ]);

        // solo una valoración por estudiante y empresa
        if (CompanyReview::where('company_user_id', $companyId)->where('student_user_id', $studentId)->exists()) {
            return back()->withErrors(['review' => 'Ya calificaste a esta empresa.']);
        }

        CompanyReview::create([
            'company_user_id' => $companyId,
            'student_user_id' => $studentId,
            'rating' => (int) $data['rating'],
            'review_title' => $data['review_title'],
        ]);

        return redirect()->route('student.companies.show', $companyId)->with('success', 'Tu valoración se publicó correctamente.');
    }
}
