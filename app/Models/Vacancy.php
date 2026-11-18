<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Vacancy extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'vacancies';

    protected $fillable = [
        'company_user_id', 'title', 'modality', 'location',
        'stay_duration', 'salary_fixed', 'periodicity',
        'description', 'benefits', 'status', 'published_at', 'expires_at',
    ];

    protected $casts = [
        'benefits' => 'array',
        'salary_fixed' => 'integer',
    ];

    // cierra vacantes vencidas
    public static function closeExpired(): void
    {
        static::where('status', 'published')->get()->each(function (self $vacancy) {
            $start = $vacancy->published_at ?? $vacancy->created_at;
            if (! $start) return;
            $months = $vacancy->stay_duration === '6 meses' ? 6 : 3;
            $expires = $vacancy->expires_at
                ? \Carbon\Carbon::parse($vacancy->expires_at)
                : \Carbon\Carbon::parse($start)->addMonthsNoOverflow($months);
            if ($expires->isPast()) {
                $vacancy->update(['status' => 'closed', 'expires_at' => $expires]);
            }
        });
    }

    public function card(User $company): array
    {
        return [
            'id' => (string) $this->getKey(),
            'companyId' => (string) $company->getKey(),
            'title' => $this->title,
            'company' => $company->company_name,
            'location' => $this->location,
            'salary' => '$'.number_format((int) $this->salary_fixed).' - '.$this->periodicity,
            'salaryFixed' => (int) $this->salary_fixed,
            'periodicity' => $this->periodicity,
            'sector' => $company->sector,
            'createdAt' => $this->created_at?->toIso8601String(),
            'modality' => $this->modality,
            'stayDuration' => $this->stay_duration,
            'jobType' => 'Práctica',
            'benefits' => $this->benefits ?? [],
            'description' => $this->description,
        ];
    }
}
