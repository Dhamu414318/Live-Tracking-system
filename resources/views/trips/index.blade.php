@extends('layouts.app')

@section('title', 'Trips')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Trip Management</h1>
            <p class="text-gray-600">View and manage vehicle trips</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="exportTrips()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
            <button onclick="refreshTrips()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-sync mr-2"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                <select id="deviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Devices</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <select id="dateFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week" selected>Last 7 Days</option>
                    <option value="month">Last 30 Days</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            
            <button onclick="filterTrips()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Filter
            </button>
        </div>
    </div>

    <!-- Trips Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Trips</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tripsTableBody">
                    @forelse($trips as $trip)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->device->name ?? 'Unknown Device' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->start_time ? $trip->start_time->format('M d, Y H:i') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->end_time ? $trip->end_time->format('M d, Y H:i') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->formatted_duration }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->formatted_distance }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->formatted_max_speed }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->formatted_avg_speed }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->formatted_start_location }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $trip->formatted_end_location }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $trip->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewTrip({{ $trip->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                View
                            </button>
                            <button onclick="showTripMap({{ $trip->id }})" class="text-green-600 hover:text-green-900">
                                Map
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-4 text-center text-gray-500">
                            No trips found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($trips->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $trips->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Trip Details Modal -->
<div id="tripModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Trip Details</h3>
                <button onclick="closeTripModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="tripModalContent">
                <!-- Trip details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Trip Map Modal -->
<div id="tripMapModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Trip Route</h3>
                <button onclick="closeTripMapModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="tripMap" class="h-96 w-full"></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
let tripMap = null;

// Filter trips
function filterTrips() {
    const deviceId = document.getElementById('deviceFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateRange = document.getElementById('dateFilter').value;
    
    // Show loading
    document.getElementById('tripsTableBody').innerHTML = '<tr><td colspan="11" class="px-6 py-4 text-center">Loading...</td></tr>';
    
    // Make AJAX request
    fetch(`/trips/filter?device_id=${deviceId}&status=${status}&date_range=${dateRange}`)
        .then(response => response.json())
        .then(data => {
            displayTrips(data);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('tripsTableBody').innerHTML = '<tr><td colspan="11" class="px-6 py-4 text-center text-red-500">Error loading trips</td></tr>';
        });
}

// Display trips
function displayTrips(trips) {
    const tbody = document.getElementById('tripsTableBody');
    tbody.innerHTML = '';
    
    if (trips.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="px-6 py-4 text-center text-gray-500">No trips found</td></tr>';
        return;
    }
    
    trips.forEach(trip => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.device_name || 'Unknown Device'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.start_time || 'N/A'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.end_time || 'N/A'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.formatted_duration}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.formatted_distance}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.formatted_max_speed}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.formatted_avg_speed}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.formatted_start_location}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trip.formatted_end_location}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${trip.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${trip.status.charAt(0).toUpperCase() + trip.status.slice(1)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewTrip(${trip.id})" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                <button onclick="showTripMap(${trip.id})" class="text-green-600 hover:text-green-900">Map</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// View trip details
function viewTrip(tripId) {
    fetch(`/trips/${tripId}`)
        .then(response => response.json())
        .then(trip => {
            document.getElementById('tripModalContent').innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-medium text-gray-900">Trip Information</h4>
                        <p><strong>Device:</strong> ${trip.device_name}</p>
                        <p><strong>Start Time:</strong> ${trip.start_time}</p>
                        <p><strong>End Time:</strong> ${trip.end_time || 'Active'}</p>
                        <p><strong>Duration:</strong> ${trip.formatted_duration}</p>
                        <p><strong>Distance:</strong> ${trip.formatted_distance}</p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">Speed Information</h4>
                        <p><strong>Max Speed:</strong> ${trip.formatted_max_speed}</p>
                        <p><strong>Average Speed:</strong> ${trip.formatted_avg_speed}</p>
                        <p><strong>Start Location:</strong> ${trip.formatted_start_location}</p>
                        <p><strong>End Location:</strong> ${trip.formatted_end_location}</p>
                        <p><strong>Status:</strong> ${trip.status}</p>
                    </div>
                </div>
            `;
            document.getElementById('tripModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading trip details');
        });
}

// Show trip map
function showTripMap(tripId) {
    fetch(`/trips/${tripId}/route`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('tripMapModal').classList.remove('hidden');
            
            // Initialize map
            setTimeout(() => {
                if (tripMap) {
                    tripMap.remove();
                }
                
                tripMap = L.map('tripMap').setView([40.7128, -74.0060], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(tripMap);
                
                // Add route points
                if (data.positions && data.positions.length > 0) {
                    const routePoints = data.positions.map(pos => [pos.latitude, pos.longitude]);
                    L.polyline(routePoints, {color: 'blue'}).addTo(tripMap);
                    
                    // Add start and end markers
                    if (data.positions.length > 0) {
                        const start = data.positions[0];
                        const end = data.positions[data.positions.length - 1];
                        
                        L.marker([start.latitude, start.longitude], {icon: L.divIcon({className: 'bg-green-500 w-4 h-4 rounded-full'})})
                            .addTo(tripMap)
                            .bindPopup('Start');
                            
                        L.marker([end.latitude, end.longitude], {icon: L.divIcon({className: 'bg-red-500 w-4 h-4 rounded-full'})})
                            .addTo(tripMap)
                            .bindPopup('End');
                    }
                    
                    // Fit map to route
                    tripMap.fitBounds(routePoints);
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading trip route');
        });
}

// Close modals
function closeTripModal() {
    document.getElementById('tripModal').classList.add('hidden');
}

function closeTripMapModal() {
    document.getElementById('tripMapModal').classList.add('hidden');
    if (tripMap) {
        tripMap.remove();
        tripMap = null;
    }
}

// Export trips
function exportTrips() {
    console.log('Exporting trips...');
}

// Refresh trips
function refreshTrips() {
    location.reload();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const tripModal = document.getElementById('tripModal');
    const tripMapModal = document.getElementById('tripMapModal');
    
    if (event.target === tripModal) {
        closeTripModal();
    }
    if (event.target === tripMapModal) {
        closeTripMapModal();
    }
}
</script>
@endpush
@endsection
