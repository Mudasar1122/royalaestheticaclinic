@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Create Manual Lead';
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card border-0">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 flex items-center justify-between">
            <h6 class="mb-0 font-semibold text-lg">Manual Lead Form</h6>
            <a href="{{ route('clinicLeads') }}" class="btn btn-secondary text-sm btn-sm px-3 py-2 rounded-lg">Back to Leads</a>
        </div>
        <div class="card-body p-6">
            <form action="{{ route('clinicManualLeadStore') }}" method="POST" class="grid grid-cols-12 gap-5">
                @csrf
                <div class="col-span-12 lg:col-span-6">
                    <label class="form-label">Full Name <span class="text-danger-600">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control rounded-lg" placeholder="Lead full name" required>
                </div>
                <div class="col-span-12 lg:col-span-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control rounded-lg" placeholder="+92xxxxxxxxxx">
                </div>
                <div class="col-span-12 lg:col-span-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control rounded-lg" placeholder="lead@example.com">
                </div>
                <div class="col-span-12 lg:col-span-6">
                    <label class="form-label">Source Platform <span class="text-danger-600">*</span></label>
                    <select name="source_platform" class="form-select rounded-lg" required>
                        @foreach ($sources as $sourceKey => $sourceLabel)
                            <option value="{{ $sourceKey }}" @selected(old('source_platform', 'manual') === $sourceKey)>{{ $sourceLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-12 lg:col-span-6">
                    <label class="form-label">Stage <span class="text-danger-600">*</span></label>
                    <select name="stage" class="form-select rounded-lg" required>
                        @foreach ($stages as $stageKey => $stageLabel)
                            <option value="{{ $stageKey }}" @selected(old('stage', 'initial') === $stageKey)>{{ $stageLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-12 lg:col-span-6">
                    <label class="form-label">First Follow-up Due At</label>
                    <input type="datetime-local" name="follow_up_due_at" value="{{ old('follow_up_due_at') }}" class="form-control rounded-lg">
                </div>
                <div class="col-span-12">
                    <label class="form-label">Lead Note</label>
                    <textarea name="note" rows="4" class="form-control rounded-lg" placeholder="Add conversation details, treatment interest, budget, concerns...">{{ old('note') }}</textarea>
                </div>
                <div class="col-span-12 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary px-6 py-3 rounded-lg">Create Lead + First Follow-up</button>
                    <a href="{{ route('clinicLeads') }}" class="btn btn-danger border border-danger-600 bg-hover-danger-200 text-danger-600 px-6 py-3 rounded-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
