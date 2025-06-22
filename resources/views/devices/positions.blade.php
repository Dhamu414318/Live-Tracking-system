@extends('layouts.app')

@section('title', $device->name . ' - Positions')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $device->name }} - Position History</h1>
            <p class="text-gray-600">Track all position updates for this device</p>
        </div>
        <a href="{{ route('devices.show', $device) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
            <i class="fas fa-arrow-left mr-2"></i>Back to Device
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-map-marker-alt text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Positions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $positions->total() }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-route text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Distance</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($positions->sum('distance'), 1) }} km</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-tachometer-alt text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Avg Speed</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($positions->avg('speed'), 1) }} km/h</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-clock text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Last Update</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $device->last_update_time ? $device->last_update_time->diffForHumans() : 'Never' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <select id="dateFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Min Speed</label>
                <input type="number" id="minSpeed" placeholder="0" 
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Speed</label>
                <input type="number" id="maxSpeed" placeholder="200" 
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            
            <button onclick="filterPositions()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Filter
            </button>
        </div>
    </div>

    <!-- Positions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Position History</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heading</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Altitude</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Battery</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($positions as $position)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->timestamp->format('M d, H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($position->latitude, 6) }}, {{ number_format($position->longitude, 6) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->speed ? number_format($position->speed, 1) . ' km/h' : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->course ? number_format($position->course, 0) . '°' : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($position->distance, 2) }} km
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->altitude ? number_format($position->altitude, 0) . ' m' : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->battery_level ? $position->battery_level . '%' : 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No positions found for this device
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($positions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $positions->links() }}
            </div>
        @endif
    </div>

    <!-- Map View -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Position Map</h3>
        </div>
        <div id="positionsMap" class="h-96 w-full"></div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
// Initialize map
let map = L.map('positionsMap').setView([{{ $device->last_lat ?? 40.7128 }}, {{ $device->last_lng ?? -74.0060 }}], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add position markers
@foreach($positions as $position)
    const marker{{ $position->id }} = L.marker([{{ $position->latitude }}, {{ $position->longitude }}])
        .addTo(map)
        .bindPopup(`
            <div class="p-2">
                <h3 class="font-bold">{{ $device->name }}</h3>
                <p class="text-sm">Time: {{ $position->timestamp->format('M d, H:i:s') }}</p>
                <p class="text-sm">Speed: {{ $position->speed ? number_format($position->speed, 1) . ' km/h' : 'N/A' }}</p>
                <p class="text-sm">Distance: {{ number_format($position->distance, 2) }} km</p>
            </div>
        `);
@endforeach

// Filter functionality
function filterPositions() {
    const dateFilter = document.getElementById('dateFilter').value;
    const minSpeed = document.getElementById('minSpeed').value;
    const maxSpeed = document.getElementById('maxSpeed').value;
    
    // Implementation for filtering positions
    console.log('Filtering positions...', { dateFilter, minSpeed, maxSpeed });
}
</script>
@endpush
@endsection 