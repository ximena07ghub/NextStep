<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NextStep</title>

    <link
        rel="icon"
        href="{{ asset('images/favicon.png') }}"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])



</head>


<body>

    <div class="student-layout">

        <x-app-header
            home-route="student.home"
            profile-route="student.profile"
        />


        <main class="student-layout__content">

            @yield('content')

        </main>

        @include('components.footer')

    </div>

</body>

</html>