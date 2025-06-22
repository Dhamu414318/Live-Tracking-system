@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
            <p class="text-gray-600">Track performance and generate insights</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="exportReport()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
            <button onclick="printReport()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-print mr-2"></i>
                Print
            </button>
        </div>
    </div>

    <!-- Report Tabs -->
    <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button onclick="showTab('combined')" id="tab-combined" class="tab-button border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                    Combined
                </button>
                <button onclick="showTab('route')" id="tab-route" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Route
                </button>
                <button onclick="showTab('events')" id="tab-events" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Events
                </button>
                <button onclick="showTab('trips')" id="tab-trips" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Trips
                </button>
                <button onclick="showTab('stops')" id="tab-stops" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Stops
                </button>
                <button onclick="showTab('summary')" id="tab-summary" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Summary
                </button>
                <button onclick="showTab('chart')" id="tab-chart" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Chart
                </button>
                <button onclick="showTab('replay')" id="tab-replay" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Replay
                </button>
            </nav>
        </div>

        <!-- Combined Tab Content -->
        <div id="tab-content-combined" class="tab-content p-6">
            <!-- Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Devices</label>
                        <select id="combinedDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Groups</label>
                        <select id="combinedGroupFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Groups</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="combinedPeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week" selected>Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <button onclick="generateCombinedReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Map View -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Travel Data Map</h3>
                </div>
                <div id="combinedMap" class="h-96 w-full"></div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Travel Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fix Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latitude</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Longitude</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Altitude</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accuracy</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GeoFence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Battery Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charge</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motion</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="combinedDataTable">
                            <tr>
                                <td colspan="18" class="px-6 py-4 text-center text-gray-500">
                                    Select filters and click "Show" to load data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Route Tab Content -->
        <div id="tab-content-route" class="tab-content p-6 hidden">
            <!-- Route Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Devices</label>
                        <select id="routeDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="routePeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week" selected>Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                        <select id="routeColumnsFilter" multiple class="border border-gray-300 rounded-md px-3 py-2 text-sm" size="8">
                            <option value="device" selected>Device</option>
                            <option value="fixTime" selected>Fix Time</option>
                            <option value="latitude" selected>Latitude</option>
                            <option value="longitude" selected>Longitude</option>
                            <option value="speed" selected>Speed</option>
                            <option value="course">Course</option>
                            <option value="altitude">Altitude</option>
                            <option value="accuracy">Accuracy</option>
                            <option value="valid">Valid</option>
                            <option value="protocol">Protocol</option>
                            <option value="serverTime">Server Time</option>
                            <option value="geoFence">GeoFence</option>
                            <option value="batteryLevel">Battery Level</option>
                            <option value="charge">Charge</option>
                            <option value="distance">Distance</option>
                            <option value="motion">Motion</option>
                        </select>
                    </div>
                    
                    <button onclick="generateRouteReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Route Data Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Route Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50" id="routeTableHeader">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fix Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latitude</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Longitude</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="routeDataTable">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Select filters and click "Show" to load data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Events Tab Content -->
        <div id="tab-content-events" class="tab-content p-6 hidden">
            <!-- Map View -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Event Location Map</h3>
                </div>
                <div id="eventsMap" class="h-96 w-full"></div>
            </div>
            
            <!-- Events Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                        <select id="eventsDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="eventsPeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week" selected>Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Types</label>
                        <select id="eventsTypeFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Events</option>
                            <option value="command_result">Command result</option>
                            <option value="status_online">Status online</option>
                            <option value="status_unknown">Status unknown</option>
                            <option value="status_offline">Status offline</option>
                            <option value="device_inactive">Device inactive</option>
                            <option value="command_sent">Queued command sent</option>
                            <option value="device_moving">Device moving</option>
                            <option value="device_stopped">Device stopped</option>
                            <option value="speed_limit_exceeded">Speed limit exceeded</option>
                            <option value="fuel_drop">Fuel drop</option>
                            <option value="fuel_increase">Fuel increase</option>
                            <option value="geofence_enter">Geofence entered</option>
                            <option value="geofence_exit">Geofence exited</option>
                            <option value="alarm">Alarm</option>
                            <option value="ignition_on">Ignition on</option>
                            <option value="ignition_off">Ignition off</option>
                            <option value="maintenance">Maintenance required</option>
                            <option value="text_message">Text message received</option>
                            <option value="driver_changed">Driver changed</option>
                            <option value="media">Media</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                        <select id="eventsColumnsFilter" multiple class="border border-gray-300 rounded-md px-3 py-2 text-sm" size="4">
                            <option value="fixTime" selected>Fix Time</option>
                            <option value="type" selected>Type</option>
                            <option value="data" selected>Data</option>
                            <option value="geofence">Geofence</option>
                        </select>
                    </div>

                    <button onclick="generateEventsReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Events Data Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Events Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50" id="eventsTableHeader">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fix Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="eventsDataTable">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Select filters and click "Show" to load data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Other Tab Contents -->
        <div id="tab-content-trips" class="tab-content p-6 hidden">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Trips Report</h3>
            <p class="text-gray-600">Trips report functionality will be implemented here.</p>
        </div>

        <div id="tab-content-stops" class="tab-content p-6 hidden">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Stops Report</h3>
            <p class="text-gray-600">Stops report functionality will be implemented here.</p>
        </div>

        <div id="tab-content-summary" class="tab-content p-6 hidden">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Summary Report</h3>
            <p class="text-gray-600">Summary report functionality will be implemented here.</p>
        </div>

        <div id="tab-content-chart" class="tab-content p-6 hidden">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Chart Report</h3>
            <p class="text-gray-600">Chart report functionality will be implemented here.</p>
        </div>

        <div id="tab-content-replay" class="tab-content p-6 hidden">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Replay Report</h3>
            <p class="text-gray-600">Replay report functionality will be implemented here.</p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
