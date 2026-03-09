@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'WhatsApp Demo';
@endphp

@section('content')
    <div class="whatsapp-demo-page">
    @php
        $setup = $setup ?? [];
        $missingItems = [];

        if (empty($setup['sid_set'])) {
            $missingItems[] = 'TWILIO_ACCOUNT_SID is missing in .env';
        }

        if (empty($setup['token_set'])) {
            $missingItems[] = 'TWILIO_AUTH_TOKEN is missing in .env';
        }

        if (empty($setup['from_set'])) {
            $missingItems[] = 'TWILIO_WHATSAPP_FROM is missing in .env';
        }

        if (empty($setup['webhook_public_https'])) {
            $missingItems[] = 'Webhook URL must be public HTTPS (not localhost)';
        }
    @endphp

    @if (session('status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-6">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('whatsapp_demo'))
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first('whatsapp_demo') }}
        </div>
    @endif

    @if (!empty($missingItems))
        <div class="alert alert-warning px-4 py-3 rounded-lg mb-6">
            <h6 class="mb-2 font-semibold">WhatsApp Setup Required</h6>
            <ul class="mb-2">
                @foreach ($missingItems as $item)
                    <li>- {{ $item }}</li>
                @endforeach
            </ul>
            <p class="mb-0 text-sm">
                Current App URL: <span class="font-medium webhook-url">{{ $setup['app_url'] ?? '' }}</span><br>
                Current Webhook URL: <span class="font-medium webhook-url">{{ $setup['webhook_url'] ?? '' }}</span>
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Inbound Today</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($stats['inbound_today']) }}</h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Outbound Today</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($stats['outbound_today']) }}</h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Webhooks Today</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($stats['webhooks_today']) }}</h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Manual Alerts</p>
                <h5 class="mb-0 {{ $stats['manual_alerts'] > 0 ? 'text-danger-600' : 'dark:text-white' }}">
                    {{ number_format($stats['manual_alerts']) }}
                </h5>
            </div>
        </div>
    </div>

    <div class="card border-0 mb-6">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Send Test WhatsApp Message</h6>
            <p class="text-xs text-secondary-light mb-0 mt-1">
                Inbound webhook URL for Twilio: <span class="font-medium webhook-url">{{ $webhookUrl }}</span>
            </p>
            <p class="text-xs text-secondary-light mb-0 mt-1">
                You can override this URL with <span class="font-medium">TWILIO_WHATSAPP_WEBHOOK_URL</span> in `.env`.
            </p>
        </div>
        <div class="card-body p-6">
            <form action="{{ route('clinicWhatsAppDemoSend') }}" method="POST" class="grid grid-cols-12 gap-4">
                @csrf
                <div class="col-span-12 md:col-span-4">
                    <label class="form-label">To Number</label>
                    <input
                        type="text"
                        name="to"
                        class="form-control rounded-lg"
                        placeholder="+923001112233"
                        value="{{ old('to') }}"
                        required
                    >
                </div>
                <div class="col-span-12 md:col-span-4">
                    <label class="form-label">Contact Name (Optional)</label>
                    <input
                        type="text"
                        name="contact_name"
                        class="form-control rounded-lg"
                        placeholder="Customer name"
                        value="{{ old('contact_name') }}"
                    >
                </div>
                <div class="col-span-12 md:col-span-4">
                    <label class="form-label">Message</label>
                    <input
                        type="text"
                        name="message"
                        class="form-control rounded-lg"
                        placeholder="Write message..."
                        maxlength="1600"
                        value="{{ old('message') }}"
                        required
                    >
                </div>
                <div class="col-span-12">
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-lg">Send Test Message</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 mb-6">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Recent Incoming Webhooks</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table id="whatsapp-webhooks-table" class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Provider</th>
                            <th>From</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Lead</th>
                            <th>Event ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inboundWebhookEvents as $event)
                            <tr class="{{ !empty($event['is_highlighted']) ? 'bg-danger-50 dark:bg-danger-600/10' : '' }}">
                                <td>{{ ($event['received_at'] ?? null)?->format('d M Y h:i A') }}</td>
                                <td>{{ strtoupper((string) ($event['provider'] ?? '-')) }}</td>
                                <td>{{ $event['from'] ?? '-' }}</td>
                                <td>
                                    <div class="max-w-[280px]">
                                        <span class="line-clamp-2">{{ $event['body'] ?? '' }}</span>
                                        @if (!empty($event['error_message']))
                                            <p class="text-danger-600 text-xs mt-1 mb-0">{{ $event['error_message'] }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        {{ ($event['status'] ?? '') === 'processed' ? 'bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-300' : '' }}
                                        {{ ($event['status'] ?? '') === 'failed' ? 'bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-300' : '' }}
                                        {{ ($event['status'] ?? '') === 'processed_with_warning' ? 'bg-warning-100 dark:bg-warning-600/25 text-warning-700 dark:text-warning-300' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', (string) ($event['status'] ?? 'unknown'))) }}
                                    </span>
                                </td>
                                <td>{{ !empty($event['lead_id']) ? '#'.$event['lead_id'] : '-' }}</td>
                                <td>{{ $event['event_id'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-secondary-light">No inbound webhook events found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Recent WhatsApp Activity (Inbound + Outbound)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table id="whatsapp-activity-table" class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Direction</th>
                            <th>Customer</th>
                            <th>Lead</th>
                            <th>Message</th>
                            <th>Message ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($whatsAppActivities as $activity)
                            <tr>
                                <td>{{ $activity->happened_at?->format('d M Y h:i A') }}</td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $activity->direction === 'outbound' ? 'bg-primary-100 dark:bg-primary-600/20 text-primary-700 dark:text-primary-300' : 'bg-success-100 dark:bg-success-600/20 text-success-700 dark:text-success-300' }}">
                                        {{ ucfirst($activity->direction) }}
                                    </span>
                                </td>
                                <td>{{ $activity->contact?->full_name ?? $activity->contact?->phone ?? 'Unknown' }}</td>
                                <td>{{ $activity->lead_id ? '#'.$activity->lead_id : '-' }}</td>
                                <td><span class="line-clamp-2 max-w-[320px] block">{{ $activity->message_text }}</span></td>
                                <td>{{ $activity->platform_message_id ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-secondary-light">No WhatsApp activity found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        html,
        body {
            max-width: 100%;
            overflow-x: hidden !important;
        }

        #whatsapp-webhooks-table,
        #whatsapp-activity-table,
        .whatsapp-demo-page .table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed;
        }

        #whatsapp-webhooks-table th,
        #whatsapp-webhooks-table td,
        #whatsapp-activity-table th,
        #whatsapp-activity-table td {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: top;
        }

        #whatsapp-webhooks-table td:nth-child(4),
        #whatsapp-webhooks-table td:nth-child(7),
        #whatsapp-activity-table td:nth-child(5),
        #whatsapp-activity-table td:nth-child(6) {
            font-size: 0.85rem;
            line-height: 1.35;
        }

        #whatsapp-webhooks-table th:nth-child(1),
        #whatsapp-webhooks-table td:nth-child(1) { width: 14%; }
        #whatsapp-webhooks-table th:nth-child(2),
        #whatsapp-webhooks-table td:nth-child(2) { width: 9%; }
        #whatsapp-webhooks-table th:nth-child(3),
        #whatsapp-webhooks-table td:nth-child(3) { width: 14%; }
        #whatsapp-webhooks-table th:nth-child(4),
        #whatsapp-webhooks-table td:nth-child(4) { width: 28%; }
        #whatsapp-webhooks-table th:nth-child(5),
        #whatsapp-webhooks-table td:nth-child(5) { width: 14%; }
        #whatsapp-webhooks-table th:nth-child(6),
        #whatsapp-webhooks-table td:nth-child(6) { width: 8%; }
        #whatsapp-webhooks-table th:nth-child(7),
        #whatsapp-webhooks-table td:nth-child(7) { width: 13%; }

        #whatsapp-activity-table th:nth-child(1),
        #whatsapp-activity-table td:nth-child(1) { width: 14%; }
        #whatsapp-activity-table th:nth-child(2),
        #whatsapp-activity-table td:nth-child(2) { width: 10%; }
        #whatsapp-activity-table th:nth-child(3),
        #whatsapp-activity-table td:nth-child(3) { width: 16%; }
        #whatsapp-activity-table th:nth-child(4),
        #whatsapp-activity-table td:nth-child(4) { width: 8%; }
        #whatsapp-activity-table th:nth-child(5),
        #whatsapp-activity-table td:nth-child(5) { width: 32%; }
        #whatsapp-activity-table th:nth-child(6),
        #whatsapp-activity-table td:nth-child(6) { width: 20%; }

        .whatsapp-demo-page {
            max-width: 100%;
            overflow-x: hidden;
        }

        .whatsapp-demo-page .table-responsive,
        .whatsapp-demo-page .dataTables_wrapper,
        .whatsapp-demo-page .dataTables_scroll,
        .whatsapp-demo-page .dataTables_scrollHead,
        .whatsapp-demo-page .dataTables_scrollBody {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        .whatsapp-demo-page .card,
        .whatsapp-demo-page .card-header,
        .whatsapp-demo-page .card-body,
        .whatsapp-demo-page .table-responsive,
        .whatsapp-demo-page .grid {
            min-width: 0;
        }

        .dashboard-main,
        .dashboard-main-body {
            max-width: 100%;
            overflow-x: hidden;
        }

        .whatsapp-demo-page .webhook-url {
            word-break: break-all;
            overflow-wrap: anywhere;
        }
    </style>
    </div>
@endsection
