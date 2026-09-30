@extends('layouts.app')

@section('content')
<div class="card shadow-sm" style="border:none;border-radius:12px;overflow:visible;">
    <div class="card-header" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);padding:18px 24px;border:none;">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white" style="font-weight:600;font-size:16px;">
                <i class="fa fa-building mr-2" style="color:#f97316;"></i>Gestión de Empresas
            </h5>
            <div class="d-flex align-items-center" style="gap:12px;">
                <div style="position:relative;">
                    <i class="fa fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                    <input type="text" id="search" placeholder="Buscar empresa..." onkeyup="debounceSearch()" style="width:260px;padding:10px 12px 10px 38px;border:none;border-radius:8px;font-size:14px;background:#f1f5f9;">
                </div>
                <a href="/configuration_admin" class="btn-orange"><i class="fa fa-plus"></i> Nueva Empresa</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0" style="overflow:visible;">
        <table class="table mb-0">
            <thead>
                <tr style="background:#f8fafc;">
                    <th class="th-head">#</th>
                    <th class="th-head">NIT</th>
                    <th class="th-head">Empresa</th>
                    <th class="th-head">Email</th>
                    <th class="th-head">Ambiente</th>
                    <th class="th-head">Estado</th>
                    <th class="th-head">Docs</th>
                    <th class="th-head">Fecha</th>
                    <th class="th-head" style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tblBody"></tbody>
        </table>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog" style="max-width:1100px;margin:20px auto;">
        <div class="modal-content modal-custom">
            <div class="modal-header-dark">
                <span><i class="fa fa-building mr-2"></i>Configuración de Empresa</span>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-tabs">
                <ul class="nav nav-tabs" id="tabsNav">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#t1"><span class="tn">1</span>Empresa</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t2"><span class="tn">2</span>Software</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t3"><span class="tn">3</span>Certificado</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t4"><span class="tn">4</span>Resolución</a></li>
                </ul>
            </div>
            <div class="tab-content" style="padding:24px;background:#fff;">
                <div class="tab-pane fade show active" id="t1"></div>
                <div class="tab-pane fade" id="t2"></div>
                <div class="tab-pane fade" id="t3"></div>
                <div class="tab-pane fade" id="t4"></div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.th-head{padding:14px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0;background:#f8fafc;}
#tblBody tr{transition:background 0.15s;position:relative;}
#tblBody tr:hover{background:#fff7ed;}
#tblBody td{padding:14px 16px;font-size:13px;vertical-align:middle;border-bottom:1px solid #f1f5f9;position:relative;}
.btn-orange{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff!important;padding:10px 20px;border-radius:10px;font-weight:600;font-size:13px;text-decoration:none!important;display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;box-shadow:0 4px 12px rgba(249,115,22,0.25);}
.btn-orange:hover{box-shadow:0 6px 16px rgba(249,115,22,0.35);transform:translateY(-1px);}
.btn-gray{background:#f1f5f9;color:#475569;padding:10px 20px;border-radius:10px;font-weight:600;font-size:13px;border:none;cursor:pointer;}
.btn-gray:hover{background:#e2e8f0;}
.badge-prod{background:#dcfce7;color:#166534;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-hab{background:#fef3c7;color:#92400e;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-on{background:#dcfce7;color:#166534;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-off{background:#fee2e2;color:#991b1b;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:600;}
.dropdown-menu,.dropup .dropdown-menu,#tblBody .dropdown-menu,#tblBody .dropup .dropdown-menu{border:none!important;box-shadow:0 10px 40px rgba(0,0,0,0.15)!important;border-radius:12px!important;padding:8px!important;min-width:180px!important;background-color:#fff!important;opacity:1!important;}
.dropdown-item,#tblBody .dropdown-item{font-size:13px!important;padding:10px 16px!important;border-radius:8px!important;margin:2px 0!important;background:#fff!important;display:block!important;}
.dropdown-item:hover,#tblBody .dropdown-item:hover{background:#fff7ed!important;}
.dropdown-item i{width:20px;margin-right:6px;}

/* Modal Empresas */
#modalEdit .modal-content{border:none!important;border-radius:16px!important;overflow:hidden!important;box-shadow:0 25px 50px rgba(0,0,0,0.2)!important;}
#modalEdit .modal-header-dark{background:linear-gradient(135deg,#1e293b,#334155)!important;padding:20px 28px!important;display:flex!important;justify-content:space-between!important;align-items:center!important;color:#fff!important;font-size:17px!important;font-weight:600!important;border:none!important;border-radius:0!important;}
#modalEdit .modal-header-dark .close{color:#fff!important;opacity:1!important;font-size:28px!important;font-weight:300!important;text-shadow:none!important;margin:0!important;padding:0!important;}
#modalEdit .modal-tabs{background:#f8fafc!important;border-bottom:1px solid #e2e8f0!important;padding:0 24px!important;}
#modalEdit #tabsNav{border:none!important;margin:0!important;display:flex!important;}
#modalEdit #tabsNav .nav-item{margin:0!important;border:none!important;}
#modalEdit #tabsNav .nav-link{border:none!important;padding:16px 20px!important;font-size:14px!important;font-weight:500!important;color:#64748b!important;background:transparent!important;display:flex!important;align-items:center!important;gap:8px!important;border-bottom:3px solid transparent!important;margin-bottom:-1px!important;border-radius:0!important;}
#modalEdit #tabsNav .nav-link:hover{color:#f97316!important;background:transparent!important;border-color:transparent!important;border-bottom-color:#f97316!important;}
#modalEdit #tabsNav .nav-link.active{color:#f97316!important;border-color:transparent!important;border-bottom:3px solid #f97316!important;font-weight:600!important;background:transparent!important;}
#modalEdit .tn{width:22px;height:22px;border-radius:50%;background:#cbd5e1;color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;margin-right:6px;}
#modalEdit #tabsNav .nav-link.active .tn{background:#f97316;}
#modalEdit .tab-content{padding:24px!important;background:#fff!important;}

/* Form Empresas */
#modalEdit .fcard{background:#f8fafc!important;border-radius:14px!important;padding:24px!important;margin-bottom:20px!important;border:none!important;box-shadow:none!important;}
#modalEdit .fcard-title{font-size:15px!important;font-weight:700!important;color:#1e293b!important;margin-bottom:4px!important;display:flex!important;align-items:center!important;gap:8px!important;}
#modalEdit .fcard-title i{color:#f97316!important;}
#modalEdit .fcard-desc{font-size:13px!important;color:#64748b!important;margin-bottom:20px!important;}
#modalEdit .fgrid{display:grid!important;gap:16px!important;margin-bottom:16px!important;}
#modalEdit .fgrid-2{grid-template-columns:repeat(2,1fr)!important;}
#modalEdit .fgrid-3{grid-template-columns:repeat(3,1fr)!important;}
#modalEdit .fgrid-4{grid-template-columns:repeat(4,1fr)!important;}
#modalEdit .flabel{display:block!important;font-size:11px!important;font-weight:700!important;color:#475569!important;margin-bottom:6px!important;text-transform:uppercase!important;letter-spacing:0.5px!important;}
#modalEdit .finput,
#modalEdit input.finput,
#modalEdit select.finput{width:100%!important;padding:12px 14px!important;border:2px solid #e2e8f0!important;border-radius:10px!important;font-size:14px!important;color:#1e293b!important;background:#fff!important;transition:border 0.2s,box-shadow 0.2s!important;box-sizing:border-box!important;height:auto!important;line-height:1.4!important;}
#modalEdit .finput:focus,
#modalEdit input.finput:focus,
#modalEdit select.finput:focus{outline:none!important;border-color:#f97316!important;box-shadow:0 0 0 3px rgba(249,115,22,0.1)!important;}
#modalEdit select.finput{appearance:none!important;-webkit-appearance:none!important;-moz-appearance:none!important;background:#fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='%236b7280' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 12px center/14px no-repeat!important;padding-right:36px!important;cursor:pointer!important;}

/* Alerts */
#modalEdit .alert-ok{background:linear-gradient(135deg,#ecfdf5,#d1fae5)!important;border:2px solid #a7f3d0!important;border-radius:12px!important;padding:16px 20px!important;margin-bottom:20px!important;display:flex!important;align-items:center!important;gap:12px!important;color:#166534!important;font-size:14px!important;}
#modalEdit .alert-ok i{color:#22c55e!important;font-size:20px!important;}
#modalEdit .alert-warn{background:linear-gradient(135deg,#fffbeb,#fef3c7)!important;border:2px solid #fde68a!important;border-radius:12px!important;padding:16px 20px!important;margin-bottom:20px!important;display:flex!important;align-items:center!important;gap:12px!important;color:#92400e!important;font-size:14px!important;}
#modalEdit .alert-warn i{color:#f59e0b!important;font-size:20px!important;}

/* Resolution */
#modalEdit .res-box{background:#fff7ed!important;border:2px dashed #fdba74!important;border-radius:12px!important;padding:20px!important;margin-bottom:20px!important;display:none!important;}
#modalEdit .res-box.show{display:block!important;}
#modalEdit .res-tbl{width:100%!important;border-collapse:collapse!important;background:#fff!important;border-radius:12px!important;overflow:hidden!important;border:1px solid #e2e8f0!important;}
#modalEdit .res-tbl th{background:#f8fafc!important;padding:12px 14px!important;font-size:11px!important;font-weight:700!important;color:#64748b!important;text-transform:uppercase!important;text-align:left!important;border-bottom:2px solid #e2e8f0!important;}
#modalEdit .res-tbl td{padding:12px 14px!important;font-size:13px!important;border-bottom:1px solid #f1f5f9!important;vertical-align:middle!important;}
#modalEdit .res-tbl tr:last-child td{border-bottom:none!important;}
#modalEdit .res-tbl tr:hover{background:#fefce8!important;}
#modalEdit .badge-num{background:linear-gradient(135deg,#0ea5e9,#0284c7)!important;color:#fff!important;padding:5px 10px!important;border-radius:6px!important;font-size:11px!important;font-weight:600!important;font-family:monospace!important;}
#modalEdit .btn-sm-icon{width:32px!important;height:32px!important;border-radius:8px!important;border:none!important;cursor:pointer!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;margin:0 2px!important;}
#modalEdit .btn-sm-edit{background:#dbeafe!important;color:#2563eb!important;}
#modalEdit .btn-sm-edit:hover{background:#bfdbfe!important;}
#modalEdit .btn-sm-del{background:#fee2e2!important;color:#dc2626!important;}
#modalEdit .btn-sm-del:hover{background:#fecaca!important;}
</style>
@endpush

<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.3/sweetalert2.all.min.js"></script>
<script>
var data=[],tbl={},cid=null,cdata=null,st=null;
document.addEventListener('DOMContentLoaded',function(){loadTbl();loadData();});
function debounceSearch(){clearTimeout(st);st=setTimeout(loadData,300);}
function loadTbl(){fetch('/companies/tables').then(r=>r.json()).then(d=>{tbl=d;});}
function loadData(){
    fetch('/companies/records?search='+encodeURIComponent(document.getElementById('search').value))
    .then(r=>r.json()).then(d=>{data=d.data;render();});
}
function render(){
    var h='';
    if(!data.length){h='<tr><td colspan="9" style="text-align:center;padding:40px;color:#94a3b8;">No hay empresas</td></tr>';}
    else{
        data.forEach((c,i)=>{
            h+='<tr>';
            h+='<td style="font-weight:600;color:#64748b;">'+(i+1)+'</td>';
            h+='<td><span style="font-weight:700;color:#1e293b;">'+c.identification_number+'</span><span style="color:#94a3b8;">-'+(c.dv||0)+'</span></td>';
            h+='<td style="font-weight:500;color:#1e293b;">'+c.name+'</td>';
            h+='<td style="color:#64748b;font-size:12px;">'+c.email+'</td>';
            h+='<td><span class="badge-'+(c.type_environment_id==1?'prod':'hab')+'">'+(c.type_environment_id==1?'Producción':'Habilitación')+'</span></td>';
            h+='<td><span class="badge-'+(c.state?'on':'off')+'">'+(c.state?'Activa':'Inactiva')+'</span></td>';
            h+='<td><span style="background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;">'+c.documents_count+'</span></td>';
            h+='<td style="color:#64748b;font-size:12px;">'+c.created_at+'</td>';
            h+='<td style="text-align:center;">';
            h+='<div class="dropup"><button class="btn btn-sm" style="background:#f1f5f9;border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;color:#475569;" data-toggle="dropdown">Acciones <i class="fa fa-chevron-up ml-1" style="font-size:9px;"></i></button>';
            h+='<div class="dropdown-menu dropdown-menu-right">';
            h+='<a class="dropdown-item" href="#" onclick="edit('+c.id+');return false;"><i class="fa fa-edit text-primary"></i>Editar</a>';
            h+='<a class="dropdown-item" href="/documents?nit='+c.identification_number+'"><i class="fa fa-file-alt text-success"></i>Ver Documentos</a>';
            h+='<a class="dropdown-item" href="#" onclick="chgEnv('+c.id+','+c.type_environment_id+');return false;"><i class="fa fa-exchange-alt text-info"></i>Cambiar Ambiente</a>';
            h+='<a class="dropdown-item" href="#" onclick="chgState('+c.id+','+(c.state?1:0)+');return false;"><i class="fa fa-'+(c.state?'ban text-warning':'check text-success')+'"></i>'+(c.state?'Deshabilitar':'Habilitar')+'</a>';
            h+='<div class="dropdown-divider"></div>';
            h+='<a class="dropdown-item text-danger" href="#" onclick="del('+c.id+',\''+c.identification_number+'\');return false;"><i class="fa fa-trash"></i>Eliminar</a>';
            h+='</div></div></td></tr>';
        });
    }
    document.getElementById('tblBody').innerHTML=h;
}
function csrf(){return document.querySelector('meta[name="csrf-token"]').content;}
function opts(arr,sel){if(!arr)return'';return arr.map(a=>'<option value="'+a.id+'"'+(a.id==sel?' selected':'')+'>'+a.name+'</option>').join('');}
function optsMun(sel,dept){if(!tbl.municipalities)return'<option value="">Seleccionar...</option>';var h='<option value="">Seleccionar...</option>';tbl.municipalities.forEach(m=>{if(m.department_id==dept)h+='<option value="'+m.id+'"'+(m.id==sel?' selected':'')+'>'+m.name+'</option>';});return h;}
function edit(id){
    cid=id;
    fetch('/companies/'+id+'/data').then(r=>r.json()).then(d=>{
        cdata=d;fillT1();fillT2();fillT3();fillT4();
        $('#tabsNav a:first').tab('show');
        $('#modalEdit').modal('show');
    });
}
</script>

<script>
function fillT1(){
    var c=cdata.company;
    var h='<div class="fcard">';
    h+='<div class="fcard-title"><i class="fa fa-building"></i>Datos Generales de la Empresa</div>';
    h+='<div class="fcard-desc">Complete la información básica de la empresa para facturación electrónica DIAN.</div>';
    h+='<div class="fgrid fgrid-3">';
    h+='<div><label class="flabel">Identificación (NIT)</label><input class="finput" id="f1" value="'+c.identification_number+'"></div>';
    h+='<div><label class="flabel">DV</label><input class="finput" id="f2" value="'+(c.dv||'')+'" maxlength="1"></div>';
    h+='<div><label class="flabel">Razón Social / Nombre</label><input class="finput" id="f3" value="'+c.name+'"></div>';
    h+='</div>';
    h+='<div class="fgrid fgrid-3">';
    h+='<div><label class="flabel">Registro Mercantil</label><input class="finput" id="f4" value="'+(c.merchant_registration||'')+'"></div>';
    h+='<div><label class="flabel">Dirección</label><input class="finput" id="f5" value="'+(c.address||'')+'"></div>';
    h+='<div><label class="flabel">Teléfono</label><input class="finput" id="f6" value="'+(c.phone||'')+'"></div>';
    h+='</div>';
    h+='<div class="fgrid fgrid-3">';
    h+='<div><label class="flabel">Correo Electrónico</label><input class="finput" id="f7" value="'+c.email+'" type="email"></div>';
    h+='<div><label class="flabel">Tipo de Documento</label><select class="finput" id="f8">'+opts(tbl.type_document_identifications,c.type_document_identification_id)+'</select></div>';
    h+='<div><label class="flabel">Departamento</label><select class="finput" id="f9" onchange="chgDept()"><option value="">Seleccionar...</option>'+opts(tbl.departments,c.department_id)+'</select></div>';
    h+='</div>';
    h+='<div class="fgrid fgrid-4">';
    h+='<div><label class="flabel">Municipio</label><select class="finput" id="f10">'+optsMun(c.municipality_id,c.department_id)+'</select></div>';
    h+='<div><label class="flabel">Tipo Organización</label><select class="finput" id="f11">'+opts(tbl.type_organizations,c.type_organization_id)+'</select></div>';
    h+='<div><label class="flabel">Régimen Tributario</label><select class="finput" id="f12">'+opts(tbl.type_regimes,c.type_regime_id)+'</select></div>';
    h+='<div><label class="flabel">Responsabilidad Fiscal</label><select class="finput" id="f13">'+opts(tbl.type_liabilities,c.type_liability_id)+'</select></div>';
    h+='</div>';
    h+='</div>';
    h+='<button class="btn-orange" onclick="saveT1()"><i class="fa fa-save"></i>Guardar Cambios</button>';
    document.getElementById('t1').innerHTML=h;
}
function fillT2(){
    var s=cdata.software||{};
    var h='<div class="fcard">';
    h+='<div class="fcard-title"><i class="fa fa-cog"></i>Configuración del Software DIAN</div>';
    h+='<div class="fcard-desc">Ingrese los datos del software registrado en la DIAN para esta empresa.</div>';
    h+='<div class="fgrid" style="grid-template-columns:1fr;">';
    h+='<div><label class="flabel">Identificador del Software (UUID)</label><input class="finput" id="s1" value="'+(s.identifier||'')+'" style="font-family:monospace;"></div>';
    h+='</div>';
    h+='<div class="fgrid fgrid-2">';
    h+='<div><label class="flabel">PIN del Software</label><input class="finput" id="s2" value="'+(s.pin||'')+'"></div>';
    h+='<div><label class="flabel">URL del Servicio Web (Opcional)</label><input class="finput" id="s3" value="'+(s.url||'')+'"></div>';
    h+='</div>';
    h+='</div>';
    h+='<button class="btn-orange" onclick="saveT2()"><i class="fa fa-save"></i>Guardar Software</button>';
    document.getElementById('t2').innerHTML=h;
}
function fillT3(){
    var c=cdata.certificate||{};
    var h='<div class="fcard">';
    h+='<div class="fcard-title"><i class="fa fa-shield-alt"></i>Certificado Digital</div>';
    h+='<div class="fcard-desc">Cargue el certificado digital (.p12 o .pfx) para firmar los documentos electrónicos.</div>';
    if(c.name){
        h+='<div class="alert-ok"><i class="fa fa-check-circle"></i><span><strong>Certificado cargado:</strong> '+c.name+(c.expiration?' | <strong>Vence:</strong> '+c.expiration:'')+'</span></div>';
    }else{
        h+='<div class="alert-warn"><i class="fa fa-exclamation-triangle"></i><span>No hay certificado digital cargado.</span></div>';
    }
    h+='<div class="fgrid fgrid-2">';
    h+='<div><label class="flabel">Archivo del Certificado (.p12 / .pfx)</label><input type="file" class="finput" id="c1" accept=".p12,.pfx" style="padding:10px;"></div>';
    h+='<div><label class="flabel">Contraseña del Certificado</label><input type="password" class="finput" id="c2" placeholder="••••••••"></div>';
    h+='</div>';
    h+='</div>';
    h+='<button class="btn-orange" onclick="saveT3()"><i class="fa fa-upload"></i>Subir Certificado</button>';
    document.getElementById('t3').innerHTML=h;
}
function fillT4(){
    var r=cdata.resolutions||[];
    var tp=[{id:1,n:'Factura'},{id:4,n:'Nota Crédito'},{id:5,n:'Nota Débito'},{id:11,n:'Doc. Soporte'},{id:13,n:'Nota Ajuste DS'}];
    var h='<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">';
    h+='<div><div class="fcard-title" style="margin:0;"><i class="fa fa-file-invoice"></i>Resoluciones de Facturación</div>';
    h+='<div style="font-size:13px;color:#64748b;margin-top:2px;">Administre las resoluciones DIAN de esta empresa</div></div>';
    h+='<button class="btn-orange" onclick="toggleNR()"><i class="fa fa-plus"></i>Nueva Resolución</button></div>';
    
    h+='<div class="res-box" id="nrBox">';
    h+='<div style="font-weight:600;color:#1e293b;margin-bottom:14px;"><i class="fa fa-plus-circle mr-2" style="color:#f97316;"></i>Agregar Nueva Resolución</div>';
    h+='<div class="fgrid fgrid-3">';
    h+='<div><label class="flabel">Tipo de Documento</label><select class="finput" id="nr1">'+tp.map(t=>'<option value="'+t.id+'">'+t.n+'</option>').join('')+'</select></div>';
    h+='<div><label class="flabel">Prefijo</label><input class="finput" id="nr2" placeholder="SETP"></div>';
    h+='<div><label class="flabel">Número de Resolución</label><input class="finput" id="nr3" placeholder="18760000001"></div>';
    h+='</div>';
    h+='<div class="fgrid fgrid-4">';
    h+='<div><label class="flabel">Desde</label><input type="number" class="finput" id="nr4" value="1"></div>';
    h+='<div><label class="flabel">Hasta</label><input type="number" class="finput" id="nr5" value="5000"></div>';
    h+='<div><label class="flabel">Vigencia Desde</label><input type="date" class="finput" id="nr6"></div>';
    h+='<div><label class="flabel">Vigencia Hasta</label><input type="date" class="finput" id="nr7"></div>';
    h+='</div>';
    h+='<div style="margin-top:12px;display:flex;gap:10px;"><button class="btn-orange" onclick="addRes()"><i class="fa fa-check"></i>Guardar</button><button class="btn-gray" onclick="toggleNR()">Cancelar</button></div>';
    h+='</div>';
    
    h+='<div style="overflow-x:auto;"><table class="res-tbl"><thead><tr>';
    h+='<th>Tipo</th><th>Prefijo</th><th>Resolución</th><th>Rango</th><th>Actual</th><th>Vigencia</th><th style="text-align:center;width:100px;">Acciones</th>';
    h+='</tr></thead><tbody>';
    if(r.length){
        r.forEach(x=>{
            var tn=tp.find(t=>t.id==x.type_document_id);
            h+='<tr>';
            h+='<td style="font-weight:500;">'+(tn?tn.n:'Tipo '+x.type_document_id)+'</td>';
            h+='<td><span style="background:#f1f5f9;padding:4px 10px;border-radius:6px;font-weight:700;">'+x.prefix+'</span></td>';
            h+='<td style="font-family:monospace;color:#475569;">'+(x.resolution||'-')+'</td>';
            h+='<td style="color:#64748b;font-size:12px;">'+(x.from||0).toLocaleString()+' - '+(x.to||0).toLocaleString()+'</td>';
            h+='<td><span class="badge-num">'+x.prefix+(x.next_consecutive||x.from||1)+'</span></td>';
            h+='<td style="color:#64748b;font-size:12px;">'+(x.date_from||'-')+'<br>al '+(x.date_to||'-')+'</td>';
            h+='<td style="text-align:center;"><button class="btn-sm-icon btn-sm-edit" onclick="editRes('+x.id+')"><i class="fa fa-edit"></i></button>';
            h+='<button class="btn-sm-icon btn-sm-del" onclick="delRes('+x.id+')"><i class="fa fa-trash"></i></button></td>';
            h+='</tr>';
        });
    }else{
        h+='<tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">No hay resoluciones configuradas</td></tr>';
    }
    h+='</tbody></table></div>';
    document.getElementById('t4').innerHTML=h;
}
function toggleNR(){document.getElementById('nrBox').classList.toggle('show');}
function chgDept(){document.getElementById('f10').innerHTML=optsMun(null,document.getElementById('f9').value);}
</script>

<script>
function saveT1(){
    fetch('/companies/'+cid,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({
        identification_number:document.getElementById('f1').value,
        dv:document.getElementById('f2').value,
        name:document.getElementById('f3').value,
        merchant_registration:document.getElementById('f4').value,
        address:document.getElementById('f5').value,
        phone:document.getElementById('f6').value,
        email:document.getElementById('f7').value,
        type_document_identification_id:document.getElementById('f8').value,
        municipality_id:document.getElementById('f10').value,
        type_organization_id:document.getElementById('f11').value,
        type_regime_id:document.getElementById('f12').value,
        type_liability_id:document.getElementById('f13').value
    })}).then(r=>r.json()).then(x=>{
        if(x.success){Swal.fire({icon:'success',title:'¡Guardado!',text:'Datos actualizados',confirmButtonColor:'#f97316'});loadData();}
        else Swal.fire({icon:'error',title:'Error',text:x.message,confirmButtonColor:'#f97316'});
    });
}
function saveT2(){
    fetch('/companies/'+cid+'/software',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({
        identifier:document.getElementById('s1').value,
        pin:document.getElementById('s2').value,
        url:document.getElementById('s3').value
    })}).then(r=>r.json()).then(x=>{
        if(x.success)Swal.fire({icon:'success',title:'¡Guardado!',confirmButtonColor:'#f97316'});
        else Swal.fire({icon:'error',title:'Error',text:x.message,confirmButtonColor:'#f97316'});
    });
}
function saveT3(){
    var f=document.getElementById('c1').files[0];
    if(!f){Swal.fire({icon:'warning',title:'Seleccione archivo',confirmButtonColor:'#f97316'});return;}
    var fd=new FormData();fd.append('certificate',f);fd.append('password',document.getElementById('c2').value);
    Swal.fire({title:'Subiendo...',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
    fetch('/companies/'+cid+'/certificate',{method:'POST',headers:{'X-CSRF-TOKEN':csrf()},body:fd})
    .then(r=>r.json()).then(x=>{
        if(x.success){Swal.fire({icon:'success',title:'¡Cargado!',confirmButtonColor:'#f97316'});fetch('/companies/'+cid+'/data').then(r=>r.json()).then(d=>{cdata=d;fillT3();});}
        else Swal.fire({icon:'error',title:'Error',text:x.message,confirmButtonColor:'#f97316'});
    });
}
function addRes(){
    fetch('/companies/'+cid+'/resolution',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({
        type_document_id:document.getElementById('nr1').value,
        prefix:document.getElementById('nr2').value,
        resolution:document.getElementById('nr3').value,
        from:document.getElementById('nr4').value||1,
        to:document.getElementById('nr5').value||5000,
        date_from:document.getElementById('nr6').value||new Date().toISOString().split('T')[0],
        date_to:document.getElementById('nr7').value||'2030-12-31',
        resolution_date:new Date().toISOString().split('T')[0]
    })}).then(r=>r.json()).then(x=>{
        if(x.success){Swal.fire({icon:'success',title:'¡Creada!',confirmButtonColor:'#f97316'});fetch('/companies/'+cid+'/data').then(r=>r.json()).then(d=>{cdata=d;fillT4();toggleNR();});}
        else Swal.fire({icon:'error',title:'Error',text:x.message,confirmButtonColor:'#f97316'});
    });
}
function delRes(id){
    Swal.fire({title:'¿Eliminar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',cancelButtonColor:'#64748b',confirmButtonText:'Sí',cancelButtonText:'No'}).then(r=>{
        if(r.isConfirmed)fetch('/companies/resolution/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf()}}).then(()=>{fetch('/companies/'+cid+'/data').then(r=>r.json()).then(d=>{cdata=d;fillT4();});});
    });
}
function editRes(id){
    var res=cdata.resolutions.find(r=>r.id==id);if(!res)return;
    Swal.fire({
        title:'Editar Resolución',
        html:`<div style="text-align:left;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Prefijo</label>
                <input id="ed_prefix" value="${res.prefix}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;"></div>
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Resolución</label>
                <input id="ed_res" value="${res.resolution||''}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px;">
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Desde</label>
                <input id="ed_from" type="number" value="${res.from||1}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;"></div>
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Hasta</label>
                <input id="ed_to" type="number" value="${res.to||5000}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;"></div>
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Actual</label>
                <input id="ed_next" type="number" value="${res.next_consecutive||res.from||1}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;background:#f0fdf4;"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Vigencia Desde</label>
                <input id="ed_dfrom" type="date" value="${res.date_from||''}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;"></div>
                <div><label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Vigencia Hasta</label>
                <input id="ed_dto" type="date" value="${res.date_to||''}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;"></div>
            </div>
            <div style="margin-top:12px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:4px;text-transform:uppercase;">Clave Técnica</label>
                <input id="ed_key" value="${res.technical_key||''}" style="width:100%;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;">
            </div>
        </div>`,
        width:520,showCancelButton:true,confirmButtonText:'Guardar',cancelButtonText:'Cancelar',confirmButtonColor:'#f97316',cancelButtonColor:'#64748b',
        preConfirm:()=>({prefix:document.getElementById('ed_prefix').value,resolution:document.getElementById('ed_res').value,from:document.getElementById('ed_from').value,to:document.getElementById('ed_to').value,next_consecutive:document.getElementById('ed_next').value,technical_key:document.getElementById('ed_key').value,date_from:document.getElementById('ed_dfrom').value,date_to:document.getElementById('ed_dto').value})
    }).then(result=>{
        if(result.isConfirmed){
            fetch('/companies/resolution/'+id,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(result.value)})
            .then(r=>r.json()).then(x=>{
                if(x.success){Swal.fire({icon:'success',title:'¡Actualizada!',confirmButtonColor:'#f97316'});fetch('/companies/'+cid+'/data').then(r=>r.json()).then(d=>{cdata=d;fillT4();});}
                else Swal.fire({icon:'error',title:'Error',text:x.message,confirmButtonColor:'#f97316'});
            });
        }
    });
}
function chgEnv(id,c){
    Swal.fire({title:'Cambiar Ambiente',text:'¿Cambiar a '+(c==1?'Habilitación':'Producción')+'?',icon:'question',showCancelButton:true,confirmButtonColor:'#f97316',confirmButtonText:'Sí'}).then(r=>{
        if(r.isConfirmed)fetch('/companies/'+id+'/environment',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({type_environment_id:c==1?2:1})}).then(()=>loadData());
    });
}
function chgState(id,c){
    Swal.fire({title:(c?'Deshabilitar':'Habilitar'),icon:'question',showCancelButton:true,confirmButtonColor:'#f97316',confirmButtonText:'Sí'}).then(r=>{
        if(r.isConfirmed)fetch('/companies/'+id+'/toggle-state',{method:'POST',headers:{'X-CSRF-TOKEN':csrf()}}).then(()=>loadData());
    });
}
function del(id,nit){
    Swal.fire({title:'Eliminar '+nit+'?',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Sí, eliminar'}).then(r=>{
        if(r.isConfirmed)fetch('/companies/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf()}}).then(()=>{Swal.fire({icon:'success',title:'Eliminada',confirmButtonColor:'#f97316'});loadData();});
    });
}
</script>
@endsection
