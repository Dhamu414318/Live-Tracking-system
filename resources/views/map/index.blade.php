@extends('layouts.app')

@section('title', 'Live Map')

@section('content')
<div class="h-screen flex flex-col">
    <!-- Map Controls -->
    <div class="bg-white shadow-sm border-b border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-semibold text-gray-900">Live Tracking Map</h1>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                        Online
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <span class="w-2 h-2 bg-red-400 rounded-full mr-1"></span>
                        Offline
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <span class="w-2 h-2 bg-yellow-400 rounded-full mr-1"></span>
                        Moving
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Device Filter -->
                <select id="deviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Devices</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->name }}</option>
                    @endforeach
                </select>
                
                <!-- Geofence Toggle -->
                <label class="flex items-center">
                    <input type="checkbox" id="showGeofences" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Show Geofences</span>
                </label>
                
                <!-- Refresh Button -->
                <button onclick="refreshMap()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
                
                <!-- Export Button -->
                <button onclick="exportTrack()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
            </div>
        </div>
    </div>
    
    <!-- Map Container -->
    <div class="flex-1 relative">
        <div id="map" class="w-full h-full"></div>
        
        <!-- Device Info Panel -->
        <div id="devicePanel" class="absolute top-4 right-4 bg-white rounded-lg shadow-lg p-4 w-80 max-h-96 overflow-y-auto hidden">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Device Info</h3>
                <button onclick="closeDevicePanel()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="deviceInfo"></div>
        </div>
        
        <!-- History Controls -->
        <div class="absolute bottom-4 left-4 bg-white rounded-lg shadow-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Track History</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                    <select id="historyDevice" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="historyDate" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" value="{{ today()->format('Y-m-d') }}">
                </div>
                <div class="flex space-x-2">
                    <button onclick="showHistory()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm">
                        Show Track
                    </button>
                    <button onclick="clearHistory()" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                        Clear
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let map;
let markers = {};
let geofenceLayers = {};
let historyLayers = {};
let deviceData = @json($devices);
let geofenceData = @json($geofences);

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

    // Add geofences
    geofenceData.forEach(geofence => {
        addGeofence(geofence);
    });
}

// Add device marker to map
function addDeviceMarker(device) {
    const icon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="map-marker ${device.status}"></div>`,
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
                <button onclick="showDeviceInfo(${device.id})" class="mt-2 bg-blue-600 text-white px-2 py-1 rounded text-xs">
                    Details
                </button>
            </div>
        `);

    markers[device.id] = marker;
}

// Add geofence to map
function addGeofence(geofence) {
    let layer;
    
    if (geofence.area_type === 'circle') {
        const center = geofence.coordinates.center;
        layer = L.circle([center.lat, center.lng], {
            radius: geofence.coordinates.radius,
            color: geofence.color,
            fillColor: geofence.color,
            fillOpacity: 0.2,
            weight: 2
        });
    } else if (geofence.area_type === 'polygon') {
        const coordinates = geofence.coordinates.polygon.map(point => [point.lat, point.lng]);
        layer = L.polygon(coordinates, {
            color: geofence.color,
            fillColor: geofence.color,
            fillOpacity: 0.2,
            weight: 2
        });
    }
    
    if (layer) {
        layer.addTo(map);
        layer.bindPopup(`
            <div class="p-2">
                <h3 class="font-bold">${geofence.name}</h3>
                <p class="text-sm">${geofence.description}</p>
                <p class="text-sm">Type: ${geofence.area_type}</p>
                <button onclick="showGeofenceDevices(${geofence.id})" class="mt-2 bg-blue-600 text-white px-2 py-1 rounded text-xs">
                    View Devices
                </button>
            </div>
        `);
        
        geofenceLayers[geofence.id] = layer;
    }
}

