@extends('layout.layout')
@php
    $title = 'CRM Dashboard';
    $subTitle = 'Royal Aesthetica';
    $script = '<script src="' . asset('assets/js/homeTwoChart.js') . '"></script>';
@endphp

@section('content')
    @php
        $metricCards = [
            [
                'label' => 'New Leads',
                'value' => number_format($leadCards['new_leads'] ?? 0),
                'note' => 'Initial stage',
                'icon' => 'mdi:account-plus',
                'iconStyle' => 'background-color:#f59e0b;color:#ffffff;',
                'cardStyle' => 'background:linear-gradient(100deg, rgba(245,158,11,0.14), #ffffff);',
                'chartId' => 'new-user-chart',
            ],
            [
                'label' => 'WhatsApp',
                'value' => number_format($leadCards['whatsapp_leads'] ?? 0),
                'note' => 'From WhatsApp',
                'icon' => 'ic:baseline-whatsapp',
                'iconStyle' => 'background-color:#25D366;color:#ffffff;',
                'cardStyle' => 'background:linear-gradient(100deg, rgba(37,211,102,0.14), #ffffff);',
                'chartId' => 'active-user-chart',
            ],
            [
                'label' => 'Facebook',
                'value' => number_format($leadCards['facebook_leads'] ?? 0),
                'note' => 'From Facebook',
                'icon' => 'ic:baseline-facebook',
                'iconStyle' => 'background-color:#1877F2;color:#ffffff;',
                'cardStyle' => 'background:linear-gradient(100deg, rgba(24,119,242,0.14), #ffffff);',
                'chartId' => 'total-sales-chart',
            ],
            [
                'label' => 'TikTok',
                'value' => number_format($leadCards['tiktok_leads'] ?? 0),
                'note' => 'From TikTok',
                'icon' => 'ri:tiktok-fill',
                'iconStyle' => 'background-color:#111111;color:#ffffff;',
                'cardStyle' => 'background:linear-gradient(100deg, rgba(17,17,17,0.1), #ffffff);',
                'chartId' => 'conversion-user-chart',
            ],
            [
                'label' => 'Instagram',
                'value' => number_format($leadCards['instagram_leads'] ?? 0),
                'note' => 'From Instagram',
                'icon' => 'ri:instagram-fill',
                'iconStyle' => 'background-color:#E4405F;color:#ffffff;',
                'cardStyle' => 'background:linear-gradient(100deg, rgba(228,64,95,0.14), #ffffff);',
                'chartId' => 'leads-chart',
            ],
            [
                'label' => 'Google Business',
                'value' => number_format($leadCards['google_business_leads'] ?? 0),
                'note' => 'From Google Business',
                'icon' => 'ri:google-fill',
                'iconStyle' => 'background-color:#4285F4;color:#ffffff;',
                'cardStyle' => 'background:linear-gradient(100deg, rgba(66,133,244,0.14), #ffffff);',
                'chartId' => 'total-profit-chart',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">
        <div class="lg:col-span-12 2xl:col-span-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach ($metricCards as $card)
                    <div class="card border border-neutral-200 dark:border-neutral-600 rounded-xl h-full shadow-none" style="{{ $card['cardStyle'] }}">
                        <div class="card-body p-4 min-h-[185px] flex flex-col">
                            <div class="flex items-start gap-2.5">
                                <span class="w-[72px] h-[72px] shrink-0 flex justify-center items-center rounded-full" style="{{ $card['iconStyle'] }}">
                                    <iconify-icon icon="{{ $card['icon'] }}" width="46" height="46"></iconify-icon>
                                </span>
                                <div class="flex-1">
                                    <span class="font-semibold text-neutral-900 dark:text-white text-base block leading-tight">{{ $card['label'] }}</span>
                                    <h3 class="font-bold text-3xl mt-1.5 mb-0 leading-none">{{ $card['value'] }}</h3>
                                </div>
                            </div>

                            <p class="text-sm text-secondary-light mt-3 mb-0">{{ $card['note'] }}</p>

                            <div class="mt-auto pt-3">
                                <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-600 bg-white/70 dark:bg-neutral-700/20 px-2 py-1">
                                    <div id="{{ $card['chartId'] }}" class="remove-tooltip-title rounded-tooltip-value"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-12 2xl:col-span-4">
            <div class="card h-full rounded-xl border border-neutral-200 dark:border-neutral-600 shadow-sm bg-white dark:bg-neutral-800">
                <div class="card-body p-6 h-full flex flex-col">
                    <div>
                        <h6 class="mb-1 font-bold text-lg">Lead Pipeline</h6>
                        <span class="text-sm font-medium text-secondary-light whitespace-nowrap block overflow-x-auto">
                            New -> Contacted -> Proposal -> Booked
                        </span>
                    </div>

                    <div class="mt-4">
                        <h3 class="mb-0 font-bold text-2xl">{{ number_format($summary['active_leads'] ?? 0) }} active leads</h3>
                        <span class="text-sm text-secondary-light">
                            {{ number_format($summary['pending_follow_ups'] ?? 0) }} total pending follow-ups
                        </span>
                    </div>

                    <div class="mt-4 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-gradient-to-b from-primary-50/70 to-neutral-50/70 dark:from-primary-900/10 dark:to-neutral-700/20 p-3">
                        <div id="revenue-chart" class="mt-0"></div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        @foreach ($pipelineStats as $pipelineStat)
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $pipelineStat['dot_class'] }}"></span>
                                <span class="text-secondary-light">
                                    {{ $pipelineStat['label'] }}: {{ number_format($pipelineStat['total']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-12 2xl:col-span-8">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex items-center flex-wrap gap-2 justify-between">
                        <h6 class="mb-2 font-bold text-lg">Lead Sources</h6>
                        <div class="text-sm text-secondary-light">
                            Live from CRM leads
                        </div>
                    </div>

                    <div class="mt-4">
                        @forelse ($sourcePerformance as $source)
                            <div class="flex items-center justify-between gap-3 {{ !$loop->last ? 'mb-4' : '' }}">
                                <div class="flex items-center">
                                    <span class="text-2xl line-height-1 flex align-content-center shrink-0 {{ $source['icon_class'] }}">
                                        <iconify-icon icon="{{ $source['icon'] }}" class="icon"></iconify-icon>
                                    </span>
                                    <div class="ps-4">
                                        <span class="text-neutral-600 dark:text-neutral-200 font-medium text-sm">{{ $source['label'] }}</span>
                                        <span class="text-secondary-light text-xs block">
                                            {{ number_format($source['total']) }} leads - {{ $source['response_rate'] }}% response
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 w-full max-w-[200px]">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                        <div class="{{ $source['bar_class'] }} h-2.5 rounded-full" style="width: {{ $source['response_rate'] }}%"></div>
                                    </div>
                                    <span class="text-secondary-light font-xs font-semibold">{{ $source['response_rate'] }}%</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary-light mb-0">No lead source data available.</p>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>

        <div class="lg:col-span-12 2xl:col-span-4">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex items-center flex-wrap gap-2 justify-between">
                        <div>
                            <h6 class="mb-2 font-bold text-lg">Service Mix</h6>
                            <span class="text-sm font-medium text-secondary-light">Top procedures selected by leads</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center mt-4">
                        <ul class="shrink-0">
                            @forelse ($serviceMix as $mix)
                                <li class="flex items-center gap-2 {{ !$loop->last ? 'mb-7' : '' }}">
                                    <span class="w-3 h-3 rounded-full {{ $mix['dot_class'] }}"></span>
                                    <span class="text-secondary-light text-sm font-medium">
                                        {{ $mix['label'] }}: {{ $mix['percentage'] }}% ({{ number_format($mix['total']) }})
                                    </span>
                                </li>
                            @empty
                                <li class="text-secondary-light text-sm font-medium">
                                    No procedure interest data available yet.
                                </li>
                            @endforelse
                        </ul>
                        <div id="donutChart" class="grow apexcharts-tooltip-z-none title-style circle-none"></div>
                    </div>

                </div>
            </div>
        </div>

        <div class="lg:col-span-12 2xl:col-span-12">
            <div class="card h-full border-0 overflow-hidden">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 py-4 px-6 flex items-center justify-between">
                    <h6 class="text-lg font-semibold mb-0">Follow-ups Due Today</h6>
                    <a href="{{ route('clinicAppointments', ['tab' => 'today']) }}" class="text-primary-600 dark:text-primary-600 hover-text-primary flex items-center gap-1">
                        View All
                        <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                    </a>
                </div>
                <div class="card-body p-6">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table style-two mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Lead</th>
                                    <th scope="col">Stage</th>
                                    <th scope="col">Next Action</th>
                                    <th scope="col">Owner</th>
                                    <th scope="col">Channel</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todayFollowUps as $followUp)
                                    <tr>
                                        <td>
                                            <div>
                                                <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">{{ $followUp['lead_name'] }}</span>
                                                <span class="text-sm block font-normal text-secondary-light">{{ $followUp['lead_code'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="{{ $followUp['stage_class'] }} px-4 py-1 rounded-full font-medium text-sm">
                                                {{ $followUp['stage_label'] }}
                                            </span>
                                        </td>
                                        <td>{{ $followUp['next_action'] }}</td>
                                        <td>{{ $followUp['owner'] }}</td>
                                        <td>{{ $followUp['channel'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary-light py-6">No follow-ups due today.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-12 2xl:col-span-12">
            <div class="card h-full border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 py-4 px-6 flex items-center justify-between">
                    <h6 class="text-lg font-semibold mb-0">Recent Leads</h6>
                    <a href="{{ route('clinicLeads') }}" class="text-primary-600 dark:text-primary-600 hover-text-primary flex items-center gap-1">
                        View All
                        <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                    </a>
                </div>
                <div class="card-body p-6">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table style-two mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Lead</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Source</th>
                                    <th scope="col">Stage</th>
                                    <th scope="col">Next Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLeads as $lead)
                                    <tr>
                                        <td>
                                            <div>
                                                <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">{{ $lead['lead_name'] }}</span>
                                                <span class="text-sm block font-normal text-secondary-light">{{ $lead['lead_code'] }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $lead['service'] }}</td>
                                        <td>{{ $lead['source'] }}</td>
                                        <td>
                                            <span class="{{ $lead['stage_class'] }} px-4 py-1 rounded-full font-medium text-sm">
                                                {{ $lead['stage_label'] }}
                                            </span>
                                        </td>
                                        <td>{{ $lead['next_action'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary-light py-6">No leads available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
