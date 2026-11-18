@extends('layouts.admin')
@section('title', 'Editar estudiante')
@section('content')
<a class="admin-link" href="{{ route('admin.students.show', $student->getKey()) }}">← Volver al perfil</a>
<h1>Editar estudiante</h1>
@if ($errors->any())
    <div class="admin-errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="admin-form" method="POST" action="{{ route('admin.students.update', $student->getKey()) }}">
    @csrf
    @method('PUT')
    <label>Nombre<input name="first_name" required maxlength="100" value="{{ old('first_name', $student->first_name) }}"></label>
    <label>Apellido paterno<input name="paternal_last_name" required maxlength="100" value="{{ old('paternal_last_name', $student->paternal_last_name) }}"></label>
    <label>Apellido materno<input name="maternal_last_name" maxlength="100" value="{{ old('maternal_last_name', $student->maternal_last_name) }}"></label>
    <label>Teléfono<input name="phone" maxlength="30" value="{{ old('phone', $student->phone) }}"></label>
    <div class="admin-form__full"><button class="admin-button admin-button--primary" type="submit">Guardar cambios</button></div>
</form>
@endsection