let map = null;
let eventsMap = null;

// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(`tab-content-${tabName}`).classList.remove('hidden');
    
    // Activate selected tab button
    document.getElementById(`tab-${tabName}`).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById(`tab-${tabName}`).classList.add('border-blue-500', 'text-blue-600');
    
    // Initialize map for combined tab
    if (tabName === 'combined' && !map) {
        initializeMap();
    }
    if (tabName === 'events' && !eventsMap) {
        initializeEventsMap();
    }
}

// Initialize map
function initializeMap() {
    map = L.map('combinedMap').setView([40.7128, -74.0060], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
}

function initializeEventsMap() {
    eventsMap = L.map('eventsMap').setView([22.5726, 88.3639], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(eventsMap);
}

// Generate combined report
function generateCombinedReport() {
    const deviceId = document.getElementById('combinedDeviceFilter').value;
    const period = document.getElementById('combinedPeriodFilter').value;
    
    // Show loading
    document.getElementById('combinedDataTable').innerHTML = '<tr><td colspan="18" class="px-6 py-4 text-center">Loading...</td></tr>';
    
    // Make AJAX request to get data
    fetch(`/api/reports/combined?device_id=${deviceId}&period=${period}`)
        .then(response => response.json())
        .then(data => {
            displayCombinedData(data);
            displayCombinedMap(data);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('combinedDataTable').innerHTML = '<tr><td colspan="18" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>';
        });
}

// Generate route report
function generateRouteReport() {
    const deviceId = document.getElementById('routeDeviceFilter').value;
    const period = document.getElementById('routePeriodFilter').value;
    const columns = Array.from(document.getElementById('routeColumnsFilter').selectedOptions).map(option => option.value);
    
    // Show loading
    document.getElementById('routeDataTable').innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center">Loading...</td></tr>';
    
    // Make AJAX request to get data
    fetch(`/api/reports/route?device_id=${deviceId}&period=${period}&columns=${columns.join(',')}`)
        .then(response => response.json())
        .then(data => {
            displayRouteData(data, columns);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('routeDataTable').innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>';
        });
}

// Generate events report
function generateEventsReport() {
    const deviceId = document.getElementById('eventsDeviceFilter').value;
    const period = document.getElementById('eventsPeriodFilter').value;
    const eventType = document.getElementById('eventsTypeFilter').value;
    const columns = Array.from(document.getElementById('eventsColumnsFilter').selectedOptions).map(option => option.value);

    document.getElementById('eventsDataTable').innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center">Loading...</td></tr>';

    fetch(`/api/reports/events?device_id=${deviceId}&period=${period}&type=${eventType}&columns=${columns.join(',')}`)
        .then(response => response.json())
        .then(data => {
            displayEventsData(data, columns);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('eventsDataTable').innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>';
        });
}

// Display combined data
function displayCombinedData(data) {
    const tbody = document.getElementById('combinedDataTable');
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.device_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.fix_time}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.type}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.latitude}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.longitude}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.speed}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.course}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.altitude}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.accuracy}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.valid}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.protocol}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.server_time}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.geofence}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.battery_level}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.charge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.distance}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.motion}</td>
        `;
        tbody.appendChild(row);
    });
}

// Display route data
function displayRouteData(data, columns) {
    const tbody = document.getElementById('routeDataTable');
    const thead = document.getElementById('routeTableHeader');
    
    // Update table headers
    thead.innerHTML = '';
    const headerRow = document.createElement('tr');
    columns.forEach(column => {
        const th = document.createElement('th');
        th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = column.charAt(0).toUpperCase() + column.slice(1);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    
    // Update table body
    tbody.innerHTML = '';
    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        columns.forEach(column => {
            const td = document.createElement('td');
            td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
            td.textContent = item[column] || '';
            row.appendChild(td);
        });
        
        tbody.appendChild(row);
    });
}

// Display events data
function displayEventsData(data, columns) {
    const tbody = document.getElementById('eventsDataTable');
    const thead = document.getElementById('eventsTableHeader');
    
    // Update table headers
    thead.innerHTML = '';
    const headerRow = document.createElement('tr');
    let headerHtml = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>';
    columns.forEach(column => {
        const colName = column.charAt(0).toUpperCase() + column.slice(1);
        headerHtml += `<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">${colName}</th>`;
    });
    headerRow.innerHTML = headerHtml;
    thead.appendChild(headerRow);

    // Update table body
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length + 1}" class="px-6 py-4 text-center text-gray-500">No events found</td></tr>`;
        return;
    }

    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        let rowHtml = `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><button onclick="showEventOnMap(${item.latitude}, ${item.longitude})" class="text-blue-500 hover:text-blue-700"><i class="fas fa-map-marker-alt"></i></button></td>`;

        columns.forEach(column => {
            rowHtml += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item[column] || ''}</td>`;
        });

        row.innerHTML = rowHtml;
        tbody.appendChild(row);
    });
}

// Display combined map
function displayCombinedMap(data) {
    if (!map) return;
    
    // Clear existing markers
    map.eachLayer((layer) => {
        if (layer instanceof L.Marker) {
            map.removeLayer(layer);
        }
    });
    
    // Add markers for each data point
    data.forEach(item => {
        if (item.latitude && item.longitude) {
            L.marker([item.latitude, item.longitude])
                .addTo(map)
                .bindPopup(`
                    <div class="p-2">
                        <h3 class="font-bold">${item.device_name}</h3>
                        <p class="text-sm">Time: ${item.fix_time}</p>
                        <p class="text-sm">Speed: ${item.speed}</p>
                    </div>
                `);
        }
    });
}

function showEventOnMap(lat, lng) {
    if (eventsMap) {
        eventsMap.setView([lat, lng], 15);
        L.marker([lat, lng]).addTo(eventsMap);
    }
}

// Export and print functions
function exportReport() {
    console.log('Exporting report...');
}

function printReport() {
    console.log('Printing report...');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    showTab('combined');
});
</script>
@endpush
@endsection 