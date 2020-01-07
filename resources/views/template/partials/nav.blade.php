<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <img src="{{ asset('images/logo.gif')}}" class="navbar-brand">
    </div>
 
    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="{{ route('ats') }}">ATS</a></li>

      </ul>
      <ul class="nav navbar-nav">
            &nbsp; <li class="dropdown">
                <!-- Right Side Of Navbar -->
           <ul class="nav navbar-nav navbar-right">
               <!-- Authentication Links -->
                  <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            Facturas Proveedores<span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="{{ route('validar.facturas') }}">Validar Facturas SRI</a></li>
                        </ul>
                  </li>
                </ul>
            </li>
        </ul>

        <ul class="nav navbar-nav">
            &nbsp; <li class="dropdown">
                <!-- Right Side Of Navbar -->
                <ul class="nav navbar-nav navbar-right">
                    <!-- Authentication Links -->
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                Documentos Electrónicos<span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ route('facturacion.electronica') }}">Facturación</a></li>
                                <li><a href="{{ route('facturacion.electronicavt') }}">Facturación Ventas a Teceros</a></li>
                            </ul>
                        </li>
                </ul>
            </li>

        </ul>
</li>
</ul>
<!-- Left Side Of Navbar -->
        <ul class="nav navbar-nav">            &nbsp;
        </ul>
        <ul class="nav navbar-nav navbar-right">        
            <!-- Authentication Links -->
            @if (Auth::guest())
                <li><a href="{{ route('login') }}">Login</a></li>
                <li><a href="{{ route('register') }}">Register</a></li>
            @else
                <li class="dropdown">                   
                     <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Register</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        </ul>
         

    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>