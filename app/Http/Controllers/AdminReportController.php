<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CompanyReview;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        Vacancy::closeExpired();
        $filters = $request->validate([
            'report' => ['nullable', 'in:applications,students,vacancies,reviews'],
            'status' => ['nullable', 'in:pending,accepted,rejected,published,closed'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $report = $filters['report'] ?? 'applications';
        $status = $filters['status'] ?? '';
        $search = mb_strtolower(trim($filters['q'] ?? ''));

        // conserva solo los estados que corresponden al reporte
        $allowed = in_array($report, ['applications', 'students'])
            ? ['pending', 'accepted', 'rejected']
            : ['published', 'closed'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $companies = User::where('role', 'company')->get()
            ->keyBy(fn ($user) => (string) $user->getKey());
        $vacancies = Vacancy::all();
        $vacancyMap = $vacancies->keyBy(fn ($vacancy) => (string) $vacancy->getKey());
        $applications = Application::all();
        $reviews = CompanyReview::all();

        $summary = [
            'applications' => $applications->count(),
            'vacancies' => $vacancies->count(),
            'companies' => $companies->count(),
            'reviews' => $reviews->count(),
        ];

        $matches = function (array $values) use ($search): bool {
            if ($search === '') {
                return true;
            }
            foreach ($values as $value) {
                if (str_contains(mb_strtolower((string) $value), $search)) {
                    return true;
                }
            }
            return false;
        };

        $rows = collect();

        if ($report === 'applications') {
            // relaciona las postulaciones con sus vacantes y empresas
            $counts = $applications->groupBy('vacancy_id');
            $rows = $vacancies->map(function ($vacancy) use ($counts, $companies) {
                $records = $counts->get((string) $vacancy->getKey(), collect());
                return [
                    'vacancy' => $vacancy->title,
                    'company' => $companies->get((string) $vacancy->company_user_id)?->company_name ?? 'Empresa no disponible',
                    'pending' => $records->where('status', 'pending')->count(),
                    'accepted' => $records->where('status', 'accepted')->count(),
                    'rejected' => $records->where('status', 'rejected')->count(),
                    'total' => $records->count(),
                ];
            })->filter(fn ($row) => ($status === '' || $row[$status] > 0)
                && $matches([$row['vacancy'], $row['company']]))->values();
        } elseif ($report === 'students') {
            // relaciona usuarios estudiantes, postulaciones y vacantes
            $studentIds = $applications->pluck('student_user_id')->filter()->unique()->all();
            $students = User::where('role', 'student')->whereIn('_id', $studentIds)
                ->get()->keyBy(fn ($user) => (string) $user->getKey());
            $rows = $applications->map(function ($application) use ($students, $vacancyMap, $companies) {
                $student = $students->get((string) $application->student_user_id);
                $vacancy = $vacancyMap->get((string) $application->vacancy_id);
                return [
                    'student' => $student
                        ? trim($student->first_name.' '.$student->paternal_last_name.' '.$student->maternal_last_name)
                        : ($application->full_name ?: 'Estudiante no disponible'),
                    'vacancy' => $vacancy?->title ?? 'Vacante no disponible',
                    'company' => $vacancy
                        ? ($companies->get((string) $vacancy->company_user_id)?->company_name ?? 'Empresa no disponible')
                        : 'Empresa no disponible',
                    'status' => $application->status,
                ];
            })->filter(fn ($row) => ($status === '' || $row['status'] === $status)
                && $matches([$row['student'], $row['vacancy'], $row['company']]))->values();
        } elseif ($report === 'vacancies') {
            // relaciona vacantes con empresas y filtra por estado
            $rows = $vacancies->map(fn ($vacancy) => [
                'company' => $companies->get((string) $vacancy->company_user_id)?->company_name ?? 'Empresa no disponible',
                'vacancy' => $vacancy->title,
                'status' => $vacancy->status,
            ])->filter(fn ($row) => ($status === '' || $row['status'] === $status)
                && $matches([$row['company'], $row['vacancy']]))->values();
        } else {
            // relaciona valoraciones con empresas y sus vacantes
            $groupedReviews = $reviews->groupBy('company_user_id');
            $groupedVacancies = $vacancies->groupBy('company_user_id');
            $rows = $companies->map(function ($company) use ($groupedReviews, $groupedVacancies) {
                $id = (string) $company->getKey();
                $companyReviews = $groupedReviews->get($id, collect());
                $companyVacancies = $groupedVacancies->get($id, collect());
                return [
                    'company' => $company->company_name,
                    'reviews' => $companyReviews->count(),
                    'average' => $companyReviews->count() ? round($companyReviews->avg('rating'), 1) : null,
                    'published' => $companyVacancies->where('status', 'published')->count(),
                    'closed' => $companyVacancies->where('status', 'closed')->count(),
                ];
            })->filter(fn ($row) => ($status === '' || $row[$status] > 0)
                && $matches([$row['company']]))->values();
        }

        return view('admin.reports.index', compact('report', 'status', 'search', 'summary', 'rows'));
    }
}
