@extends('layouts.app')

@section('content')
<div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <div class="card-header" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 18px 24px; border: none; border-radius: 12px 12px 0 0;">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
            <h5 class="mb-0" style="font-weight: 600; color: #fff;"><i class="fa fa-file-alt mr-2" style="color: #f97316;"></i>Lista de Documentos</h5>
            <div class="d-flex align-items-center" style="gap: 10px;">
                <select id="filter-type" class="form-control form-control-sm" style="width: 150px; border-radius: 8px; border: none;" onchange="applyFilters()">
                    <option value="">Todos los tipos</option>
                    <option value="1">Facturas</option>
                    <option value="4">Notas Crédito</option>
                    <option value="5">Notas Débito</option>
                    <option value="11">Doc. Soporte</option>
                    <option value="6">Nómina</option>
                    <option value="7">Nómina Ajuste</option>
                </select>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="border: none; background: #f1f5f9; border-radius: 8px 0 0 8px;"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="text" id="filter-search" class="form-control" placeholder="Buscar número o cliente..." onkeyup="debounceFilter()" style="border: none; border-radius: 0 8px 8px 0;">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 100px;">Tipo</th>
                        <th style="width: 140px;">Número</th>
                        <th>Cliente</th>
                        <th style="width: 100px;">Fecha</th>
                        <th style="width: 110px;" class="text-right">Total</th>
                        <th style="width: 100px;">Estado</th>
                        <th style="width: 90px;" class="text-center">Archivos</th>
                        <th style="width: 100px;" class="text-center">DIAN</th>
                    </tr>
                </thead>
                <tbody id="documents-body">
                    <tr><td colspan="9" class="text-center py-4 text-muted">Cargando documentos...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center" id="pagination-footer" style="display: none !important;">
        <span class="text-muted" id="doc-count" style="font-size: 13px;">0 documentos</span>
        <div class="d-flex align-items-center" style="gap: 5px;">
            <button class="btn btn-sm btn-outline-secondary" id="btn-prev" onclick="changePage(-1)">
                <i class="fa fa-chevron-left"></i>
            </button>
            <span class="badge badge-primary px-3 py-2" id="page-num" style="background: #f97316; font-size: 13px;">1</span>
            <button class="btn btn-sm btn-outline-secondary" id="btn-next" onclick="changePage(1)">
                <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales */
.table thead th {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px;
}
.table tbody td {
    padding: 12px;
    vertical-align: middle;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
}
.table tbody tr:hover {
    background-color: #f8fafc;
}

/* Número documento */
.doc-number {
    font-weight: 600;
    color: #1e293b;
}

/* Badges de estado */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-success {
    background: #dcfce7;
    color: #166534;
}
.status-warning {
    background: #fef3c7;
    color: #92400e;
}
.status-info {
    background: #dbeafe;
    color: #1e40af;
}

/* Botones de archivos */
.file-btn {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    margin: 1px;
}
.file-btn-xml {
    background: #dbeafe;
    color: #1e40af;
}
.file-btn-pdf {
    background: #fce7f3;
    color: #be185d;
}
.file-btn:hover {
    opacity: 0.8;
    text-decoration: none;
}

/* Botón DIAN */
.btn-dian {
    display: inline-block;
    padding: 4px 12px;
    background: #f97316;
    color: white;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
}
.btn-dian:hover {
    background: #ea580c;
    color: white;
    text-decoration: none;
}

/* Badge ambiente */
.env-badge {
    display: inline-block;
    margin-left: 4px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 600;
}
.env-hab {
    background: #fef3c7;
    color: #92400e;
}
.env-prod {
    background: #dcfce7;
    color: #166534;
}

/* Botón reenvío */
.btn-resend {
    margin-left: 4px;
    padding: 2px 6px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 10px;
    cursor: pointer;
}
.btn-resend:hover {
    background: #2563eb;
}

/* Input search focus */
.form-control:focus {
    border-color: #f97316;
    box-shadow: 0 0 0 0.1rem rgba(249, 115, 22, 0.25);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let page = 1, pages = 1, total = 0, timeout = null;
const DIAN = {
    1: 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey=',
    2: 'https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey='
};

document.addEventListener('DOMContentLoaded', load);

function debounceFilter() {
    clearTimeout(timeout);
    timeout = setTimeout(function() { page = 1; load(); }, 400);
}

function applyFilters() {
    page = 1;
    load();
}

function load() {
    var url = '/documents/records?page=' + page + '&per_page=15';
    var params = new URLSearchParams(window.location.search);
    var company = params.get('company') || params.get('nit');
    if (company) url += '&company=' + company;
    
    var type = document.getElementById('filter-type').value;
    var search = document.getElementById('filter-search').value;
    if (type) url += '&type=' + type;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            total = data.total || 0;
            pages = Math.ceil(total / 15) || 1;
            document.getElementById('doc-count').textContent = total + ' documento' + (total !== 1 ? 's' : '');
            render(data.data);
            updatePagination();
        })
        .catch(function() {
            document.getElementById('documents-body').innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Error al cargar</td></tr>';
        });
}

