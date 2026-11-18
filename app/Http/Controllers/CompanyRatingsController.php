<?php

namespace App\Http\Controllers;

use App\Models\CompanyReview;
use Illuminate\Http\Request;

class CompanyRatingsController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user('web');
        $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'recent';
        $reviews = CompanyReview::where('company_user_id', (string) $company->getKey());
        $reviews = $sort === 'oldest'
            ? $reviews->oldest()->paginate(10)->withQueryString()
            : $reviews->latest()->paginate(10)->withQueryString();

        $total = CompanyReview::where('company_user_id', (string) $company->getKey())->count();
        $average = $total
            ? CompanyReview::where('company_user_id', (string) $company->getKey())->avg('rating')
            : null;

        return view('company.ratings', compact('company', 'reviews', 'total', 'average', 'sort'));
    }
}
