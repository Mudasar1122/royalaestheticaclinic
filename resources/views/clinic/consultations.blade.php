@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Activity Log';

    $formatPhoneWithGender = static function ($contact): string {
        $phone = trim((string) ($contact?->phone ?? ''));
        $gender = trim((string) ($contact?->gender ?? ''));
        $genderLabel = $gender !== '' ? ucfirst($gender) : '';

        if ($phone !== '' && $genderLabel !== '') {
            return $phone.' / '.$genderLabel;
        }

        if ($phone !== '') {
            return $phone;
        }

        return $genderLabel !== '' ? $genderLabel : '';
    };
@endphp

@section('content')
    <div class="card border-0">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Lead Communication & Activity History</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Lead</th>
                            <th>Type</th>
                            <th>Platform</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td>{{ $activity->happened_at?->format('d M Y h:i A') }}</td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-neutral-700 dark:text-neutral-100">{{ $activity->lead?->contact?->full_name ?? 'Unnamed Lead' }}</span>
                                        <span class="text-xs text-secondary-light">{{ $formatPhoneWithGender($activity->lead?->contact) !== '' ? $formatPhoneWithGender($activity->lead?->contact) : ($activity->lead?->contact?->email ?? 'No contact info') }}</span>
                                    </div>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $activity->platform)) }}</td>
                                <td class="max-w-[420px]">
                                    <span class="line-clamp-2 text-secondary-light">{{ $activity->message_text ?: 'No message body' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-secondary-light">No lead activities found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-t border-neutral-200 dark:border-neutral-600">
            {{ $activities->links() }}
        </div>
    </div>
@endsection
