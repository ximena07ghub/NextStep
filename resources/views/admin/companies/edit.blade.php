@extends('layouts.admin')
@section('title', 'Editar empresa')
@section('content')
<a class="admin-link" href="{{ route('admin.companies.show', $company->getKey()) }}">← Volver al perfil</a>
<h1>Editar empresa</h1>
@if ($errors->any())
    <div class="admin-errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="admin-form" method="POST" action="{{ route('admin.companies.update', $company->getKey()) }}">
    @csrf
    @method('PUT')
    <label>Nombre de empresa<input name="company_name" required maxlength="150" value="{{ old('company_name', $company->company_name) }}"></label>
    <label>RFC<input name="rfc" required maxlength="20" value="{{ old('rfc', $company->rfc) }}"></label>
    <label>Estado<input name="state" maxlength="100" value="{{ old('state', $company->state) }}"></label>
    <label>Ciudad<input name="city" maxlength="100" value="{{ old('city', $company->city) }}"></label>
    <label class="admin-form__full">Descripción<textarea name="description" maxlength="2000" rows="5">{{ old('description', $company->description) }}</textarea></label>
    <div class="admin-form__full"><button class="admin-button admin-button--primary" type="submit">Guardar cambios</button></div>
</form>
@endsection
