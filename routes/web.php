<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CompanyMembershipController;
use App\Http\Controllers\CompanyRatingsController;
use App\Http\Controllers\CompanyVacancyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentVacancyController;
use Illuminate\Support\Facades\Route;

// ------- paginas publicas -------

// pagina de inicio
Route::view('/', 'home')->name('home');

// rutas para personas sin sesion
Route::middleware('guest:web')->group(function () {
    Route::view('/login', 'auth.login')->name('login');

    Route::view('/registro', 'auth.register')->name('register');

    // recibe los datos para iniciar sesion
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1,login')
        ->name('login.store');

    // registra a un estudiante
    Route::post(
        '/registro/estudiante',
        [AuthController::class, 'registerStudent']
    )
        ->middleware('throttle:10,1,register-student')
        ->name('register.student');

    // registra a una empresa
    Route::post(
        '/registro/empresa',
        [AuthController::class, 'registerCompany']
    )
        ->middleware('throttle:10,1,register-company')
        ->name('register.company');
});

// cierra la sesion del usuario
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:web')
    ->name('logout');

// ------- rutas del estudiante -------

// estas rutas solo las puede usar un estudiante con sesion
Route::prefix('student')
    ->name('student.')
    ->middleware(['auth:web', 'role:student'])
    ->group(function () {

        // inicio del estudiante y vacantes disponibles
        Route::get('/', [StudentVacancyController::class, 'index'])
            ->name('home');

        // pagina de vacantes guardadas
        Route::get('/bookmarks', [StudentVacancyController::class, 'bookmarks'])
            ->name('bookmarks');

        // ------- perfil del estudiante -------

        // muestra el perfil
        Route::get('/profile', [ProfileController::class, 'student'])
            ->name('profile');

        // guarda los cambios del perfil
        Route::post('/profile', [ProfileController::class, 'updateStudent'])
            ->name('profile.update');

        // permite consultar el cv
        Route::get('/profile/cv', [ProfileController::class, 'cv'])
            ->name('profile.cv');

        // permite consultar el kardex
        Route::get('/profile/kardex', [ProfileController::class, 'kardex'])
            ->name('profile.kardex');

        // ------- vacantes y postulaciones -------

        // muestra el detalle de una vacante
        Route::get('/vacancies/{id}', [StudentVacancyController::class, 'show'])
            ->name('vacancies.show');

        // recibe la postulacion desde la pagina
        Route::post(
            '/vacancies/{id}/applications',
            [ApplicationController::class, 'storeStudent']
        )->name('applications.store');

        // guarda una vacante
        Route::post(
            '/vacancies/{id}/bookmark',
            [BookmarkController::class, 'store']
        )->name('bookmarks.store');

        // quita una vacante de guardadas
        Route::delete(
            '/vacancies/{id}/bookmark',
            [BookmarkController::class, 'destroy']
        )->name('bookmarks.destroy');

        // muestra el perfil publico de una empresa
        Route::get('/companies/{id}', [StudentVacancyController::class, 'company'])
            ->name('companies.show');
    });

// ------- rutas de la empresa -------

