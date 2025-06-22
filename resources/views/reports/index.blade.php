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
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                        <select id="tripsDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="tripsPeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="previous_month">Previous Month</option>
                            <option value="month">Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                        <select id="tripsColumnsFilter" multiple class="border border-gray-300 rounded-md px-3 py-2 text-sm" size="5">
                            <option value="start_time" selected>Start Time</option>
                            <option value="end_time" selected>End Time</option>
                            <option value="distance" selected>Distance</option>
                            <option value="average_speed" selected>Average Speed</option>
                            <option value="max_speed">Maximum Speed</option>
                            <option value="duration">Duration</option>
                        </select>
                    </div>
                    
                    <button onclick="generateTripsReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Trips Data Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Trips Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50" id="tripsTableHeader">
                            <!-- Headers will be generated by JS -->
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tripsDataTable">
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

        <div id="tab-content-stops" class="tab-content p-6 hidden">
            <!-- Stops Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                        <select id="stopsDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="stopsPeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="previous_month">Previous Month</option>
                            <option value="month">Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                        <select id="stopsColumnsFilter" multiple class="border border-gray-300 rounded-md px-3 py-2 text-sm" size="4">
                            <option value="start_time" selected>Start Time</option>
                            <option value="end_time" selected>End Time</option>
                            <option value="duration" selected>Duration</option>
                            <option value="address" selected>Address</option>
                            <option value="odometer">Odometer</option>
                        </select>
                    </div>
                    
                    <button onclick="generateStopsReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Stops Data Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Stops Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50" id="stopsTableHeader">
                            <!-- Headers will be generated by JS -->
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="stopsDataTable">
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

        <div id="tab-content-summary" class="tab-content p-6 hidden">
            <!-- Summary Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Devices</label>
                        <select id="summaryDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Groups</label>
                        <select id="summaryGroupFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Groups</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="summaryPeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="previous_month">Previous Month</option>
                            <option value="month">Last 30 Days</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select id="summaryTypeFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="summary" selected>Summary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                        <select id="summaryColumnsFilter" multiple class="border border-gray-300 rounded-md px-3 py-2 text-sm" size="4">
                            <option value="device" selected>Device</option>
                            <option value="start_date" selected>Start Date</option>
                            <option value="distance" selected>Distance</option>
                            <option value="average_speed" selected>Average Speed</option>
                        </select>
                    </div>
                    <button onclick="generateSummaryReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Summary Data Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Summary Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50" id="summaryTableHeader">
                            <!-- JS generated -->
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="summaryDataTable">
                            <!-- JS generated -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-content-chart" class="tab-content p-6 hidden">
            <!-- Chart Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                        <select id="chartDeviceFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="chartPeriodFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week" selected>This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="previous_month">Previous Month</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chart Type</label>
                        <select id="chartTypeFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="speed" selected>Speed</option>
                            <option value="altitude">Altitude</option>
                            <option value="battery_level">Battery Level</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <select id="chartTimeFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="fix_time" selected>Fix Time</option>
                        </select>
                    </div>
                    <button onclick="generateChartReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Show
                    </button>
                </div>
            </div>

            <!-- Chart Canvas -->
            <div class="bg-white rounded-lg shadow p-4">
                <canvas id="reportChart"></canvas>
            </div>
        </div>

        <div id="tab-content-replay" class="tab-content p-0 hidden">
            <div class="relative w-full h-[75vh]">
                <div id="replayMap" class="w-full h-full bg-gray-200"></div>

                <!-- Filter Panel -->
                <div id="replay-setup-panel" class="absolute top-4 left-4 bg-white p-4 rounded-lg shadow-xl z-[1000] w-80 transition-opacity duration-300">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Replay Route</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                        <select id="replayDeviceFilter" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="replayPeriodFilter" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week" selected>This Week</option>
                            <option value="this_month">This Month</option>
                        </select>
                    </div>
                    <button id="replay-show-button" onclick="generateReplayReport(this)" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-md transition duration-150 ease-in-out">
                        Show
                    </button>
                    <div id="replay-error" class="text-red-600 text-sm mt-2 h-4"></div>
                </div>

                <!-- Playback Panel -->
                <div id="replay-controls-panel" class="absolute top-4 left-4 bg-gray-800 bg-opacity-90 text-white p-4 rounded-lg shadow-xl z-[1000] w-[26rem] transition-opacity duration-300 opacity-0 pointer-events-none">
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-600">
                        <button id="replay-back-to-filters" class="text-gray-300 hover:text-white" title="Back to filters">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </button>
                        <h3 class="text-md font-semibold">Replay</h3>
                        <div class="flex items-center gap-3">
                           <button class="text-gray-300 hover:text-white" title="Download">
                               <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                           </button>
                           <button class="text-gray-300 hover:text-white" title="Settings">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" /></svg>
                           </button>
                        </div>
                    </div>
                    <p id="replay-device-name" class="text-center font-semibold text-gray-100 mb-3 truncate"></p>
                    <input id="replay-slider" type="range" min="0" value="0" class="w-full h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer mb-3 range-thumb-blue">
                    <div class="flex items-center justify-between text-sm text-gray-300">
                        <div class="flex items-center gap-2">
                           <span id="replay-frame-info">0/0</span>
                           <button id="replay-step-back" class="hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 16 16"><path d="M4.15 11.532a.5.5 0 0 1 0-.916l6-3.5a.5.5 0 0 1 .5.458V12a.5.5 0 0 1-.5.458l-6-3.5a.5.5 0 0 1 0-.916z"/><path d="M8.15 11.532a.5.5 0 0 1 0-.916l6-3.5a.5.5 0 0 1 .5.458V12a.5.5 0 0 1-.5.458l-6-3.5a.5.5 0 0 1 0-.916z"/></svg>
                           </button>
                        </div>
                        <button id="replay-play-pause" class="hover:text-white">
                            <svg id="play-icon" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="currentColor" viewBox="0 0 16 16"><path d="M10.804 8L5 4.633v6.734L10.804 8z"/></svg>
                            <svg id="pause-icon" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 hidden" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5h1v6h-1zm4 0h1v6h-1z"/></svg>
                        </button>
                        <div class="flex items-center gap-2">
                            <button id="replay-step-forward" class="hover:text-white">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 16 16"><path d="m11.596 8.5-6-3.5A.5.5 0 0 0 5 5.5v6a.5.5 0 0 0 .596.458l6-3.5a.5.5 0 0 0 0-.916z"/><path d="m7.596 8.5-6-3.5A.5.5 0 0 0 1 5.5v6a.5.5 0 0 0 .596.458l6-3.5a.5.5 0 0 0 0-.916z"/></svg>
                            </button>
                            <div id="replay-timestamp-info" class="font-mono text-xs text-right whitespace-nowrap">
                                <span></span><br><span></span>
                            </div>
                        </div>
                    </div>
                 </div>
             </div>
         </div>

        <div id="tab-content-logs" class="tab-content p-6 hidden">
            <!-- Logs content -->
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

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

