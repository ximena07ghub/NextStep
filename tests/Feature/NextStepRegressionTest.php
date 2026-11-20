<?php

use App\Models\Application;
use App\Models\CompanyReview;
use App\Models\Membership;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\PayPalService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Nunca borrar ni reutilizar la base normal: exigir URI de pruebas diferente.
    $testUri = env('TEST_MONGODB_URI');
    $normalUri = env('DB_URI', 'mongodb://127.0.0.1:27017');
    if (app()->environment() !== 'testing' || ! $testUri || $testUri === $normalUri) {
        throw new RuntimeException('Configura TEST_MONGODB_URI distinta de DB_URI.');
    }
    config([
        'database.default' => 'mongodb',
        'database.connections.mongodb.dsn' => $testUri,
        'database.connections.mongodb.database' => 'nextstep_regression_test',
    ]);
    DB::purge('mongodb');
    DB::connection('mongodb')->getDatabase()->drop();
    $this->withoutVite();
});

afterEach(function () {
    if (app()->environment() === 'testing' && config('database.connections.mongodb.database') === 'nextstep_regression_test') {
        DB::connection('mongodb')->getDatabase()->drop();
    }
});

function regressionVacancyFields(): array
{
    return [
        'title' => 'Prácticas de desarrollo',
        'modality' => 'Remoto',
        'location' => 'Monterrey',
        'stay_duration' => '3 meses',
        'salary_fixed' => 5000,
        'periodicity' => 'Por mes',
        'description' => 'Participación en proyectos',
        'benefits' => ['Aprendizaje'],
    ];
}

function regressionSubscription(User $company, ?int $limit, array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'company_user_id' => (string) $company->getKey(),
        'membership_id' => '507f191e810c19729de860ea',
        'membership_name' => 'Plan prueba',
        'membership_vacancy_limit' => $limit,
        'payment_status' => 'paid',
        'status' => 'active',
        'amount' => 100,
        'currency' => 'MXN',
        'started_at' => now()->subDay(),
        'ended_at' => now()->addDays(25),
    ], $overrides));
}

test('las pantallas de pagos y membresías son exclusivas de administración', function () {
    $this->get('/admin/payments')->assertRedirect(route('login'));
    $student = User::factory()->create(['role' => 'student']);
    $this->actingAs($student, 'web')->get('/admin/memberships')->assertForbidden();

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin, 'web')->get('/admin/payments')->assertOk();
    $this->actingAs($admin, 'web')->get('/admin/memberships')->assertOk();
});

test('una orden pendiente de paypal no se captura ni activa sin aprobación', function () {
    $company = User::factory()->create(['role' => 'company']);
    $purchase = Subscription::create([
        'company_user_id' => (string) $company->getKey(),
        'membership_id' => '507f191e810c19729de860ea',
        'reference' => 'ORDER123456',
        'payment_method' => 'paypal',
        'payment_status' => 'pending',
        'status' => 'pending',
        'amount' => 100,
        'currency' => 'MXN',
    ]);
    $paypal = Mockery::mock(PayPalService::class);
    $paypal->shouldReceive('getOrder')->once()->with('ORDER123456')
        ->andReturn(['id' => 'ORDER123456', 'status' => 'CREATED']);
    $paypal->shouldNotReceive('captureOrder');
    $this->app->instance(PayPalService::class, $paypal);

    $this->actingAs($company, 'web')
        ->get(route('company.membership.paypal.return', ['token' => 'ORDER123456']))
        ->assertRedirect(route('company.membership.change'));
    expect($purchase->fresh()->payment_status)->toBe('pending');
});

test('se respeta el límite de vacantes y se permite publicar tras liberar cupo', function () {
    $company = User::factory()->create(['role' => 'company']);
    regressionSubscription($company, 1);
    $this->actingAs($company, 'web')->post(route('company.vacancies.store'), regressionVacancyFields())
        ->assertRedirect();
    expect(Vacancy::where('company_user_id', (string) $company->getKey())->where('status', 'published')->count())->toBe(1);

    $this->post(route('company.vacancies.store'), regressionVacancyFields())->assertRedirect(route('company.membership'));
    $vacancy = Vacancy::where('company_user_id', (string) $company->getKey())->firstOrFail();
    $this->delete(route('company.vacancies.destroy', $vacancy->getKey()))->assertRedirect();
    $this->post(route('company.vacancies.store'), regressionVacancyFields())->assertRedirect();
    expect(Vacancy::where('company_user_id', (string) $company->getKey())->where('status', 'published')->count())->toBe(1);
});

test('una membresía vencida impide publicar', function () {
    $company = User::factory()->create(['role' => 'company']);
    regressionSubscription($company, 3, ['started_at' => now()->subMonths(2), 'ended_at' => now()->subMonth()]);
    $this->actingAs($company, 'web')->post(route('company.vacancies.store'), regressionVacancyFields())
        ->assertRedirect(route('company.membership'));
    expect(Vacancy::count())->toBe(0);
});

test('una valoración por estudiante y empresa y sin duplicados', function () {
    $company = User::factory()->create(['role' => 'company']);
    $student = User::factory()->create(['role' => 'student']);
    $url = route('student.companies.reviews.store', (string) $company->getKey());
    $this->actingAs($student, 'web')->post($url, ['rating' => 5, 'review_title' => 'Buena experiencia'])->assertRedirect();
    $this->post($url, ['rating' => 4, 'review_title' => 'Otra valoración'])->assertSessionHasErrors('review');
    expect(CompanyReview::where('company_user_id', (string) $company->getKey())->count())->toBe(1);
});

test('un estudiante no puede postularse dos veces a la misma vacante', function () {
    $company = User::factory()->create(['role' => 'company']);
    $student = User::factory()->create(['role' => 'student']);
    $vacancy = Vacancy::create(regressionVacancyFields() + [
        'company_user_id' => (string) $company->getKey(),
        'status' => 'published',
        'published_at' => now(),
        'expires_at' => now()->addMonth(),
    ]);
    $url = route('student.applications.store', (string) $vacancy->getKey());
    $payload = ['full_name' => 'Estudiante Prueba', 'email' => 'test@example.com', 'phone' => '8112345678', 'location' => 'Monterrey'];
    $this->actingAs($student, 'web')->post($url, $payload)->assertRedirect();
    $this->post($url, $payload)->assertRedirect();
    expect(Application::where('student_user_id', (string) $student->getKey())->count())->toBe(1);
});
