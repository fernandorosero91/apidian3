@php
$path = explode('/', request()->path());
$path[1] = (array_key_exists(1, $path)> 0)?$path[1]:'';
$path[2] = (array_key_exists(2, $path)> 0)?$path[2]:'';
$path[0] = ($path[0] === '')?'documents':$path[0];
$comp_id = $path[1];
$cust_id = $path[2];
@endphp

<aside id="sidebar-left" class="sidebar-left">
    <div class="sidebar-header">
        <div class="sidebar-title">
            <span class="sidebar-brand">APIDIAN</span>
        </div>
        <div class="sidebar-toggle d-none d-md-block" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle">
            <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
        </div>
    </div>
    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">
                    @if(isset(Auth::user()->email))
                        <li class="{{ ($path[0] === 'documents' || $path[0] === 'dashboard')?'nav-active':'' }}">
                            <a class="nav-link" href="{{route('documents_index')}}">
                                <i class="fas fa-file-alt" aria-hidden="true"></i>
                                <span>Documentos</span>
                            </a>
                        </li>
                        <li class="{{ ($path[0] === 'taxes')?'nav-active':'' }}">
                            <a class="nav-link" href="{{route('tax_index')}}">
                                <i class="fas fa-percent" aria-hidden="true"></i>
                                <span>Impuestos</span>
                            </a>
                        </li>
                        <li class="nav-parent {{ in_array($path[0], ['configuration', 'companies'])?'nav-active nav-expanded':'' }}">
                            <a class="nav-link" href="#">
                                <i class="fas fa-building" aria-hidden="true"></i>
                                <span>Empresas</span>
                            </a>
                            <ul class="nav nav-children">
                                <li class="{{ (request()->routeIs('companies_index') || $path[0] === 'companies')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('companies_index')}}">
                                        <span>Gestionar Empresas</span>
                                    </a>
                                </li>
                                <li class="{{ (request()->routeIs('configuration_admin'))?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('configuration_admin')}}">
                                        <span>Nueva Empresa</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="{{ ($path[0] === 'dashboard')?'nav-active':'nav-active' }}">
                            <form action="{{ url('/okcustomerlogin/'.$comp_id.'/'.$cust_id) }}" method="POST">
                                @csrf
                                <a class="nav-link" href="javascript:;" onclick="$('#action-button').click();">
                                    <input type="hidden" name="verificar" value="FALSE"/>
                                    <i class="fas fa-inbox" aria-hidden="true"></i>
                                    <span>Documentos Recibidos</span>
                                </a>
                                <input type="submit" id="action-button" style="display: none;" >
                            </form>
                        </li>
                        <li>
                            <form action="{{ url('/customer-password/'.$comp_id.'/'.$cust_id) }}" method="GET">
                                @csrf
                                <a class="nav-link" href="javascript:;" onclick="$('#action-button2').click();">
                                    <i class="fas fa-key" aria-hidden="true"></i>
                                    <span>Cambiar Password</span>
                                </a>
                                <input type="submit" id="action-button2" style="display: none;" >
                            </form>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
        <script>
            if (typeof localStorage !== 'undefined') {
                if (localStorage.getItem('sidebar-left-position') !== null) {
                    var initialPosition = localStorage.getItem('sidebar-left-position'),
                        sidebarLeft = document.querySelector('#sidebar-left .nano-content');
                    sidebarLeft.scrollTop = initialPosition;
                }
            }
        </script>
    </div>
</aside>

<style>
/* Sidebar Brand */
.sidebar-brand {
    font-size: 1.3rem;
    font-weight: 700;
    color: #f97316;
    letter-spacing: 1px;
}

/* Nav Links */
ul.nav-main > li > a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #94a3b8;
    transition: all 0.2s;
}

ul.nav-main > li > a i {
    width: 20px;
    margin-right: 12px;
    font-size: 15px;
}

ul.nav-main > li > a:hover {
    color: #ffffff;
    background: rgba(255,255,255,0.05);
}

ul.nav-main > li.nav-active > a {
    color: #f97316;
    background: rgba(249, 115, 22, 0.1);
    border-left: 3px solid #f97316;
}

/* Children */
ul.nav-main ul.nav-children {
    background: rgba(0,0,0,0.15);
    padding: 5px 0;
}

ul.nav-main ul.nav-children > li > a {
    padding: 10px 20px 10px 52px;
    font-size: 13px;
}

ul.nav-main ul.nav-children > li.nav-active > a {
    color: #f97316;
}

/* Collapsed state fix */
html.sidebar-left-collapsed .sidebar-brand {
    display: none;
}

html.sidebar-left-collapsed ul.nav-main > li > a span {
    display: none;
}

html.sidebar-left-collapsed ul.nav-main > li > a {
    justify-content: center;
    padding: 15px;
}

html.sidebar-left-collapsed ul.nav-main > li > a i {
    margin-right: 0;
    font-size: 18px;
}

html.sidebar-left-collapsed ul.nav-main ul.nav-children {
    display: none;
}

html.sidebar-left-collapsed .sidebar-header {
    justify-content: center;
}

html.sidebar-left-collapsed .sidebar-toggle {
    display: none !important;
}
</style>