function generateTripsReport() {
    const deviceId = document.getElementById('tripsDeviceFilter').value;
    const period = document.getElementById('tripsPeriodFilter').value;
    const columns = Array.from(document.getElementById('tripsColumnsFilter').selectedOptions).map(option => option.value);
    
    // Show loading
    const tbody = document.getElementById('tripsDataTable');
    tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center">Loading...</td></tr>`;
    
    // Make AJAX request
    fetch(`/api/reports/trips?device_id=${deviceId}&period=${period}&columns=${columns.join(',')}`)
        .then(response => response.json())
        .then(data => {
            displayTripsData(data, columns);
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>`;
        });
}

function displayTripsData(data, columns) {
    const tbody = document.getElementById('tripsDataTable');
    const thead = document.getElementById('tripsTableHeader');
    const columnNames = {
        start_time: 'Start Time',
        end_time: 'End Time',
        distance: 'Distance',
        average_speed: 'Average Speed',
        max_speed: 'Maximum Speed',
        duration: 'Duration'
    };
    
    // Clear previous data
    thead.innerHTML = '';
    tbody.innerHTML = '';
    
    // Create table headers
    const headerRow = document.createElement('tr');
    columns.forEach(column => {
        const th = document.createElement('th');
        th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = columnNames[column] || column;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    
    // Populate table body
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center text-gray-500">No data found</td></tr>`;
        return;
    }
    
    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        columns.forEach(column => {
            const td = document.createElement('td');
            td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
            td.textContent = item[column] || 'N/A';
            row.appendChild(td);
        });
        
        tbody.appendChild(row);
    });
}

