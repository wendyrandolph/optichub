<@extends('layouts.app') @section('title', 'Time' ) @section('content') @php
    // Prefer to have $tenant (Tenant model) passed into the view.
    // If not, fall back to something your app exposes.
    $tenantParam =
        $tenant ?? // Tenant model (best)
        (auth()->user()->tenant ?? // model via relationship
            tenant()); // from your tenancy helper
@endphp!-- app/views/time/index.php -->
    <div class="container">
        <a href="/time/create" class="btn btn-add">Log Time</a>
        <div class="hero-section">

            <h1>Time Entries</h1>
            <div class="description">
                <p>This is the time index page. You'll eventually have a list of all time entries here.</p>
            </div>
        </div>
    </div>

    @endphp
