@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">GPS Tracking Dashboard</h1>
                    <p class="text-sm text-gray-600">Real-time vehicle tracking and monitoring</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Last Update</div>
                        <div class="text-lg font-semibold text-gray-900" id="lastUpdate">Just now</div>
                    </div>
                    <button onclick="refreshData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Devices -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Devices</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_devices'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Online Devices -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Online Devices</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['online_devices'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Moving Devices -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Moving</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['moving_devices'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Unread Alerts</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['unread_alerts'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Map Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Live Tracking Map</h2>
                    </div>
                    <div class="p-4">
                        <div id="map" class="w-full h-96 rounded-lg border"></div>
                    </div>
                </div>
            </div>

            <!-- Device List -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Devices</h2>
                    </div>
                    <div class="p-4">
                        <div id="deviceList" class="space-y-3">
                            @foreach($devices as $device)
                            <div class="device-item border rounded-lg p-3 cursor-pointer hover:bg-gray-50" data-device-id="{{ $device->id }}">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $device->name }}</h3>
                                        <p class="text-sm text-gray-600">{{ $device->unique_id }}</p>
                                        <div class="flex items-center mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $device->status === 'online' ? 'bg-green-100 text-green-800' : 
                                                   ($device->status === 'offline' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($device->status) }}
                                            </span>
                                            @if($device->last_speed > 0)
                                            <span class="ml-2 text-sm text-gray-600">{{ number_format($device->last_speed, 1) }} km/h</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($device->battery_level)
                                        <div class="text-sm text-gray-600">{{ number_format($device->battery_level, 1) }}%</div>
                                        @endif
                                        @if($device->last_update_time)
                                        <div class="text-xs text-gray-500">{{ $device->last_update_time->diffForHumans() }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent Alerts -->
                <div class="bg-white rounded-lg shadow mt-6">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Alerts</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3">
                            @foreach($recentAlerts as $alert)
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-2 h-2 rounded-full {{ $alert->is_read ? 'bg-gray-400' : 'bg-red-500' }}"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $alert->title }}</p>
                                    <p class="text-sm text-gray-600">{{ Str::limit($alert->message, 50) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $alert->triggered_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map;
let markers = {};
let deviceData = @json($devices);

// Initialize map
function initMap() {
    map = L.map('map').setView([40.7128, -74.0060], 10);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add device markers
    deviceData.forEach(device => {
        if (device.last_lat && device.last_lng) {
            addDeviceMarker(device);
        }
    });
}

// Add device marker to map
function addDeviceMarker(device) {
    const icon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="marker-pin ${device.status}"></div>`,
        iconSize: [30, 42],
        iconAnchor: [15, 42]
    });

    const marker = L.marker([device.last_lat, device.last_lng], { icon: icon })
        .addTo(map)
        .bindPopup(`
            <div class="p-2">
                <h3 class="font-bold">${device.name}</h3>
                <p class="text-sm">${device.unique_id}</p>
                <p class="text-sm">Status: ${device.status}</p>
                ${device.last_speed ? `<p class="text-sm">Speed: ${device.last_speed} km/h</p>` : ''}
                ${device.battery_level ? `<p class="text-sm">Battery: ${device.battery_level}%</p>` : ''}
            </div>
        `);

    markers[device.id] = marker;
}

// Refresh data
function refreshData() {
    fetch('/api/live-updates')
        .then(response => response.json())
        .then(data => {
            updateDeviceList(data);
            updateMarkers(data);
            document.getElementById('lastUpdate').textContent = 'Just now';
        })
        .catch(error => {
            console.error('Error refreshing data:', error);
        });
}

// Update device list
function updateDeviceList(devices) {
    const deviceList = document.getElementById('deviceList');
    deviceList.innerHTML = '';

    devices.forEach(device => {
        const deviceHtml = `
            <div class="device-item border rounded-lg p-3 cursor-pointer hover:bg-gray-50" data-device-id="${device.id}">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-gray-900">${device.name}</h3>
                        <p class="text-sm text-gray-600">${device.unique_id}</p>
                        <div class="flex items-center mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                ${device.status === 'online' ? 'bg-green-100 text-green-800' : 
                                   (device.status === 'offline' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')}">
                                ${device.status.charAt(0).toUpperCase() + device.status.slice(1)}
                            </span>
                            ${device.last_speed > 0 ? `<span class="ml-2 text-sm text-gray-600">${device.last_speed} km/h</span>` : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        ${device.battery_level ? `<div class="text-sm text-gray-600">${device.battery_level}%</div>` : ''}
                        <div class="text-xs text-gray-500">Just now</div>
                    </div>
                </div>
            </div>
        `;
        deviceList.innerHTML += deviceHtml;
    });
}

// Update markers on map
function updateMarkers(devices) {
    devices.forEach(device => {
        if (device.last_lat && device.last_lng) {
            if (markers[device.id]) {
                markers[device.id].setLatLng([device.last_lat, device.last_lng]);
            } else {
                addDeviceMarker(device);
            }
        }
    });
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});

// Add CSS for markers
const style = document.createElement('style');
style.textContent = `
    .marker-pin {
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
    .marker-pin.online {
        background: #28a745;
    }
    .marker-pin.offline {
        background: #dc3545;
    }
    .marker-pin.maintenance {
        background: #ffc107;
    }
    .marker-pin::after {
        content: '';
        width: 24px;
        height: 24px;
        margin: 3px 0 0 3px;
        background: #fff;
        position: absolute;
        border-radius: 50%;
    }
`;
document.head.appendChild(style);
</script>
@endsection