function generateStopsReport() {
    const deviceId = document.getElementById('stopsDeviceFilter').value;
    const period = document.getElementById('stopsPeriodFilter').value;
    const columns = Array.from(document.getElementById('stopsColumnsFilter').selectedOptions).map(option => option.value);
    
    const tbody = document.getElementById('stopsDataTable');
    tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center">Loading...</td></tr>`;
    
    fetch(`/api/reports/stops?device_id=${deviceId}&period=${period}&columns=${columns.join(',')}`)
        .then(response => response.json())
        .then(data => {
            displayStopsData(data, columns);
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>`;
        });
}

function displayStopsData(data, columns) {
    const tbody = document.getElementById('stopsDataTable');
    const thead = document.getElementById('stopsTableHeader');
    const columnNames = {
        start_time: 'Start Time',
        end_time: 'End Time',
        duration: 'Duration',
        address: 'Address',
        odometer: 'Odometer'
    };

    thead.innerHTML = '';
    tbody.innerHTML = '';

    const headerRow = document.createElement('tr');
    columns.forEach(column => {
        const th = document.createElement('th');
        th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = columnNames[column] || column;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center text-gray-500">No stops found</td></tr>`;
        return;
    }

    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        columns.forEach(column => {
            const td = document.createElement('td');
            td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
            if (column === 'address') {
                td.innerHTML = `<a href="https://www.google.com/maps?q=${item[column]}" target="_blank" class="text-blue-600 hover:text-blue-900">${item[column]}</a>`;
            } else {
                td.textContent = item[column] || 'N/A';
            }
            row.appendChild(td);
        });
        
        tbody.appendChild(row);
    });
}

function generateSummaryReport() {
    const deviceId = document.getElementById('summaryDeviceFilter').value;
    const period = document.getElementById('summaryPeriodFilter').value;
    const columns = Array.from(document.getElementById('summaryColumnsFilter').selectedOptions).map(option => option.value);
    
    const tbody = document.getElementById('summaryDataTable');
    tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center">Loading...</td></tr>`;
    
    fetch(`/api/reports/summary?device_id=${deviceId}&period=${period}&columns=${columns.join(',')}`)
        .then(response => response.json())
        .then(data => {
            displaySummaryData(data, columns);
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center text-red-500">Error loading data</td></tr>`;
        });
}

function displaySummaryData(data, columns) {
    const tbody = document.getElementById('summaryDataTable');
    const thead = document.getElementById('summaryTableHeader');
    const columnNames = {
        device: 'Device',
        start_date: 'Start Date',
        distance: 'Distance',
        average_speed: 'Average Speed'
    };

    thead.innerHTML = '';
    tbody.innerHTML = '';

    const headerRow = document.createElement('tr');
    columns.forEach(column => {
        const th = document.createElement('th');
        th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = columnNames[column] || column;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns.length}" class="px-6 py-4 text-center text-gray-500">No summary data found</td></tr>`;
        return;
    }

    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        columns.forEach(column => {
            const td = document.createElement('td');
            td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
            td.textContent = item[column] || 'N/A';
            row.appendChild(td);
        });
        
        tbody.appendChild(row);
    });
}

let reportChartInstance = null;

function generateChartReport() {
    const deviceId = document.getElementById('chartDeviceFilter').value;
    const period = document.getElementById('chartPeriodFilter').value;
    const chartType = document.getElementById('chartTypeFilter').value;
    
    const ctx = document.getElementById('reportChart');
    if (!ctx) return;

    if (reportChartInstance) {
        reportChartInstance.destroy();
    }

    const loadingChart = new Chart(ctx, {
        type: 'scatter',
        data: { datasets: [] },
        options: { 
            plugins: { legend: { display: false }, title: { display: true, text: 'Loading...' }}, 
            scales: { x: { display: false }, y: { display: false } }
        }
    });

    fetch(`/api/reports/chart?device_id=${deviceId}&period=${period}&chart_type=${chartType}`)
        .then(response => response.json())
        .then(data => {
            loadingChart.destroy();
            renderChart(data, chartType);
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            loadingChart.destroy();
            const errorCtx = document.getElementById('reportChart').getContext('2d');
            new Chart(errorCtx, {
                type: 'scatter',
                data: { datasets: [] },
                options: { 
                    plugins: { legend: { display: false }, title: { display: true, text: 'No data to display' }},
                    scales: { x: { display: false }, y: { display: false } }
                }
            });
        });
}

