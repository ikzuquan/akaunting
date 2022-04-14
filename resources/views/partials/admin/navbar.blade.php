@stack('navbar_start')
<nav class="navbar navbar-top navbar-expand navbar-dark border-bottom">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            @stack('navbar_search')

            @can('read-common-search')
                <livewire:common.search />
            @endcan

            <ul class="navbar-nav align-items-center ml-md-auto">
                <li class="nav-item d-xl-none">
                    <div class="pr-3 sidenav-toggler sidenav-toggler-dark" data-action="sidenav-pin" data-target="#sidenav-main">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </div>
                </li>

                @stack('navbar_create')

                <li class="nav-item d-sm-none">
                    <a class="nav-link" href="#" data-action="search-show" data-target="#navbar-search-main">
                        <i class="fa fa-search"></i>
                    </a>
                </li>

                @stack('navbar_notifications')

                @stack('navbar_updates')

                @stack('navbar_help_start')

                @stack('navbar_help_end')
            </ul>

            @stack('navbar_profile')

            <ul class="navbar-nav align-items-center ml-auto ml-md-0">
                <li class="nav-item dropdown">
                    <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <div class="media align-items-center">
                            @if (setting('default.use_gravatar', '0') == '1')
                                <img src="{{ $user->picture }}" alt="{{ $user->name }}" class="rounded-circle image-style user-img" title="{{ $user->name }}">
                            @elseif (is_object($user->picture))
                                <img src="{{ Storage::url($user->picture->id) }}" class="rounded-circle image-style user-img" alt="{{ $user->name }}" title="{{ $user->name }}">
                            @else
                                <img src="{{ asset('public/img/user.svg') }}" class="user-img" alt="{{ $user->name }}"/>
                            @endif
                            @if (!empty($user->name))
                                <div class="media-body ml-2 d-none d-lg-block">
                                    <span class="mb-0 text-sm font-weight-bold">{{ $user->name }}</span>
                                </div>
                            @endif
                        </div>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right">
                        @stack('navbar_profile_welcome')

                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">{{ trans('general.welcome') }}</h6>
                        </div>

                        @stack('navbar_profile_edit')

                        @canany(['read-auth-users', 'read-auth-profile'])
                            <a href="{{ route('users.edit', $user->id) }}" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>{{ trans('auth.profile') }}</span>
                            </a>
                        @endcanany
                        
                        @stack('navbar_profile_edit_end')

                        @canany(['read-auth-users', 'read-auth-roles', 'read-auth-permissions'])
                            <div class="dropdown-divider"></div>

                            @stack('navbar_profile_users')

                            @can('read-auth-users')
                                <a href="{{ route('users.index') }}" class="dropdown-item">
                                    <i class="fas fa-users"></i>
                                    <span>{{ trans_choice('general.users', 2) }}</span>
                                </a>
                            @endcan

                            @stack('navbar_profile_roles')

                            @can('read-auth-roles')
                                <a href="{{ route('roles.index') }}" class="dropdown-item">
                                    <i class="fas fa-list"></i>
                                    <span>{{ trans_choice('general.roles', 2) }}</span>
                                </a>
                            @endcan

                            @stack('navbar_profile_permissions_start')

                            @can('read-auth-permissions')
                                <a href="{{ route('permissions.index') }}" class="dropdown-item">
                                    <i class="fas fa-key"></i>
                                    <span>{{ trans_choice('general.permissions', 2) }}</span>
                                </a>
                            @endcan

                            @stack('navbar_profile_permissions_end')
                        @endcanany

                        <div class="dropdown-divider"></div>

                        @stack('navbar_profile_logout_start')

                        <a href="{{ route('logout') }}" class="dropdown-item">
                            <i class="fas fa-power-off"></i>
                            <span>{{ trans('auth.logout') }}</span>
                        </a>

                        @stack('navbar_profile_logout_end')
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
@stack('navbar_end')
