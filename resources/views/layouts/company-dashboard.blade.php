@extends('layouts.company')

@section('content')

<div class="company-home">

    <div class="company-home__navigation">

        @include('components.company.publish-button')

        @include('components.company.sidebar')

    </div>


    <div class="company-home__workspace">

        <section class="company-home__content">

            @yield('dashboard-content')

        </section>

    </div>

</div>

@endsection