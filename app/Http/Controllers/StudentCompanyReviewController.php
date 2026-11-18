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
            'review_title' => ['required', 'string', 'min:3', 'max:100'],
            'review_description' => ['required', 'string', 'min:10', 'max:1500'],
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
            'review_description' => $data['review_description'],
        ]);

        return redirect()->route('student.companies.show', $companyId)->with('success', 'Tu valoración se publicó correctamente.');
    }
}
