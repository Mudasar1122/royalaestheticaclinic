@extends('layout.layout')

@php
    $title = 'CRM Dashboard';
    $subTitle = 'Social Lead Management';
    $canCreateLead = auth()->user()?->hasModulePermission('lead_management', 'create_lead') ?? false;
    $canViewLeads = auth()->user()?->hasModulePermission('lead_management', 'view_leads') ?? false;
@endphp

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-6">
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Total Leads</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($summary['total_leads']) }}</h5>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Open Leads</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($summary['open_leads']) }}</h5>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Confirmed</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($summary['confirmed_leads']) }}</h5>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Pending Follow-ups</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($summary['pending_follow_ups']) }}</h5>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Overdue Follow-ups</p>
                <h5 class="mb-0 {{ $summary['overdue_follow_ups'] > 0 ? 'text-danger-600' : 'dark:text-white' }}">
                    {{ number_format($summary['overdue_follow_ups']) }}
                </h5>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-6">
        <div class="xl:col-span-8">
            <div class="card h-full border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 flex items-center justify-between">
                    <h6 class="mb-0 font-semibold text-lg">Recent Leads & Follow-ups</h6>
                    <div class="flex items-center gap-2">
                        @if ($canCreateLead)
                            <a href="{{ route('clinicManualLead') }}" class="btn btn-primary text-sm btn-sm px-3 py-2 rounded-lg">Create New Lead</a>
                        @endif
                        @if ($canViewLeads)
                            <a href="{{ route('clinicLeads') }}" class="btn btn-secondary text-sm btn-sm px-3 py-2 rounded-lg">Manage Leads</a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Lead</th>
                                    <th scope="col">Source</th>
                                    <th scope="col">Stage</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Next Follow-up</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLeads as $lead)
                                    @php
                                        $nextFollowUp = $lead->followUps->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-neutral-700 dark:text-neutral-100">{{ $lead->contact?->full_name ?? 'Unnamed Lead' }}</span>
                                                <span class="text-xs text-secondary-light">{{ $lead->contact?->phone ?? $lead->contact?->email ?? 'No contact info' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $lead->source_platform === 'manual' ? 'Walk In Lead' : ucfirst(str_replace('_', ' ', $lead->source_platform)) }}</td>
                                        <td>
                                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-300">
                                                {{ ucfirst($lead->stage) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $lead->status === 'open' ? 'bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-300' : 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200' }}">
                                                {{ ucfirst($lead->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($nextFollowUp)
                                                <span class="{{ $nextFollowUp->due_at < now('Asia/Karachi') ? 'text-danger-600 font-medium' : '' }}">
                                                    {{ $nextFollowUp->due_at?->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT
                                                </span>
                                            @else
                                                <span class="text-secondary-light">No pending follow-up</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-8 text-secondary-light">No leads available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-4 flex flex-col gap-6">
            <div class="card border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
                    <h6 class="mb-0 font-semibold text-lg">Platform Performance</h6>
                </div>
                <div class="card-body">
                    <ul class="flex flex-col gap-3">
                        @foreach ($platformStats as $platform)
                            <li class="flex items-center justify-between">
                                <span class="text-neutral-600 dark:text-neutral-200">{{ $platform['label'] }}</span>
                                <span class="font-semibold">{{ number_format($platform['total']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
                    <h6 class="mb-0 font-semibold text-lg">Lead Stages</h6>
                </div>
                <div class="card-body">
                    <ul class="flex flex-col gap-3">
                        @foreach ($stageStats as $stage)
                            <li class="flex items-center justify-between">
                                <span class="text-neutral-600 dark:text-neutral-200">{{ $stage['label'] }}</span>
                                <span class="font-semibold">{{ number_format($stage['total']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