// estas rutas solo las puede usar una empresa con sesion
Route::prefix('company')
    ->name('company.')
    ->middleware(['auth:web', 'role:company'])
    ->group(function () {

        // inicio de la empresa
        Route::get('/', [CompanyVacancyController::class, 'dashboard'])
            ->name('home');

        // ------- vacantes de la empresa -------

        // muestra las vacantes publicadas
        Route::get('/vacancies', [CompanyVacancyController::class, 'index'])
            ->name('vacancies.index');

        // muestra el formulario para crear una vacante
        Route::view('/vacancies/create', 'company.vacancy-create')
            ->name('vacancies.create');

        // guarda la nueva vacante
        Route::post('/vacancies', [CompanyVacancyController::class, 'store'])
            ->name('vacancies.store');

        // muestra el formulario para editar
        Route::get('/vacancies/{id}/edit', [CompanyVacancyController::class, 'edit'])
            ->name('vacancies.edit');

        // guarda los cambios de una vacante
        Route::put('/vacancies/{id}', [CompanyVacancyController::class, 'update'])
            ->name('vacancies.update');

        // retira una vacante
        Route::delete('/vacancies/{id}', [CompanyVacancyController::class, 'destroy'])
            ->name('vacancies.destroy');

        // muestra el detalle de una vacante
        Route::get('/vacancies/{id}', [CompanyVacancyController::class, 'show'])
            ->name('vacancies.show');

        // ------- solicitudes recibidas -------

        // muestra las postulaciones de la empresa
        Route::get('/applications', [ApplicationController::class, 'companyIndex'])
            ->name('applications');

        // muestra una postulacion
        Route::get('/applications/{id}', [ApplicationController::class, 'show'])
            ->name('applications.show');

        // descarga el cv del estudiante
        Route::get('/applications/{id}/cv', [ApplicationController::class, 'cv'])
            ->name('applications.cv');

        // cambia el estado de la postulacion
        Route::patch(
            '/applications/{id}/status',
            [ApplicationController::class, 'status']
        )->name('applications.status');

        // muestra la pagina de valoraciones
        Route::get('/ratings', [CompanyRatingsController::class, 'index'])
            ->name('ratings');

        // ------- membresia -------

        // muestra la membresia y sus solicitudes
        Route::get('/membership', [CompanyMembershipController::class, 'index'])
            ->name('membership');

        // muestra los planes disponibles
        Route::get('/membership/change', [CompanyMembershipController::class, 'catalog'])
            ->name('membership.change');

        // ------- perfil de la empresa -------

        // muestra el perfil
        Route::get('/profile', [ProfileController::class, 'company'])
            ->name('profile');

        // guarda los cambios del perfil
        Route::post('/profile', [ProfileController::class, 'updateCompany'])
            ->name('profile.update');
    });

// ------- rutas del administrador -------

// estas rutas solo las puede usar un administrador
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth:web', 'role:admin'])
    ->group(function () {

        // lleva al inicio del panel
        Route::redirect('/', '/admin/students')
            ->name('home');

        // ------- estudiantes -------

        // muestra la lista de estudiantes
        Route::get('/students', [AdminController::class, 'students'])
            ->name('students.index');

        // muestra un estudiante por su id
        Route::get('/students/{id}', [AdminController::class, 'student'])
            ->where('id', '[a-f0-9]{24}')
            ->name('students.show');

        // permite descargar su cv o kardex
        Route::get(
            '/students/{id}/{document}',
            [AdminController::class, 'document']
        )
            ->where('id', '[a-f0-9]{24}')
            ->where('document', 'cv|kardex')
            ->name('students.document');

        Route::get('/students/{id}/edit', [AdminController::class, 'editStudent'])
            ->where('id', '[a-f0-9]{24}')->name('students.edit');
        Route::put('/students/{id}', [AdminController::class, 'updateStudent'])
            ->where('id', '[a-f0-9]{24}')->name('students.update');

        Route::delete('/students/{id}', [AdminController::class, 'deleteStudent'])
            ->where('id', '[a-f0-9]{24}')->name('students.destroy');

        // ------- empresas -------

        // muestra la lista de empresas
        Route::get('/companies', [AdminController::class, 'companies'])
            ->name('companies.index');

        // muestra una empresa por su id
        Route::get('/companies/{id}', [AdminController::class, 'company'])
            ->where('id', '[a-f0-9]{24}')
            ->name('companies.show');
        Route::get('/companies/{id}/edit', [AdminController::class, 'editCompany'])
            ->where('id', '[a-f0-9]{24}')
            ->name('companies.edit');
        Route::put('/companies/{id}', [AdminController::class, 'updateCompany'])
            ->where('id', '[a-f0-9]{24}')
            ->name('companies.update');
        Route::delete('/companies/{id}', [AdminController::class, 'deleteCompany'])
            ->where('id', '[a-f0-9]{24}')->name('companies.destroy');

    });

