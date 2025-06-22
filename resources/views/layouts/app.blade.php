<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'GPS Tracking System') }} - @yield('title', 'Dashboard')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        
        .map-marker {
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            background: #c30b82;
            position: absolute;
            transform: rotate(-45deg);
            left: 50%;
            top: 50%;
            margin: -15px 0 0 -15px;
        }
        
        .map-marker.online {
            background: #28a745;
        }
        
        .map-marker.offline {
            background: #dc3545;
        }
        
        .map-marker.moving {
            background: #ffc107;
        }
        
        .map-marker::after {
            content: '';
            width: 24px;
            height: 24px;
            margin: 3px 0 0 3px;
            background: #fff;
            position: absolute;
            border-radius: 50%;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 sidebar-transition transform -translate-x-full lg:translate-x-0">
        <div class="flex items-center justify-between h-16 px-6 bg-gray-800">
            <div class="flex items-center">
                <i class="fas fa-satellite text-blue-400 text-xl mr-3"></i>
                <h1 class="text-white text-lg font-semibold">GPS Tracker</h1>
            </div>
            <button id="sidebar-close" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="mt-6">
            <div class="px-4 mb-4">
                <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Main</div>
            </div>
            
            <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="{{ route('devices.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('devices.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-mobile-alt w-5 h-5 mr-3"></i>
                <span>Devices</span>
            </a>
            
            <a href="{{ route('map.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('map.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-map-marked-alt w-5 h-5 mr-3"></i>
                <span>Live Map</span>
            </a>
            
            <a href="{{ route('geofences.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('geofences.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-draw-polygon w-5 h-5 mr-3"></i>
                <span>Geofences</span>
            </a>
            
            <div class="px-4 mt-6 mb-4">
                <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Reports</div>
            </div>
            
            <a href="{{ route('alerts.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('alerts.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-bell w-5 h-5 mr-3"></i>
                <span>Alerts</span>
                @if(auth()->check() && auth()->user()->getUnreadAlertsCount() > 0)
                    <span class="notification-badge">{{ auth()->user()->getUnreadAlertsCount() }}</span>
                @endif
            </a>
            
            <a href="{{ route('trips.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('trips.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-route w-5 h-5 mr-3"></i>
                <span>Trips</span>
            </a>
            
            <a href="{{ route('reports.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('reports.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-chart-bar w-5 h-5 mr-3"></i>
                <span>Reports</span>
            </a>
            
            @if(auth()->check() && auth()->user()->isAdmin())
            <div class="px-4 mt-6 mb-4">
                <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Admin</div>
            </div>
            
            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-cog w-5 h-5 mr-3"></i>
                <span>Admin Panel</span>
            </a>
            
            <a href="{{ route('admin.users') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-users w-5 h-5 mr-3"></i>
                <span>Users</span>
            </a>
            @endif
            
            <div class="px-4 mt-6 mb-4">
                <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Settings</div>
            </div>
            
            <a href="{{ route('settings.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('settings.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-cogs w-5 h-5 mr-3"></i>
                <span>Settings</span>
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="lg:hidden text-gray-500 hover:text-gray-700 mr-4">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-900">@yield('title', 'Dashboard')</h2>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-gray-500 hover:text-gray-700 relative">
                            <i class="fas fa-bell text-lg"></i>
                            @if(auth()->check() && auth()->user()->getUnreadAlertsCount() > 0)
                                <span class="notification-badge">{{ auth()->user()->getUnreadAlertsCount() }}</span>
                            @endif
                        </button>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center text-gray-700 hover:text-gray-900">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                @if(auth()->check())
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                @endif
                            </div>
                            <span class="ml-2 hidden sm:block">
                                @if(auth()->check())
                                    {{ auth()->user()->name }}
                                @endif
                            </span>
                            <i class="fas fa-chevron-down ml-1 text-sm"></i>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-2"></i>Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="p-4 sm:p-6 lg:p-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @yield('content')
        </main>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Custom Scripts -->
    <script>
        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
        
        document.getElementById('sidebar-close').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });
        
        // Auto-refresh for real-time updates
        setInterval(function() {
            // Refresh unread alerts count
            fetch('/api/unread-alerts-count')
                .then(response => response.json())
                .then(data => {
                    const badges = document.querySelectorAll('.notification-badge');
                    badges.forEach(badge => {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    });
                })
                .catch(error => console.error('Error fetching alerts count:', error));
        }, 30000); // Every 30 seconds
    </script>
    
    @stack('scripts')
</body>
</html>
