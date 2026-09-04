<header>
    <div class="border-b site-header">
        <div class="py-4 site-header__identity">
            <div class="container site-header__identity-container">
                <div class="flex flex-wrap w-full items-center justify-between">
                    <div class="lg:w-1/6 md:w-1/2 w-2/5 site-header__logo">
                        <a class="navbar-brand" href="{{ route('inicio') }}">
                            <img src="{{ asset('pagina_web/images/logo/freshcart-logo.svg') }}"
                                alt="FreshCart" />
                        </a>
                    </div>

                    <div class="lg:w-1/6 text-end md:w-1/2 w-3/5 site-header__actions">
                        <div class="flex items-center justify-end">
                            <a href="{{ Route::has('login') ? route('login') : '#!' }}"
                                class="btn inline-flex items-center gap-x-2 bg-green-600 text-white border-green-600 hover:text-white hover:bg-green-700 hover:border-green-700 active:bg-green-700 active:border-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 site-header__login"
                                title="Iniciar sesión">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user"
                                    width="20" height="20" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                </svg>
                                <span class="site-header__login-label">Iniciar sesión</span>
                            </a>

                            <div class="lg:hidden leading-none">
                                <button class="collapsed" type="button" data-bs-toggle="offcanvas"
                                    data-bs-target="#navbar-default" aria-controls="navbar-default"
                                    aria-label="Abrir menú de navegación">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-menu-2 text-gray-800" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 6l16 0" />
                                        <path d="M4 12l16 0" />
                                        <path d="M4 18l16 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <nav class="navbar relative navbar-expand-lg lg:flex lg:flex-wrap items-center content-between text-black navbar-default site-header__nav"
            aria-label="Navegación principal">
            <div class="container max-w-7xl mx-auto w-full xl:px-4 lg:px-0">
                <div class="offcanvas offcanvas-left lg:visible" tabindex="-1" id="navbar-default">
                    <div class="offcanvas-header pb-1">
                        <a href="{{ route('inicio') }}">
                            <img src="{{ asset('pagina_web/images/logo/freshcart-logo.svg') }}"
                                alt="FreshCart" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            aria-label="Cerrar menú de navegación">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="icon icon-tabler icon-tabler-x text-gray-700" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M18 6l-12 12" />
                                <path d="M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="offcanvas-body lg:flex lg:items-center">
                        <a href="#productos"
                            class="mr-4 btn inline-flex items-center gap-x-2 bg-green-600 text-white border-green-600 hover:text-white hover:bg-green-700 hover:border-green-700 active:bg-green-700 active:border-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 site-header__products">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="icon icon-tabler icon-tabler-layout-grid" width="16" height="16"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 4h6v6h-6z" />
                                <path d="M14 4h6v6h-6z" />
                                <path d="M4 14h6v6h-6z" />
                                <path d="M14 14h6v6h-6z" />
                            </svg>
                            Nuestros productos
                        </a>

                        <ul class="navbar-nav lg:flex gap-3 lg:items-center">
                            <li class="nav-item w-full lg:w-auto">
                                <a class="nav-link" href="{{ route('inicio') }}">Inicio</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>