function render(docs) {
    var tbody = document.getElementById('documents-body');
    
    if (!docs || docs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No se encontraron documentos</td></tr>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < docs.length; i++) {
        var d = docs[i];
        var n = (page - 1) * 15 + i + 1;
        
        // Estado
        var status = '<span class="status-badge status-' + d.state_class + '">' + d.state_name + '</span>';
        if (d.can_resend) {
            status += '<button class="btn-resend" onclick="resend(' + d.id + ')"><i class="fa fa-redo"></i></button>';
        }
        
        // Archivos
        var files = '';
        if (d.xml && d.xml !== 'INITIAL_NUMBER.XML') {
            files += '<a href="/documents/downloadxml/' + d.xml + '" class="file-btn file-btn-xml">XML</a>';
        }
        if (d.pdf && d.pdf !== 'INITIAL_NUMBER.PDF') {
            files += '<a href="/documents/downloadpdf/' + d.pdf + '" class="file-btn file-btn-pdf">PDF</a>';
        }
        if (!files) files = '<span class="text-muted">-</span>';
        
        // DIAN
        var dian = '<span class="text-muted">-</span>';
        if (d.cufe && d.cufe.length > 10) {
            var dianUrl = DIAN[d.environment] || DIAN[2];
            var envText = d.environment == 1 ? 'PROD' : 'HAB';
            var envClass = d.environment == 1 ? 'env-prod' : 'env-hab';
            dian = '<a href="' + dianUrl + d.cufe + '" target="_blank" class="btn-dian">Ver</a>';
            dian += '<span class="env-badge ' + envClass + '">' + envText + '</span>';
        }
        
        // Total formateado
        var totalStr = d.total ? '$' + Number(d.total).toLocaleString('es-CO') : '$0';
        
        // Fecha
        var fecha = d.date ? d.date.split(' ')[0] : '-';
        
        html += '<tr>';
        html += '<td>' + n + '</td>';
        html += '<td>' + (d.type_document_name || '-') + '</td>';
        html += '<td class="doc-number">' + (d.prefix || '') + (d.number || '') + '</td>';
        html += '<td>' + (d.client || '-') + '</td>';
        html += '<td>' + fecha + '</td>';
        html += '<td class="text-right">' + totalStr + '</td>';
        html += '<td>' + status + '</td>';
        html += '<td class="text-center">' + files + '</td>';
        html += '<td class="text-center">' + dian + '</td>';
        html += '</tr>';
    }
    
    tbody.innerHTML = html;
}

function updatePagination() {
    var footer = document.getElementById('pagination-footer');
    footer.style.display = 'flex';
    
    document.getElementById('page-num').textContent = page;
    document.getElementById('btn-prev').disabled = page <= 1;
    document.getElementById('btn-next').disabled = page >= pages;
}

function changePage(dir) {
    var newPage = page + dir;
    if (newPage >= 1 && newPage <= pages) {
        page = newPage;
        load();
    }
}

function resend(id) {
    Swal.fire({
        title: 'Reenviar Documento',
        text: '¿Desea reenviar este documento a la DIAN?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando...',
                text: 'Procesando documento con la DIAN',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });
            
            fetch('/documents/resend/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function(result) {
                if (result.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Documento Procesado',
                        html: result.data.message + (result.data.cufe ? '<br><br><small><strong>CUFE/CUDS:</strong><br>' + result.data.cufe + '</small>' : ''),
                        confirmButtonColor: '#f97316'
                    });
                    load(); // Recargar la lista
                } else {
                    var errorHtml = '<p>' + result.data.message + '</p>';
                    if (result.data.errors && result.data.errors.length > 0) {
                        errorHtml += '<br><strong>Errores DIAN:</strong><ul style="text-align:left;font-size:12px;">';
                        for (var i = 0; i < result.data.errors.length; i++) {
                            errorHtml += '<li>' + result.data.errors[i] + '</li>';
                        }
                        errorHtml += '</ul>';
                    }
                    if (result.data.dian_status) {
                        errorHtml += '<br><small><strong>Estado:</strong> ' + result.data.dian_status + '</small>';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error DIAN',
                        html: errorHtml,
                        confirmButtonColor: '#f97316'
                    });
                }
            })
            .catch(function(error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: error.message,
                    confirmButtonColor: '#f97316'
                });
            });
        }
    });
}
</script>
@endsection
