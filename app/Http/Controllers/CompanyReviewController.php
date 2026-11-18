<?php

namespace App\Http\Controllers;

use App\Models\CompanyReview;
use Illuminate\Http\Request;

class CompanyReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_user_id' => 'required|string',
            'student_user_id' => 'required|string',
            'job_title' => 'required|string',
            'job_location' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'review_title' => 'required|string',
            'review_description' => 'required|string',
            'employment_start_date' => 'required|date',
            'employment_end_date' => 'required|date',
        ]);

        $review = CompanyReview::create($validated);
        return response()->json($review, 201);
    }

    public function index()
    {
        return response()->json(CompanyReview::all(), 200);
    }

    public function update(Request $request, $id)
    {
        $review = CompanyReview::findOrFail($id);
        $validated = $request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'review_title' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'review_description' => ['sometimes', 'required', 'string', 'min:10', 'max:1500'],
        ]);
        $review->update($validated);
        return response()->json($review, 200);
    }

    public function destroy($id)
    {
        $review = CompanyReview::findOrFail($id);
        $review->delete();
        return response()->json(['message' => 'Valoración eliminada'], 200);
    }
}