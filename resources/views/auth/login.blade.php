@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-2xl shadow-xl">
        <h2 class="text-2xl font-bold text-center text-gray-800">Welcome back 👋</h2>

        @if (session('status'))
            <div class="p-2 text-sm text-green-700 bg-green-100 rounded">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-2 text-sm text-red-700 bg-red-100 rounded">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="space-y-4" method="POST" action="{{ route('hi') }}">
            @csrf
            <p class="text-xs text-gray-400">Token: {{ csrf_token() }}</p>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input id="email" type="email" name="email" required autofocus
                    class="w-full px-4 py-2 mt-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-4 py-2 mt-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <span class="ml-2">Remember me</span>
                </label>

            </div>

            <button type="submit"
                class="w-full px-4 py-2 font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none">
                Sign In
            </button>

        </form>
    </div>
</div>
@endsection
