@extends('layouts.app')

@section('title', 'Devices')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Devices</h1>
            <p class="text-gray-600">Manage your GPS tracking devices</p>
        </div>
        <a href="{{ route('devices.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Add Device
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-mobile-alt text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Devices</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $devices->count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-circle text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Online</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $devices->where('status', 'online')->count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <i class="fas fa-circle text-red-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Offline</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $devices->where('status', 'offline')->count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-route text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Moving</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $devices->where('last_speed', '>', 0)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select id="userFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="searchFilter" placeholder="Search devices..." 
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <!-- Devices Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Device List</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Update</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($devices as $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-mobile-alt text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $device->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $device->unique_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $device->status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                <span class="w-2 h-2 rounded-full mr-1 {{ $device->status === 'online' ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ ucfirst($device->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($device->last_lat && $device->last_lng)
                                {{ number_format($device->last_lat, 4) }}, {{ number_format($device->last_lng, 4) }}
                            @else
                                <span class="text-gray-500">No location</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($device->last_speed)
                                {{ number_format($device->last_speed, 1) }} km/h
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($device->last_update_time)
                                {{ $device->last_update_time->diffForHumans() }}
                            @else
                                <span class="text-gray-500">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $device->user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('devices.show', $device) }}" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('devices.edit', $device) }}" class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('devices.positions', $device) }}" class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-map-marker-alt"></i>
                                </a>
                                <form method="POST" action="{{ route('devices.destroy', $device) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" 
                                            onclick="return confirm('Are you sure you want to delete this device?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No devices found. <a href="{{ route('devices.create') }}" class="text-blue-600 hover:text-blue-500">Add your first device</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Map View -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Device Locations</h3>
        </div>
        <div id="devicesMap" class="h-96 w-full"></div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
// Initialize map
let map = L.map('devicesMap').setView([40.7128, -74.0060], 10);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add device markers
@foreach($devices as $device)
    @if($device->last_lat && $device->last_lng)
        const marker{{ $device->id }} = L.marker([{{ $device->last_lat }}, {{ $device->last_lng }}])
            .addTo(map)
            .bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold">{{ $device->name }}</h3>
                    <p class="text-sm">{{ $device->unique_id }}</p>
                    <p class="text-sm">Status: {{ ucfirst($device->status) }}</p>
                    @if($device->last_speed)
                        <p class="text-sm">Speed: {{ number_format($device->last_speed, 1) }} km/h</p>
                    @endif
                    <a href="{{ route('devices.show', $device) }}" class="text-blue-600 text-sm">View Details</a>
                </div>
            `);
    @endif
@endforeach

// Filter functionality
document.getElementById('statusFilter').addEventListener('change', filterDevices);
document.getElementById('userFilter').addEventListener('change', filterDevices);
document.getElementById('searchFilter').addEventListener('input', filterDevices);

function filterDevices() {
    const statusFilter = document.getElementById('statusFilter').value;
    const userFilter = document.getElementById('userFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const status = row.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
        const deviceName = row.querySelector('td:nth-child(1) .text-gray-900').textContent.toLowerCase();
        const deviceId = row.querySelector('td:nth-child(1) .text-gray-500').textContent.toLowerCase();
        
        const statusMatch = !statusFilter || status.includes(statusFilter);
        const searchMatch = !searchFilter || deviceName.includes(searchFilter) || deviceId.includes(searchFilter);
        
        row.style.display = statusMatch && searchMatch ? '' : 'none';
    });
}
</script>
@endpush
@endsection 