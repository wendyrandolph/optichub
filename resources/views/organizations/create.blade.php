@extends('layouts.app')

@section('title', 'Organizations')

@section('content')
    @php
        // Resolve tenant id safely (model or scalar)
        $tp = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;
    @endphp
    <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

        <div class="container">
            <a href=" /organizations" class="btn btn-back">Back to Organizations</a>
        </div>

        <div class="form-card w-full">
            <div class="form-card__head">
                <h2> Add New Tenant </h2>
            </div>

            <form method="POST" action="/organizations/create" class="flex flex-col space-y-4 p-4">
                @csrf
                <div>
                    <div class="w-1/2">
                        <label for="name">Organization Name *</label>
                        <input class="cws-input" type="text" id="name" name="name" required>
                    </div>
                    <div class="w-1/2">
                        <label for="industry">Industry</label>
                        <input class="cws-input" type="text" id="industry" name="industry">
                    </div>
                </div>

                <div class="row-padding">
                    <div class="cws-half">
                        <label for="location">Location</label>
                        <input class="cws-input" type="text" id="location" name="location">
                    </div>
                    <div class="cws-half">
                        <label for="website">Website</label>
                        <input class="cws-input" type="url" id="website" name="website">
                    </div>
                </div>
                <div class="row-padding">
                    <div class="cws-half">
                        <label for="phone">Phone</label>
                        <input class="cws-input" type="tel" id="phone" name="phone">
                    </div>
                    <div class="cws-half">
                        <label for="notes">Notes</label>
                        <textarea class="cws-input notes" id="notes" name="notes"></textarea>
                    </div>
                </div>
                <div class=" row-padding">
                    <div class="cws-half">
                        <button type="submit" class="btn btn-save centered-block">Save Organization</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
