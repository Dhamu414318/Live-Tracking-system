@extends('layouts.app')

@section('title', 'Add Geofence')

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Add New Geofence</h1>
        <p class="text-gray-600">Create a geographic boundary for tracking</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('geofences.store') }}" id="geofenceForm">
                @csrf
                
                @if($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Geofence Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter geofence name">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter description">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select id="type" name="type" required
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select type</option>
                            <option value="polygon" {{ old('type') == 'polygon' ? 'selected' : '' }}>Polygon</option>
                            <option value="circle" {{ old('type') == 'circle' ? 'selected' : '' }}>Circle</option>
                        </select>
                    </div>

                    <div>
                        <label for="devices" class="block text-sm font-medium text-gray-700">Devices</label>
                        <select id="devices" name="devices[]" multiple
                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" {{ in_array($device->id, old('devices', [])) ? 'selected' : '' }}>
                                    {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Hold Ctrl/Cmd to select multiple devices</p>
                    </div>

                    <div>
                        <label for="active" class="flex items-center">
                            <input type="checkbox" id="active" name="active" value="1" {{ old('active') ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500">Check this if the geofence should be active</p>
                    </div>

                    <!-- Hidden coordinates field -->
                    <input type="hidden" id="coordinates" name="coordinates" value="{{ old('coordinates') }}">

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('geofences.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                            Create Geofence
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Map -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Draw Geofence</h3>
                <p class="text-sm text-gray-600">Click on the map to draw your geofence boundary</p>
            </div>
            <div id="geofenceMap" class="h-96 w-full"></div>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex space-x-2">
                    <button type="button" id="clearMap" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                        Clear
                    </button>
                    <button type="button" id="finishDrawing" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                        Finish Drawing
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
let map, drawingLayer, currentPolygon, currentCircle;
let isDrawing = false;

// Initialize map
map = L.map('geofenceMap').setView([40.7128, -74.0060], 10);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Drawing layer
drawingLayer = L.layerGroup().addTo(map);

// Map click handler
map.on('click', function(e) {
    const type = document.getElementById('type').value;
    
    if (type === 'polygon') {
        handlePolygonClick(e);
    } else if (type === 'circle') {
        handleCircleClick(e);
    }
});

function handlePolygonClick(e) {
    if (!isDrawing) {
        // Start new polygon
        isDrawing = true;
        currentPolygon = L.polygon([e.latlng], {
            color: 'blue',
            weight: 2,
            fillColor: 'blue',
            fillOpacity: 0.2
        }).addTo(drawingLayer);
    } else {
        // Add point to existing polygon
        const latlngs = currentPolygon.getLatLngs()[0];
        latlngs.push(e.latlng);
        currentPolygon.setLatLngs(latlngs);
    }
}

function handleCircleClick(e) {
    if (currentCircle) {
        drawingLayer.removeLayer(currentCircle);
    }
    
    currentCircle = L.circle(e.latlng, {
        color: 'red',
        weight: 2,
        fillColor: 'red',
        fillOpacity: 0.2,
        radius: 1000 // Default 1km radius
    }).addTo(drawingLayer);
}

// Type change handler
document.getElementById('type').addEventListener('change', function() {
    clearMap();
});

// Clear map button
document.getElementById('clearMap').addEventListener('click', clearMap);

function clearMap() {
    drawingLayer.clearLayers();
    currentPolygon = null;
    currentCircle = null;
    isDrawing = false;
    document.getElementById('coordinates').value = '';
}

// Finish drawing button
document.getElementById('finishDrawing').addEventListener('click', function() {
    const type = document.getElementById('type').value;
    let coordinates = [];
    
    if (type === 'polygon' && currentPolygon) {
        const latlngs = currentPolygon.getLatLngs()[0];
        coordinates = latlngs.map(latlng => [latlng.lat, latlng.lng]);
    } else if (type === 'circle' && currentCircle) {
        const center = currentCircle.getLatLng();
        const radius = currentCircle.getRadius();
        coordinates = {
            center: [center.lat, center.lng],
            radius: radius
        };
    }
    
    if (coordinates.length > 0 || (type === 'circle' && coordinates.center)) {
        document.getElementById('coordinates').value = JSON.stringify(coordinates);
        isDrawing = false;
    }
});

// Form submission
document.getElementById('geofenceForm').addEventListener('submit', function(e) {
    const coordinates = document.getElementById('coordinates').value;
    if (!coordinates) {
        e.preventDefault();
        alert('Please draw a geofence on the map first.');
        return false;
    }
});
</script>
@endpush
@endsection 