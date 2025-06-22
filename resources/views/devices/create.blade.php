@extends('layouts.app')

@section('title', 'Add Device')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Add New Device</h1>
        <p class="text-gray-600">Register a new GPS tracking device</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('devices.store') }}">
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
                    <label for="name" class="block text-sm font-medium text-gray-700">Device Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter device name">
                </div>

                <div>
                    <label for="unique_id" class="block text-sm font-medium text-gray-700">Device ID</label>
                    <input type="text" id="unique_id" name="unique_id" value="{{ old('unique_id') }}" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter unique device ID">
                    <p class="mt-1 text-sm text-gray-500">This should be the unique identifier from your GPS device</p>
                </div>

                <div>
                    <label for="model" class="block text-sm font-medium text-gray-700">Device Model</label>
                    <input type="text" id="model" name="model" value="{{ old('model') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., GT06N, TK103">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Device phone number (optional)">
                </div>

                <div>
                    <label for="contact" class="block text-sm font-medium text-gray-700">Contact Person</label>
                    <input type="text" id="contact" name="contact" value="{{ old('contact') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Contact person name">
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category" name="category" 
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select category</option>
                        <option value="car" {{ old('category') == 'car' ? 'selected' : '' }}>Car</option>
                        <option value="truck" {{ old('category') == 'truck' ? 'selected' : '' }}>Truck</option>
                        <option value="motorcycle" {{ old('category') == 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                        <option value="bicycle" {{ old('category') == 'bicycle' ? 'selected' : '' }}>Bicycle</option>
                        <option value="person" {{ old('category') == 'person' ? 'selected' : '' }}>Person</option>
                        <option value="asset" {{ old('category') == 'asset' ? 'selected' : '' }}>Asset</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="disabled" class="flex items-center">
                        <input type="checkbox" id="disabled" name="disabled" value="1" {{ old('disabled') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">Disabled</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">Check this if the device should be disabled</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('devices.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        Add Device
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection 