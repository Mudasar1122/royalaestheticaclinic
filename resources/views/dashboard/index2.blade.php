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
                        <span class="text-sm font-medium text-secondary-light whitespace-nowrap block overflow-x-auto">New -> Contacted -> Visit -> Proposal -> Booked</span>
                    </div>

                    <div class="mt-4">
                        <h3 class="mb-0 font-bold text-2xl">74 active leads</h3>
                    </div>

                    <div class="mt-4 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-gradient-to-b from-primary-50/70 to-neutral-50/70 dark:from-primary-900/10 dark:to-neutral-700/20 p-3">
                        <div id="revenue-chart" class="mt-0"></div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
                            <span class="text-secondary-light">New: 24</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-success-600"></span>
                            <span class="text-secondary-light">Contacted: 18</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-warning-600"></span>
                            <span class="text-secondary-light">Visit: 12</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                            <span class="text-secondary-light">Proposal: 9</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-pink-600"></span>
                            <span class="text-secondary-light">Booked: 11</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-info-600"></span>
                            <span class="text-secondary-light">Follow-up: 8</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-12 2xl:col-span-8">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex items-center flex-wrap gap-2 justify-between">
                        <h6 class="mb-2 font-bold text-lg">Lead Sources</h6>
                        <div class="">
                            <select class="form-select form-select-sm w-auto bg-white dark:bg-neutral-700 border text-secondary-light">
                                <option>Weekly</option>
                                <option>Monthly</option>
                                <option>Today</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center">
                                <span class="text-2xl line-height-1 flex align-content-center shrink-0 text-success-500 dark:text-success-500">
                                    <iconify-icon icon="ic:baseline-whatsapp" class="icon"></iconify-icon>
                                </span>
                                <div class="ps-4">
                                    <span class="text-neutral-600 dark:text-neutral-200 font-medium text-sm">WhatsApp</span>
                                    <span class="text-secondary-light text-xs block">42 leads - 68% response</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full max-w-[200px]">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                    <div class="bg-success-500 h-2.5 rounded-full" style="width: 68%"></div>
                                </div>
                                <span class="text-secondary-light font-xs font-semibold">68%</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center">
                                <span class="text-2xl line-height-1 flex align-content-center shrink-0 text-purple-600 dark:text-purple-500">
                                    <iconify-icon icon="ri:instagram-fill" class="icon"></iconify-icon>
                                </span>
                                <div class="ps-4">
                                    <span class="text-neutral-600 dark:text-neutral-200 font-medium text-sm">Instagram</span>
                                    <span class="text-secondary-light text-xs block">36 leads - 62% response</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full max-w-[200px]">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                    <div class="bg-purple-600 h-2.5 rounded-full" style="width: 62%"></div>
                                </div>
                                <span class="text-secondary-light font-xs font-semibold">62%</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center">
                                <span class="text-2xl line-height-1 flex align-content-center shrink-0 text-blue-600 dark:text-blue-500">
                                    <iconify-icon icon="ri:facebook-fill" class="icon"></iconify-icon>
                                </span>
                                <div class="ps-4">
                                    <span class="text-neutral-600 dark:text-neutral-200 font-medium text-sm">Facebook</span>
                                    <span class="text-secondary-light text-xs block">28 leads - 49% response</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full max-w-[200px]">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: 49%"></div>
                                </div>
                                <span class="text-secondary-light font-xs font-semibold">49%</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center">
                                <span class="text-2xl line-height-1 flex align-content-center shrink-0 text-neutral-900 dark:text-neutral-200">
                                    <iconify-icon icon="simple-icons:tiktok" class="icon"></iconify-icon>
                                </span>
                                <div class="ps-4">
                                    <span class="text-neutral-600 dark:text-neutral-200 font-medium text-sm">TikTok</span>
                                    <span class="text-secondary-light text-xs block">22 leads - 41% response</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full max-w-[200px]">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                    <div class="bg-neutral-900 h-2.5 rounded-full" style="width: 41%"></div>
                                </div>
                                <span class="text-secondary-light font-xs font-semibold">41%</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center">
                                <span class="text-2xl line-height-1 flex align-content-center shrink-0 text-warning-600 dark:text-warning-500">
                                    <iconify-icon icon="ri:google-fill" class="icon"></iconify-icon>
                                </span>
                                <div class="ps-4">
                                    <span class="text-neutral-600 dark:text-neutral-200 font-medium text-sm">Google Business</span>
                                    <span class="text-secondary-light text-xs block">19 leads - 54% response</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full max-w-[200px]">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                    <div class="bg-warning-600 h-2.5 rounded-full" style="width: 54%"></div>
                                </div>
                                <span class="text-secondary-light font-xs font-semibold">54%</span>
                            </div>
                        </div>
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
                            <span class="text-sm font-medium text-secondary-light">Skin Treatment, Hair Treatment, Botox</span>
                        </div>
                        <div class="">
                            <select class="form-select form-select-sm w-auto bg-white dark:bg-neutral-700 border text-secondary-light">
                                <option>Monthly</option>
                                <option>Quarterly</option>
                                <option>Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center mt-4">
                        <ul class="shrink-0">
                            <li class="flex items-center gap-2 mb-7">
                                <span class="w-3 h-3 rounded-full bg-primary-600"></span>
                                <span class="text-secondary-light text-sm font-medium">Skin Treatment: 52%</span>
                            </li>
                            <li class="flex items-center gap-2 mb-7">
                                <span class="w-3 h-3 rounded-full bg-success-600"></span>
                                <span class="text-secondary-light text-sm font-medium">Hair Treatment: 28%</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-warning-600"></span>
                                <span class="text-secondary-light text-sm font-medium">Botox &amp; Aesthetics: 20%</span>
                            </li>
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
                    <a href="javascript:void(0)" class="text-primary-600 dark:text-primary-600 hover-text-primary flex items-center gap-1">
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
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Amna Saeed</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2103</span>
                                        </div>
                                    </td>
                                    <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-4 py-1 rounded-full font-medium text-sm">Contacted</span></td>
                                    <td>Send clinic location</td>
                                    <td>Fatima</td>
                                    <td>WhatsApp</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Hira Nawaz</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2141</span>
                                        </div>
                                    </td>
                                    <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-4 py-1 rounded-full font-medium text-sm">Visit</span></td>
                                    <td>Confirm time 4:30 PM</td>
                                    <td>Rania</td>
                                    <td>Instagram</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Omar Sheikh</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2168</span>
                                        </div>
                                    </td>
                                    <td><span class="bg-purple-100 dark:bg-purple-600/25 text-purple-600 dark:text-purple-400 px-4 py-1 rounded-full font-medium text-sm">Proposal</span></td>
                                    <td>Share treatment plan</td>
                                    <td>Asad</td>
                                    <td>Facebook</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Ruba Ali</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2199</span>
                                        </div>
                                    </td>
                                    <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-4 py-1 rounded-full font-medium text-sm">Booked</span></td>
                                    <td>Send pre-visit checklist</td>
                                    <td>Maryam</td>
                                    <td>Google</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Talha Noor</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2205</span>
                                        </div>
                                    </td>
                                    <td><span class="bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 px-4 py-1 rounded-full font-medium text-sm">No Response</span></td>
                                    <td>Final attempt SMS</td>
                                    <td>Usman</td>
                                    <td>TikTok</td>
                                </tr>
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
                    <a href="javascript:void(0)" class="text-primary-600 dark:text-primary-600 hover-text-primary flex items-center gap-1">
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
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Sana Noor</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2211</span>
                                        </div>
                                    </td>
                                    <td>Skin Treatment</td>
                                    <td>Instagram</td>
                                    <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-4 py-1 rounded-full font-medium text-sm">Contacted</span></td>
                                    <td>Call back 5 PM</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Hamza Qureshi</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2220</span>
                                        </div>
                                    </td>
                                    <td>Hair Treatment</td>
                                    <td>WhatsApp</td>
                                    <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-4 py-1 rounded-full font-medium text-sm">Visit</span></td>
                                    <td>Book consultation</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Noura Aziz</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2244</span>
                                        </div>
                                    </td>
                                    <td>Botox</td>
                                    <td>Google Business</td>
                                    <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-4 py-1 rounded-full font-medium text-sm">Booked</span></td>
                                    <td>Send reminder</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Rima Farooq</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2256</span>
                                        </div>
                                    </td>
                                    <td>Skin Treatment</td>
                                    <td>Facebook</td>
                                    <td><span class="bg-purple-100 dark:bg-purple-600/25 text-purple-600 dark:text-purple-400 px-4 py-1 rounded-full font-medium text-sm">Proposal</span></td>
                                    <td>Share plan</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div>
                                            <span class="text-base block line-height-1 font-medium text-neutral-600 dark:text-neutral-200 text-w-200-px">Layla Ahmed</span>
                                            <span class="text-sm block font-normal text-secondary-light">LD-2269</span>
                                        </div>
                                    </td>
                                    <td>Hair Treatment</td>
                                    <td>TikTok</td>
                                    <td><span class="bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 px-4 py-1 rounded-full font-medium text-sm">New</span></td>
                                    <td>Initial reply</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
