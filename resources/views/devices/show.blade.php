@extends('layouts.app')

@section('title', $device->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $device->name }}</h1>
            <p class="text-gray-600">Device ID: {{ $device->unique_id }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('devices.edit', $device) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('devices.map', $device) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-map mr-2"></i>View Map
            </a>
        </div>
    </div>

    <!-- Device Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-info-circle text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Status</p>
                    <p class="text-lg font-semibold text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $device->status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($device->status) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-user text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Owner</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $device->user->name }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Last Update</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $latestPosition ? $latestPosition->timestamp->diffForHumans() : 'Never' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Device Details -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Device Information</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Device Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $device->name }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Unique ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $device->unique_id }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Model</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $device->model ?: 'Not specified' }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($device->category ?: 'Not specified') }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $device->phone ?: 'Not specified' }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $device->contact ?: 'Not specified' }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">API Key</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="flex items-center space-x-2">
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $device->api_key }}</code>
                            <form method="POST" action="{{ route('devices.api-key', $device) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-900 text-xs">
                                    <i class="fas fa-sync-alt"></i> Regenerate
                                </button>
                            </form>
                        </div>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Disabled</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $device->disabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                            {{ $device->disabled ? 'Yes' : 'No' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Current Location -->
    @if($latestPosition)
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Current Location</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Coordinates</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ number_format($latestPosition->latitude, 6) }}, {{ number_format($latestPosition->longitude, 6) }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Speed</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $latestPosition->speed ? number_format($latestPosition->speed, 1) . ' km/h' : 'N/A' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Heading</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $latestPosition->course ? number_format($latestPosition->course, 0) . '°' : 'N/A' }}
                    </dd>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-route text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Distance</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalDistance, 1) }} km</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-clock text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Time</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $totalTime }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-map-marker-alt text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Positions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $device->positions->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Positions -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Recent Positions</h3>
            <a href="{{ route('devices.positions', $device) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                View All
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heading</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($device->positions->take(10) as $position)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->timestamp->format('M d, H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($position->latitude, 4) }}, {{ number_format($position->longitude, 4) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->speed ? number_format($position->speed, 1) . ' km/h' : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $position->course ? number_format($position->course, 0) . '°' : 'N/A' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 