@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Follow-ups';
@endphp

@section('content')
    @if (session('status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-6">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Pending Follow-ups</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($pendingCount) }}</h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Overdue Follow-ups</p>
                <h5 class="mb-0 {{ $overdueCount > 0 ? 'text-danger-600' : 'dark:text-white' }}">{{ number_format($overdueCount) }}</h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Completed Today</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($completedTodayCount) }}</h5>
            </div>
        </div>
    </div>

    <div class="card border-0 mt-6">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Follow-up Queue</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Stage</th>
                            <th>Trigger</th>
                            <th>Due At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($followUps as $followUp)
                            <tr>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-neutral-700 dark:text-neutral-100">{{ $followUp->lead?->contact?->full_name ?? 'Unnamed Lead' }}</span>
                                        <span class="text-xs text-secondary-light">{{ $followUp->lead?->source_platform ? ucfirst(str_replace('_', ' ', $followUp->lead->source_platform)) : 'Unknown source' }}</span>
                                    </div>
                                </td>
                                <td>{{ ucfirst($followUp->stage_snapshot) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $followUp->trigger_type)) }}</td>
                                <td>
                                    <span class="{{ $followUp->status === 'pending' && $followUp->due_at < now() ? 'text-danger-600 font-medium' : '' }}">
                                        {{ $followUp->due_at?->format('d M Y h:i A') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium
                                        {{ $followUp->status === 'pending' ? 'bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-300' : '' }}
                                        {{ $followUp->status === 'completed' ? 'bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-300' : '' }}
                                        {{ $followUp->status === 'cancelled' ? 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200' : '' }}">
                                        {{ ucfirst($followUp->status) }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('clinicFollowUpStatusUpdate', $followUp) }}" method="POST" class="flex items-center gap-2 min-w-[190px]">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select rounded-lg">
                                            <option value="pending" @selected($followUp->status === 'pending')>Pending</option>
                                            <option value="completed" @selected($followUp->status === 'completed')>Completed</option>
                                            <option value="cancelled" @selected($followUp->status === 'cancelled')>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary text-xs px-3 py-2 rounded-lg">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-secondary-light">No follow-ups available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-t border-neutral-200 dark:border-neutral-600">
            {{ $followUps->links() }}
        </div>
    </div>
@endsection