function renderChart(data, chartType) {
    const ctx = document.getElementById('reportChart').getContext('2d');
    
    if (reportChartInstance) {
        reportChartInstance.destroy();
    }

    const chartTypeLabels = {
        speed: 'Speed (km/h)',
        altitude: 'Altitude (m)',
        battery_level: 'Battery Level (%)'
    };
    
    reportChartInstance = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: chartTypeLabels[chartType] || chartType,
                data: data,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                showLine: true,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        tooltipFormat: 'MMM dd, yyyy, HH:mm:ss'
                    },
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: chartTypeLabels[chartType] || chartType
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(2);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// --- Replay Module ---
let replayMap = null;
let replayMarker = null;
let replayPolylineGroup = null;
let replayAnimation;
let currentReplayIndex = 0;
let replayPositions = [];
let isReplayPlaying = false;

function initializeReplayMap() {
    if (replayMap) return;
    replayMap = L.map('replayMap', { zoomControl: false }).setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(replayMap);
    L.control.zoom({ position: 'topright' }).addTo(replayMap);
}

function generateReplayReport(button) {
    const deviceId = document.getElementById('replayDeviceFilter').value;
    const period = document.getElementById('replayPeriodFilter').value;
    const errorDiv = document.getElementById('replay-error');
    const setupPanel = document.getElementById('replay-setup-panel');
    const controlsPanel = document.getElementById('replay-controls-panel');
    
    errorDiv.textContent = '';
    button.textContent = 'Loading...';
    button.disabled = true;

    fetch(`/api/reports/replay?device_id=${deviceId}&period=${period}`)
        .then(response => response.ok ? response.json() : Promise.reject('Failed to load data'))
        .then(data => {
            if (data.length < 2) {
                errorDiv.textContent = 'Not enough data to display a route.';
                return;
            }
            replayPositions = data;
            setupReplayAnimation(data);
            
            setupPanel.classList.add('opacity-0', 'pointer-events-none');
            controlsPanel.classList.remove('opacity-0', 'pointer-events-none');
            const deviceName = document.getElementById('replayDeviceFilter').selectedOptions[0].text;
            document.getElementById('replay-device-name').textContent = deviceName;
        })
        .catch(error => {
            console.error('Error fetching replay data:', error);
            errorDiv.textContent = 'Failed to load replay data or no data found.';
        })
        .finally(() => {
           button.textContent = 'Show';
           button.disabled = false;
        });
}

function backToFilters() {
    stopReplay();
    document.getElementById('replay-setup-panel').classList.remove('opacity-0', 'pointer-events-none');
    document.getElementById('replay-controls-panel').classList.add('opacity-0', 'pointer-events-none');
    if(replayPolylineGroup) replayMap.removeLayer(replayPolylineGroup);
    if(replayMarker) replayMap.removeLayer(replayMarker);
    replayMap.closePopup();
    replayPositions = [];
}

function onReplaySegmentClick(e) {
    const pos = e.target.positionData;
    const deviceName = document.getElementById('replay-device-name').textContent;
    const totalDistance = pos.distance ? `${parseFloat(pos.distance).toFixed(2)} km` : 'N/A';
    const fixTime = new Date(pos.timestamp).toLocaleString([], { dateStyle: 'medium', timeStyle: 'medium' });

    const popupContent = `
        <div class="w-72 -m-1">
            <div class="bg-gray-800 text-white text-sm font-semibold p-2 rounded-t-lg flex justify-between items-center">
                <span class="truncate pr-2">${deviceName}</span>
                <button onclick="replayMap.closePopup()" class="font-bold text-xl leading-none">&times;</button>
            </div>
            <div class="p-3 text-xs text-gray-700 space-y-2">
                <p><strong>Fix Time:</strong> ${fixTime}</p>
                <p><strong>Address:</strong> <span id="popup-address-${pos.id}">Loading...</span></p>
                <p><strong>Speed:</strong> ${pos.speed} km/h</p>
                <p><strong>Total Distance:</strong> ${totalDistance}</p>
            </div>
            <div class="bg-gray-100 p-2 rounded-b-lg flex justify-around items-center text-gray-600">
                <button title="More"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" /></svg></button>
                <button title="Street View"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg></button>
                <button title="Edit"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg></button>
                <button title="Delete"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
            </div>
        </div>
    `;

    L.popup({ minWidth: 288 }).setLatLng(e.latlng).setContent(popupContent).openOn(replayMap);

    fetch(`/api/geocode/reverse?lat=${pos.latitude}&lon=${pos.longitude}`)
        .then(res => res.json())
        .then(data => {
            const addressEl = document.getElementById(`popup-address-${pos.id}`);
            if (addressEl) {
                addressEl.textContent = data.address || 'Address not found.';
            }
        });
}