// Show device information panel
function showDeviceInfo(deviceId) {
    const device = deviceData.find(d => d.id === deviceId);
    if (!device) return;
    
    const panel = document.getElementById('devicePanel');
    const info = document.getElementById('deviceInfo');
    
    info.innerHTML = `
        <div class="space-y-3">
            <div>
                <h4 class="font-semibold text-gray-900">${device.name}</h4>
                <p class="text-sm text-gray-600">${device.unique_id}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="text-sm text-gray-500">Status</span>
                    <p class="font-medium">${device.status}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Last Update</span>
                    <p class="font-medium">${device.last_update_time ? new Date(device.last_update_time).toLocaleString() : 'Never'}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Speed</span>
                    <p class="font-medium">${device.last_speed || 0} km/h</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Battery</span>
                    <p class="font-medium">${device.battery_level || 'N/A'}%</p>
                </div>
            </div>
            
            <div>
                <span class="text-sm text-gray-500">Location</span>
                <p class="font-medium">${device.last_lat}, ${device.last_lng}</p>
            </div>
            
            <div class="flex space-x-2">
                <button onclick="centerOnDevice(${device.id})" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                    Center
                </button>
                <button onclick="showDeviceHistory(${device.id})" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                    History
                </button>
            </div>
        </div>
    `;
    
    panel.classList.remove('hidden');
}

// Close device panel
function closeDevicePanel() {
    document.getElementById('devicePanel').classList.add('hidden');
}

// Center map on device
function centerOnDevice(deviceId) {
    const device = deviceData.find(d => d.id === deviceId);
    if (device && device.last_lat && device.last_lng) {
        map.setView([device.last_lat, device.last_lng], 15);
    }
}

// Show device history
function showDeviceHistory(deviceId) {
    const date = document.getElementById('historyDate').value;
    
    fetch(`/api/device-history?device_id=${deviceId}&date=${date}`)
        .then(response => response.json())
        .then(positions => {
            clearHistory();
            
            if (positions.length > 1) {
                const coordinates = positions.map(pos => [pos.latitude, pos.longitude]);
                const polyline = L.polyline(coordinates, {
                    color: 'blue',
                    weight: 3,
                    opacity: 0.7
                }).addTo(map);
                
                historyLayers[deviceId] = polyline;
                
                // Add start and end markers
                const startMarker = L.marker(coordinates[0], {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: '<div class="w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(map);
                
                const endMarker = L.marker(coordinates[coordinates.length - 1], {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: '<div class="w-4 h-4 bg-red-500 rounded-full border-2 border-white"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(map);
                
                historyLayers[`${deviceId}_start`] = startMarker;
                historyLayers[`${deviceId}_end`] = endMarker;
            }
        })
        .catch(error => console.error('Error fetching device history:', error));
}

// Show history for selected device and date
function showHistory() {
    const deviceId = document.getElementById('historyDevice').value;
    const date = document.getElementById('historyDate').value;
    
    if (deviceId) {
        showDeviceHistory(deviceId);
    }
}

// Clear history layers
function clearHistory() {
    Object.values(historyLayers).forEach(layer => {
        map.removeLayer(layer);
    });
    historyLayers = {};
}

// Refresh map data
function refreshMap() {
    fetch('/api/live-updates')
        .then(response => response.json())
        .then(devices => {
            devices.forEach(device => {
                if (device.last_lat && device.last_lng) {
                    if (markers[device.id]) {
                        markers[device.id].setLatLng([device.last_lat, device.last_lng]);
                    } else {
                        addDeviceMarker(device);
                    }
                }
            });
        })
        .catch(error => console.error('Error refreshing map:', error));
}

// Export track
function exportTrack() {
    const deviceId = document.getElementById('historyDevice').value;
    const date = document.getElementById('historyDate').value;
    
    if (!deviceId) {
        alert('Please select a device first');
        return;
    }
    
    window.open(`/api/export-track?device_id=${deviceId}&start_date=${date}&end_date=${date}&format=gpx`, '_blank');
}

// Toggle geofence visibility
document.getElementById('showGeofences').addEventListener('change', function() {
    const show = this.checked;
    Object.values(geofenceLayers).forEach(layer => {
        if (show) {
            map.addLayer(layer);
        } else {
            map.removeLayer(layer);
        }
    });
});

// Device filter
document.getElementById('deviceFilter').addEventListener('change', function() {
    const selectedDeviceId = this.value;
    
    Object.entries(markers).forEach(([deviceId, marker]) => {
        if (!selectedDeviceId || deviceId == selectedDeviceId) {
            map.addLayer(marker);
        } else {
            map.removeLayer(marker);
        }
    });
});

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshMap, 30000);
});
</script>
@endpush
@endsection 