<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Bookmark;
use App\Models\CompanyProfileView;
use App\Models\CompanyReview;
use App\Models\StudentProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Storage;

class AccountDeletionService
{
    // elimina una cuenta y sus registros asociados
    public function delete(User $user): void
    {
        $id = (string) $user->getKey();

        if ($user->role === 'student') {
            $profile = StudentProfile::where('user_id', $id)->first();
            Application::where('student_user_id', $id)->delete();
            Bookmark::where('student_user_id', $id)->delete();
            CompanyReview::where('student_user_id', $id)->delete();
            CompanyProfileView::where('student_user_id', $id)->delete();
            StudentProfile::where('user_id', $id)->delete();
            $user->delete();

            foreach (array_filter([$user->cv_path, $profile?->kardex_path]) as $path) {
                Storage::disk('local')->delete($path);
            }
            return;
        }

        if ($user->role === 'company') {
            $ids = Vacancy::where('company_user_id', $id)->get()
                ->map(fn ($vacancy) => (string) $vacancy->getKey())->all();
            Bookmark::whereIn('vacancy_id', $ids)->delete();
            Application::where('company_user_id', $id)->delete();
            Vacancy::where('company_user_id', $id)->delete();
            CompanyReview::where('company_user_id', $id)->delete();
            CompanyProfileView::where('company_user_id', $id)->delete();
            Subscription::where('company_user_id', $id)->delete();
            $user->delete();

            if ($user->logo_path) {
                Storage::disk('public')->delete($user->logo_path);
            }
            return;
        }

        abort(403, 'No se permite eliminar administradores.');
    }

    public function deleteStudent(string $id): void
    {
        $this->delete(User::where('role', 'student')->findOrFail($id));
    }

    public function deleteCompany(string $id): void
    {
        $this->delete(User::where('role', 'company')->findOrFail($id));
    }
}
