<?php

use App\Models\Application;
use App\Models\Bookmark;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // nunca ejecuta borrados sin una base de pruebas explícita
    $testUri = env('TEST_MONGODB_URI');
    $normalUri = env('DB_URI', 'mongodb://127.0.0.1:27017');

    if (app()->environment() !== 'testing' || ! $testUri || $testUri === $normalUri) {
        throw new \RuntimeException('Configura TEST_MONGODB_URI distinta de DB_URI antes de ejecutar estas pruebas.');
    }

    config([
        'database.default' => 'mongodb',
        'database.connections.mongodb.dsn' => $testUri,
        'database.connections.mongodb.database' => 'nextstep_admin_test',
    ]);
    DB::purge('mongodb');

    if (config('database.connections.mongodb.database') !== 'nextstep_admin_test') {
        throw new \RuntimeException('Base de pruebas incorrecta.');
    }

    DB::connection('mongodb')->getDatabase()->drop();
    $this->withoutVite();
    $this->admin = User::factory()->create(['first_name' => 'Administradora', 'role' => 'admin']);
});

afterEach(function () {
    if (app()->environment() === 'testing' && config('database.connections.mongodb.database') === 'nextstep_admin_test') {
        DB::connection('mongodb')->getDatabase()->drop();
    }
});

test('las rutas administrativas requieren el rol correcto', function () {
    $this->getJson('/api/admin/students')->assertUnauthorized();
    $student = User::factory()->create(['role' => 'student']);
    $this->actingAs($student, 'web')->getJson('/api/admin/students')->assertForbidden();
    $this->actingAs($this->admin, 'web')->getJson('/api/admin/students')->assertOk();
});

test('los documentos privados solo se descargan con autorización', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/test.pdf', '%PDF-test');
    $student = User::factory()->create(['role' => 'student', 'cv_path' => 'cvs/test.pdf', 'cv_name' => 'CV.pdf']);
    $id = (string) $student->getKey();

    $this->get('/admin/students/'.$id.'/cv')->assertRedirect(route('login'));
    $this->actingAs($student, 'web')->get('/admin/students/'.$id.'/cv')->assertForbidden();
    $this->actingAs($this->admin, 'web')->get('/admin/students/'.$id.'/cv')->assertDownload('CV.pdf');
});

test('el rfc de una empresa no se puede modificar', function () {
    $company = User::factory()->create(['role' => 'company', 'company_name' => 'Empresa', 'rfc' => 'ABC240101XYZ']);
    $this->actingAs($this->admin, 'web')->put('/admin/companies/'.$company->getKey(), [
        'company_name' => 'Empresa nueva',
        'rfc' => 'OTRO240101XYZ',
    ])->assertRedirect();

    expect($company->fresh()->rfc)->toBe('ABC240101XYZ');
    expect($company->fresh()->company_name)->toBe('Empresa nueva');
});

test('la api elimina los registros relacionados de estudiantes', function () {
    Storage::fake('local');
    Storage::disk('local')->put('cvs/test.pdf', '%PDF-test');
    $student = User::factory()->create(['role' => 'student', 'cv_path' => 'cvs/test.pdf']);
    $id = (string) $student->getKey();
    StudentProfile::create(['user_id' => $id]);
    Bookmark::create(['student_user_id' => $id, 'vacancy_id' => 'test']);
    Application::create(['student_user_id' => $id, 'full_name' => 'Estudiante', 'status' => 'pending']);

    $this->actingAs($this->admin, 'web')->deleteJson('/api/users/'.$id)->assertOk();
    expect(User::find($id))->toBeNull();
    expect(StudentProfile::where('user_id', $id)->count())->toBe(0);
    expect(Bookmark::where('student_user_id', $id)->count())->toBe(0);
    expect(Application::where('student_user_id', $id)->count())->toBe(0);
    expect(Storage::disk('local')->exists('cvs/test.pdf'))->toBeFalse();
});

test('la api no permite eliminar administradores', function () {
    $this->actingAs($this->admin, 'web')->deleteJson('/api/users/'.$this->admin->getKey())->assertForbidden();
    expect($this->admin->fresh())->not->toBeNull();
});

test('el comando crea un administrador sin registro público', function () {
    $this->artisan('nextstep:create-admin', ['email' => 'admin@example.test'])
        ->expectsQuestion('Nombre del administrador', 'Administradora')
        ->expectsQuestion('Contraseña (8 a 72 caracteres)', 'AdminTest123!')
        ->expectsQuestion('Repite la contraseña', 'AdminTest123!')
        ->assertSuccessful();

    expect(User::where('email', 'admin@example.test')->first()->role)->toBe('admin');
});
