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

test('un pago confirmado activa una vez y valida la empresa, plan e importe', function () {
    $company = User::factory()->create(['role' => 'company']);
    $reference = 'ORDER654321';
    $purchase = Subscription::create([
        'company_user_id' => (string) $company->getKey(),
        'membership_id' => '507f191e810c19729de860ea',
        'reference' => $reference,
        'payment_method' => 'paypal',
        'payment_status' => 'pending',
        'status' => 'pending',
        'amount' => 100,
        'currency' => 'MXN',
    ]);
    $order = [
        'id' => $reference,
        'status' => 'COMPLETED',
        'purchase_units' => [[
            'custom_id' => (string) $company->getKey(),
            'reference_id' => $purchase->membership_id,
            'payments' => ['captures' => [[
                'id' => 'CAPTURE123',
                'status' => 'COMPLETED',
                'amount' => ['currency_code' => 'MXN', 'value' => '100.00'],
            ]]],
        ]],
    ];
    $paypal = Mockery::mock(PayPalService::class);
    $paypal->shouldReceive('getOrder')->once()->with($reference)->andReturn($order);
    $paypal->shouldNotReceive('captureOrder');
    $this->app->instance(PayPalService::class, $paypal);
    $url = route('company.membership.paypal.return', ['token' => $reference]);
    $this->actingAs($company, 'web')->get($url)->assertRedirect(route('company.membership'));
    expect($purchase->fresh()->payment_status)->toBe('paid');
    $startedAt = $purchase->fresh()->started_at;
    $this->get($url)->assertRedirect(route('company.membership'));
    expect($purchase->fresh()->started_at->equalTo($startedAt))->toBeTrue();
});

test('el pago de otra empresa o con importe incorrecto no activa la membresía', function () {
    $company = User::factory()->create(['role' => 'company']);
    $purchase = Subscription::create([
        'company_user_id' => (string) $company->getKey(),
        'membership_id' => '507f191e810c19729de860ea',
        'reference' => 'ORDER888888',
        'payment_method' => 'paypal',
        'payment_status' => 'pending',
        'status' => 'pending',
        'amount' => 100,
        'currency' => 'MXN',
    ]);
    $paypal = Mockery::mock(PayPalService::class);
    $paypal->shouldReceive('getOrder')->once()->andReturn([
        'id' => 'ORDER888888',
        'status' => 'COMPLETED',
        'purchase_units' => [[
            'custom_id' => 'incorrect-company',
            'reference_id' => $purchase->membership_id,
            'payments' => ['captures' => [[
                'id' => 'CAPTURE888',
                'status' => 'COMPLETED',
                'amount' => ['currency_code' => 'MXN', 'value' => '99.00'],
            ]]],
        ]],
    ]);
    $paypal->shouldNotReceive('captureOrder');
    $this->app->instance(PayPalService::class, $paypal);
    $this->actingAs($company, 'web')
        ->get(route('company.membership.paypal.return', ['token' => 'ORDER888888']))
        ->assertRedirect(route('company.membership.change'));
    expect($purchase->fresh()->payment_status)->toBe('pending');
});

test('un plan ilimitado admite más de una vacante', function () {
    $company = User::factory()->create(['role' => 'company']);
    regressionSubscription($company, null);
    $this->actingAs($company, 'web')->post(route('company.vacancies.store'), regressionVacancyFields())->assertRedirect();
    $this->post(route('company.vacancies.store'), regressionVacancyFields())->assertRedirect();
    expect(Vacancy::where('company_user_id', (string) $company->getKey())->where('status', 'published')->count())->toBe(2);
});

test('cada plan respeta su cupo configurado en la compra', function (int $limit) {
    $company = User::factory()->create(['role' => 'company']);
    regressionSubscription($company, $limit);
    $this->actingAs($company, 'web');

    for ($i = 0; $i < $limit; $i++) {
        $this->post(route('company.vacancies.store'), regressionVacancyFields())->assertRedirect();
    }
    expect(Vacancy::where('company_user_id', (string) $company->getKey())->where('status', 'published')->count())->toBe($limit);
    $this->post(route('company.vacancies.store'), regressionVacancyFields())
        ->assertRedirect(route('company.membership'));
})->with([1, 2, 3]);

test('la renovación de una vacante cerrada requiere vigencia y cupo', function () {
    $company = User::factory()->create(['role' => 'company']);
    regressionSubscription($company, 1);
    $vacancy = Vacancy::create(regressionVacancyFields() + [
        'company_user_id' => (string) $company->getKey(),
        'status' => 'closed',
        'published_at' => now()->subMonths(4),
    ]);
    $this->actingAs($company, 'web')
        ->post(route('company.vacancies.renew', (string) $vacancy->getKey()))
        ->assertRedirect();
    expect($vacancy->fresh()->status)->toBe('published');

    $vacancy->update(['status' => 'closed']);
    Subscription::where('company_user_id', (string) $company->getKey())
        ->update(['ended_at' => now()->subDay()]);
    $this->post(route('company.vacancies.renew', (string) $vacancy->getKey()))
        ->assertRedirect(route('company.membership'));
    expect($vacancy->fresh()->status)->toBe('closed');
});
