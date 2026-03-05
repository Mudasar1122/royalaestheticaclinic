@extends('layout.layout')

@php
    $title = 'Campaign Management';
    $subTitle = 'Send Deals & Information';
@endphp

@section('content')
    @if (session('campaign_status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-6">
            {{ session('campaign_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8">
            <div class="card border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-6 py-4">
                    <h6 class="mb-0 font-semibold text-lg">General Campaign Message</h6>
                </div>
                <div class="card-body p-6">
                    <form method="POST" action="{{ route('emailCampaignSend') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="form-label">Campaign Subject</label>
                            <input
                                type="text"
                                name="subject"
                                value="{{ old('subject') }}"
                                class="form-control rounded-lg"
                                maxlength="160"
                                placeholder="Example: Limited Time Ramadan Deals"
                                required
                            >
                        </div>

                        <div>
                            <label class="form-label">Message for All Customers</label>
                            <textarea
                                name="message"
                                class="form-control rounded-lg"
                                rows="8"
                                maxlength="5000"
                                placeholder="Write your general deals and information text here..."
                                required
                            >{{ old('message') }}</textarea>
                            <p class="text-xs text-secondary-light mt-2 mb-0">This message will be sent to every customer that has an email address.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary text-sm btn-sm px-4 py-2 rounded-lg">
                                Send to All Customers
                            </button>
                            <a href="{{ route('email') }}" class="btn btn-light text-sm btn-sm px-4 py-2 rounded-lg border border-neutral-200 dark:border-neutral-600">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4">
            <div class="grid grid-cols-1 gap-4">
                <div class="card border border-neutral-200 dark:border-neutral-600 shadow-none rounded-lg">
                    <div class="card-body p-4">
                        <p class="text-sm text-secondary-light mb-1">Total Customers</p>
                        <h6 class="mb-0 dark:text-white">{{ number_format($totalCustomers) }}</h6>
                    </div>
                </div>
                <div class="card border border-neutral-200 dark:border-neutral-600 shadow-none rounded-lg">
                    <div class="card-body p-4">
                        <p class="text-sm text-secondary-light mb-1">Customers with Email</p>
                        <h6 class="mb-0 dark:text-white">{{ number_format($emailableCustomers) }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 mt-6">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-6 py-4">
            <h6 class="mb-0 font-semibold text-lg">Recent Customers (Campaign Audience Preview)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentCustomers as $customer)
                            <tr>
                                <td>{{ $customer->full_name ?: 'Unnamed' }}</td>
                                <td>{{ $customer->email ?: 'No email' }}</td>
                                <td>{{ $customer->phone ?: 'No phone' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-8 text-secondary-light">No customers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
