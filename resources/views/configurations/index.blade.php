@extends('layouts.app')

@section('content')
<div class="card" style="margin-top: -20px;">
    <div class="card-header" style="padding: 10px 15px; font-weight: 600;">
        Lista de Empresas
    </div>
    <div class="card-body" style="padding: 10px;">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NIT</th>
                        <th>Empresa</th>
                        <th>Email</th>
                        <th>Ambiente</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="companies-body">
                    <tr><td colspan="7" class="text-center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.table-sm td, .table-sm th { padding: 8px 10px; font-size: 13px; }
.btn-docs {
    background: #f97316;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
}
.btn-docs:hover { background: #ea580c; color: white; text-decoration: none; }
.btn-edit {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: none;
    margin-right: 5px;
}
.btn-edit:hover { background: #2563eb; color: white; text-decoration: none; }
.env-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 600;
}
.env-hab { background: #fef3c7; color: #92400e; }
.env-prod { background: #dcfce7; color: #166534; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCompanies();
});

function loadCompanies() {
    fetch('/configuration/records')
        .then(response => response.json())
        .then(data => {
            renderTable(data.data);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('companies-body').innerHTML = 
                '<tr><td colspan="7" class="text-center text-danger">Error al cargar</td></tr>';
        });
}

function renderTable(companies) {
    const tbody = document.getElementById('companies-body');
    
    // Filtrar solo empresas con NIT
    companies = companies.filter(c => c.identification_number);
    
    if (!companies || companies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay empresas registradas</td></tr>';
        return;
    }
    
    let html = '';
    companies.forEach((company, index) => {
        // Ambiente
        let envHtml = '<span class="env-badge env-hab">Habilitación</span>';
        if (company.type_environment_id == 1) {
            envHtml = '<span class="env-badge env-prod">Producción</span>';
        }
        
        // Fecha
        let fecha = company.created_at ? company.created_at.split('T')[0] : '-';
        
        html += '<tr>';
        html += '<td>' + (index + 1) + '</td>';
        html += '<td><strong>' + (company.identification_number || '-') + '</strong></td>';
        html += '<td>' + (company.name || 'Sin nombre') + '</td>';
        html += '<td>' + (company.email || '-') + '</td>';
        html += '<td>' + envHtml + '</td>';
        html += '<td>' + fecha + '</td>';
        html += '<td>';
        html += '<a href="/documents?company=' + company.identification_number + '&name=' + encodeURIComponent(company.name || '') + '" class="btn-docs"><i class="fa fa-file-alt"></i> Documentos</a>';
        html += '</td>';
        html += '</tr>';
    });
    
    tbody.innerHTML = html;
}
</script>
@endsection
