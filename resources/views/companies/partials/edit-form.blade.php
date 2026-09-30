<form id="editForm">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Identificación</label>
                <input type="text" name="identification_number" class="form-control" value="{{ $company->identification_number }}" required>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>DV</label>
                <input type="text" name="dv" class="form-control" value="{{ $company->dv }}" maxlength="1">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Nombre/Razón Social</label>
                <input type="text" name="name" class="form-control" value="{{ $company->user->name ?? '' }}" required>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $company->user->email ?? '' }}" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="phone" class="form-control" value="{{ $company->phone }}">
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="address" class="form-control" value="{{ $company->address }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Registro Mercantil</label>
                <input type="text" name="merchant_registration" class="form-control" value="{{ $company->merchant_registration }}">
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Tipo Documento</label>
                <select name="type_document_identification_id" class="form-control">
                    @foreach($tables['type_document_identifications'] as $item)
                        <option value="{{ $item->id }}" {{ $company->type_document_identification_id == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Organización</label>
                <select name="type_organization_id" class="form-control">
                    @foreach($tables['type_organizations'] as $item)
                        <option value="{{ $item->id }}" {{ $company->type_organization_id == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Régimen</label>
                <select name="type_regime_id" class="form-control">
                    @foreach($tables['type_regimes'] as $item)
                        <option value="{{ $item->id }}" {{ $company->type_regime_id == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Departamento</label>
                <select name="department_id" class="form-control" onchange="loadMunicipalities(this.value)">
                    <option value="">Seleccionar...</option>
                    @foreach($tables['departments'] as $dept)
                        <option value="{{ $dept->id }}" {{ optional($company->municipality)->department_id == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Municipio</label>
                <select name="municipality_id" id="municipality_select" class="form-control">
                    <option value="{{ $company->municipality_id }}">{{ $company->municipality->name ?? 'Seleccionar...' }}</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Responsabilidad</label>
                <select name="type_liability_id" class="form-control">
                    @foreach($tables['type_liabilities'] as $item)
                        <option value="{{ $item->id }}" {{ $company->type_liability_id == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Estado</label>
                <select name="state" class="form-control">
                    <option value="1" {{ $company->state ? 'selected' : '' }}>Activa</option>
                    <option value="0" {{ !$company->state ? 'selected' : '' }}>Inactiva</option>
                </select>
            </div>
        </div>
    </div>
    
    <hr>
    <div class="text-right">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveCompany({{ $company->id }})">
            <i class="fa fa-save"></i> Guardar
        </button>
    </div>
</form>

<script>
function loadMunicipalities(deptId) {
    if (!deptId) return;
    const municipalities = @json($tables['municipalities']);
    const filtered = municipalities.filter(m => m.department_id == deptId);
    const select = document.getElementById('municipality_select');
    select.innerHTML = '<option value="">Seleccionar...</option>';
    filtered.forEach(m => {
        select.innerHTML += `<option value="${m.id}">${m.name}</option>`;
    });
}
</script>
