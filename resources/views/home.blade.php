@extends('layouts.app')
@section('content')
<div class="dashboard-home">
    <div class="welcome-hero">
        <div class="welcome-text">
            <span class="welcome-eyebrow">Panel de control</span>
            <h2>Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}</h2>
            <p>Gestiona tu facturación electrónica DIAN desde un solo lugar.</p>
        </div>
        <div class="welcome-icon">
            <i class="fas fa-file-invoice"></i>
        </div>
    </div>

    <div class="quick-grid">
        <a href="{{ route('documents_index') }}" class="quick-card">
            <div class="quick-icon quick-icon-blue"><i class="fas fa-file-alt"></i></div>
            <div class="quick-body">
                <h4>Documentos</h4>
                <p>Consulta y gestiona documentos electrónicos</p>
            </div>
            <i class="fas fa-arrow-right quick-arrow"></i>
        </a>

        <a href="{{ route('tax_index') }}" class="quick-card">
            <div class="quick-icon quick-icon-green"><i class="fas fa-percent"></i></div>
            <div class="quick-body">
                <h4>Impuestos</h4>
                <p>Configuración de impuestos y tarifas</p>
            </div>
            <i class="fas fa-arrow-right quick-arrow"></i>
        </a>

        <a href="{{ route('companies_index') }}" class="quick-card">
            <div class="quick-icon quick-icon-orange"><i class="fas fa-building"></i></div>
            <div class="quick-body">
                <h4>Empresas</h4>
                <p>Gestiona empresas, software y resoluciones</p>
            </div>
            <i class="fas fa-arrow-right quick-arrow"></i>
        </a>

        <a href="{{ route('configuration_admin') }}" class="quick-card">
            <div class="quick-icon quick-icon-purple"><i class="fas fa-plus-circle"></i></div>
            <div class="quick-body">
                <h4>Nueva Empresa</h4>
                <p>Registra y configura una nueva empresa</p>
            </div>
            <i class="fas fa-arrow-right quick-arrow"></i>
        </a>
    </div>
</div>
@endsection