function setupReplayAnimation(positions) {
    initializeReplayMap();
    if(replayPolylineGroup) replayMap.removeLayer(replayPolylineGroup);
    if(replayMarker) replayMap.removeLayer(replayMarker);

    const speedColors = { high: '#16a34a', medium: '#f97316', low: '#dc2626' }; // Green, Orange, Red
    replayPolylineGroup = L.featureGroup().addTo(replayMap);

    for (let i = 1; i < positions.length; i++) {
        const p1 = positions[i-1];
        const p2 = positions[i];
        const speed = p2.speed;
        const color = speed > 40 ? speedColors.high : (speed > 5 ? speedColors.medium : speedColors.low);
        const segment = L.polyline([[p1.latitude, p1.longitude], [p2.latitude, p2.longitude]], { 
            color: color, 
            weight: 5, 
            opacity: 0.85,
            dashArray: '1, 8'
        });
        segment.positionData = p2;
        segment.on('click', onReplaySegmentClick);
        segment.addTo(replayPolylineGroup);
    }
    
    replayMap.fitBounds(replayPolylineGroup.getBounds(), { padding: [50, 50] });

    const carIcon = L.divIcon({
       html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-white drop-shadow-lg"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`,
       className: 'bg-blue-600 rounded-full border-2 border-white shadow-md',
       iconSize: [32, 32],
       iconAnchor: [16, 32]
    });
    replayMarker = L.marker([positions[0].latitude, positions[0].longitude], { icon: carIcon, zIndexOffset: 1000 }).addTo(replayMap);
    
    const slider = document.getElementById('replay-slider');
    slider.max = positions.length - 1;
    slider.value = 0;

    slider.oninput = function() {
        currentReplayIndex = parseInt(this.value);
        updateReplayMarkerAndInfo(currentReplayIndex);
    };
    
    document.getElementById('replay-play-pause').onclick = toggleReplay;
    document.getElementById('replay-back-to-filters').onclick = backToFilters;
    document.getElementById('replay-step-back').onclick = () => {
        currentReplayIndex = Math.max(0, currentReplayIndex - 1);
        updateReplayMarkerAndInfo(currentReplayIndex);
    };
    document.getElementById('replay-step-forward').onclick = () => {
        currentReplayIndex = Math.min(replayPositions.length - 1, currentReplayIndex + 1);
        updateReplayMarkerAndInfo(currentReplayIndex);
    };

    updateReplayMarkerAndInfo(0);
}

function updateReplayMarkerAndInfo(index) {
    if(!replayPositions[index]) return;
    const pos = replayPositions[index];
    const latLng = [pos.latitude, pos.longitude];
    replayMarker.setLatLng(latLng);
    
    document.getElementById('replay-frame-info').textContent = `${index + 1}/${replayPositions.length}`;
    
    const timestamp = new Date(pos.timestamp);
    const dateEl = document.querySelector('#replay-timestamp-info span:first-child');
    const timeEl = document.querySelector('#replay-timestamp-info span:last-child');
    dateEl.textContent = timestamp.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) + ',';
    timeEl.textContent = timestamp.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

    document.getElementById('replay-slider').value = index;
}

function toggleReplay() {
    isReplayPlaying = !isReplayPlaying;
    const playIcon = document.getElementById('play-icon');
    const pauseIcon = document.getElementById('pause-icon');

    if (isReplayPlaying) {
        playIcon.classList.add('hidden');
        pauseIcon.classList.remove('hidden');
        if (currentReplayIndex >= replayPositions.length - 1) {
            currentReplayIndex = 0; // Restart if at the end
        }
        animateReplay();
    } else {
        playIcon.classList.remove('hidden');
        pauseIcon.classList.add('hidden');
        cancelAnimationFrame(replayAnimation);
    }
}

function animateReplay() {
    if (!isReplayPlaying || currentReplayIndex >= replayPositions.length - 1) {
        stopReplay();
        return;
    }
    
    const speedMultiplier = parseInt(document.getElementById('replay-speed').value, 10);
    currentReplayIndex = Math.min(replayPositions.length - 1, currentReplayIndex + speedMultiplier);
    
    updateReplayMarkerAndInfo(Math.floor(currentReplayIndex));

    replayAnimation = requestAnimationFrame(animateReplay);
}

function stopReplay() {
    isReplayPlaying = false;
    document.getElementById('play-icon').classList.remove('hidden');
    document.getElementById('pause-icon').classList.add('hidden');
    cancelAnimationFrame(replayAnimation);
}
// --- End Replay Module ---
</script>
@endpush
@endsection 