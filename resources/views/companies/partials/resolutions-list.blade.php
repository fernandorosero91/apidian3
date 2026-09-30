<h6 class="mb-3">Empresa: <strong>{{ $company->user->name ?? $company->identification_number }}</strong></h6>

@if($company->resolutions->count() > 0)
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr>
                <th>Tipo</th>
                <th>Prefijo</th>
                <th>Resolución</th>
                <th>Desde</th>
                <th>Hasta</th>
                <th>Actual</th>
                <th>Vigencia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($company->resolutions as $res)
            <tr id="res-row-{{ $res->id }}">
                <td>{{ $res->type_document_id }}</td>
                <td><strong>{{ $res->prefix }}</strong></td>
                <td>{{ $res->resolution }}</td>
                <td>{{ $res->from }}</td>
                <td>{{ $res->to }}</td>
                <td>
                    <input type="number" class="form-control form-control-sm" style="width: 80px;" 
                           value="{{ $res->next_consecutive }}" id="next-{{ $res->id }}">
                </td>
                <td>
                    <small>{{ $res->date_from }} - {{ $res->date_to }}</small>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="updateResolution({{ $res->id }})">
                        <i class="fa fa-save"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteResolution({{ $res->id }})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="alert alert-info">
    No hay resoluciones registradas para esta empresa.
</div>
@endif

<script>
function updateResolution(id) {
    const nextConsecutive = document.getElementById('next-' + id).value;
    
    fetch('/companies/resolution/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ next_consecutive: nextConsecutive })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('✅ Resolución actualizada');
        } else {
            alert('❌ ' + res.message);
        }
    });
}

function deleteResolution(id) {
    if (!confirm('¿Eliminar esta resolución?')) return;
    
    fetch('/companies/resolution/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('res-row-' + id).remove();
            alert('✅ Resolución eliminada');
        } else {
            alert('❌ ' + res.message);
        }
    });
}
</script>
