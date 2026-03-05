@extends('layout.layout')

@php
    $title = 'Dashboard';
    $subTitle = 'Finance';
    $script = '<script src="' . asset('assets/js/homeOneChart.js') . '"></script>';
@endphp

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <button type="button" class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-700 rounded-lg h-full text-left transition hover:shadow-sm" data-finance-role="card" data-finance-target="income-details" data-finance-title="Total Income Details">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-white mb-1">Total Income</p>
                        <h6 class="mb-1 dark:text-white">$1,240,000</h6>
                        <span class="text-xs text-secondary-light">Fiscal year to date</span>
                    </div>
                    <div class="w-[50px] h-[50px] bg-success-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="solar:wallet-bold" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </button>

        <button type="button" class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-700 rounded-lg h-full text-left transition hover:shadow-sm" data-finance-role="card" data-finance-target="expenses-details" data-finance-title="Total Expenses Details">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-white mb-1">Total Expenses</p>
                        <h6 class="mb-1 dark:text-white">$620,000</h6>
                        <span class="text-xs text-secondary-light">Fiscal year to date</span>
                    </div>
                    <div class="w-[50px] h-[50px] bg-red-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="fa6-solid:file-invoice-dollar" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </button>

        <button type="button" class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-700 rounded-lg h-full text-left transition hover:shadow-sm" data-finance-role="card" data-finance-target="payable-details" data-finance-title="Payable Details">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-white mb-1">Payable</p>
                        <h6 class="mb-1 dark:text-white">$128,000</h6>
                        <span class="text-xs text-secondary-light">12 invoices due</span>
                    </div>
                    <div class="w-[50px] h-[50px] bg-warning-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="mdi:calendar-alert" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </button>

        <button type="button" class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-700 rounded-lg h-full text-left transition hover:shadow-sm" data-finance-role="card" data-finance-target="receivable-details" data-finance-title="Receivable Details">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-white mb-1">Receivable</p>
                        <h6 class="mb-1 dark:text-white">$196,000</h6>
                        <span class="text-xs text-secondary-light">18 invoices pending</span>
                    </div>
                    <div class="w-[50px] h-[50px] bg-primary-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="mdi:cash-multiple" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </button>
    </div>

    <div class="card h-full rounded-lg border-0 mt-6">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 py-4 px-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h6 id="finance-details-title" class="text-lg font-semibold mb-1">Payable Details</h6>
                <span class="text-sm font-medium text-secondary-light">Click any card to switch details</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg border border-neutral-200 dark:border-neutral-600 text-neutral-600 dark:text-neutral-200 bg-white dark:bg-neutral-700 transition" data-finance-role="tab" data-finance-target="income-details" data-finance-title="Total Income Details">Income</button>
                <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg border border-neutral-200 dark:border-neutral-600 text-neutral-600 dark:text-neutral-200 bg-white dark:bg-neutral-700 transition" data-finance-role="tab" data-finance-target="expenses-details" data-finance-title="Total Expenses Details">Expenses</button>
                <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg border border-neutral-200 dark:border-neutral-600 text-neutral-600 dark:text-neutral-200 bg-white dark:bg-neutral-700 transition" data-finance-role="tab" data-finance-target="payable-details" data-finance-title="Payable Details">Payable</button>
                <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg border border-neutral-200 dark:border-neutral-600 text-neutral-600 dark:text-neutral-200 bg-white dark:bg-neutral-700 transition" data-finance-role="tab" data-finance-target="receivable-details" data-finance-title="Receivable Details">Receivable</button>
            </div>
        </div>
        <div class="card-body p-6">
            <div id="income-details" class="hidden" data-finance-section>
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table style-two mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Source</th>
                                <th scope="col">Period</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Courses and Programs</td>
                                <td>Jan 2026</td>
                                <td>$420,000</td>
                                <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-4 py-1 rounded-full font-medium text-sm">Collected</span></td>
                            </tr>
                            <tr>
                                <td>Coworking Seats</td>
                                <td>Jan 2026</td>
                                <td>$110,000</td>
                                <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-4 py-1 rounded-full font-medium text-sm">Collected</span></td>
                            </tr>
                            <tr>
                                <td>Franchise Income</td>
                                <td>Jan 2026</td>
                                <td>$85,000</td>
                                <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-4 py-1 rounded-full font-medium text-sm">Invoiced</span></td>
                            </tr>
                            <tr>
                                <td>Campus Income</td>
                                <td>Jan 2026</td>
                                <td>$140,000</td>
                                <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-4 py-1 rounded-full font-medium text-sm">Invoiced</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="expenses-details" class="hidden" data-finance-section>
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table style-two mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Vendor</th>
                                <th scope="col">Category</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>MedSupply Co.</td>
                                <td>Consumables</td>
                                <td>$46,000</td>
                                <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-4 py-1 rounded-full font-medium text-sm">Pending</span></td>
                            </tr>
                            <tr>
                                <td>ClinicOps</td>
                                <td>Maintenance</td>
                                <td>$28,500</td>
                                <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-4 py-1 rounded-full font-medium text-sm">Paid</span></td>
                            </tr>
                            <tr>
                                <td>Marketing Hub</td>
                                <td>Advertising</td>
                                <td>$52,000</td>
                                <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-4 py-1 rounded-full font-medium text-sm">In Review</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="payable-details" class="hidden" data-finance-section>
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table style-two mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Vendor</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Dermal Supplies</td>
                                <td>Feb 14, 2026</td>
                                <td>$18,200</td>
                                <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-4 py-1 rounded-full font-medium text-sm">Due</span></td>
                            </tr>
                            <tr>
                                <td>ClinicOps</td>
                                <td>Feb 18, 2026</td>
                                <td>$24,000</td>
                                <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-4 py-1 rounded-full font-medium text-sm">Due</span></td>
                            </tr>
                            <tr>
                                <td>Royal Training</td>
                                <td>Feb 22, 2026</td>
                                <td>$12,500</td>
                                <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-4 py-1 rounded-full font-medium text-sm">Scheduled</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="receivable-details" class="hidden" data-finance-section>
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table style-two mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Client</th>
                                <th scope="col">Invoice</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Campus A</td>
                                <td>INV-24031</td>
                                <td>Feb 12, 2026</td>
                                <td>$36,000</td>
                            </tr>
                            <tr>
                                <td>Franchise South</td>
                                <td>INV-24042</td>
                                <td>Feb 20, 2026</td>
                                <td>$28,000</td>
                            </tr>
                            <tr>
                                <td>Coworking Downtown</td>
                                <td>INV-24057</td>
                                <td>Feb 27, 2026</td>
                                <td>$14,500</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-6">
        <div class="xl:col-span-6">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex flex-wrap items-center justify-between">
                        <div>
                            <h6 class="text-lg mb-1">Trend: Courses and Programs</h6>
                            <span class="text-sm font-medium text-secondary-light">Monthly revenue trend</span>
                        </div>
                        <select class="form-select bg-white dark:bg-neutral-700 form-select-sm w-auto">
                            <option>Monthly</option>
                            <option>Quarterly</option>
                            <option>Yearly</option>
                        </select>
                    </div>
                    <div id="trendProgramChart" class="pt-6 min-h-[260px]"></div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-6">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex flex-wrap items-center justify-between">
                        <div>
                            <h6 class="text-lg mb-1">Trend: Coworking Seats</h6>
                            <span class="text-sm font-medium text-secondary-light">Monthly seat utilization</span>
                        </div>
                        <select class="form-select bg-white dark:bg-neutral-700 form-select-sm w-auto">
                            <option>Monthly</option>
                            <option>Quarterly</option>
                            <option>Yearly</option>
                        </select>
                    </div>
                    <div id="trendCoworkingChart" class="pt-6 min-h-[260px]"></div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-6">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex flex-wrap items-center justify-between">
                        <div>
                            <h6 class="text-lg mb-1">Campus-wise Comparison</h6>
                            <span class="text-sm font-medium text-secondary-light">Revenue by campus</span>
                        </div>
                        <select class="form-select bg-white dark:bg-neutral-700 form-select-sm w-auto">
                            <option>Monthly</option>
                            <option>Quarterly</option>
                            <option>Yearly</option>
                        </select>
                    </div>
                    <div id="campusComparisonChart" class="pt-6 min-h-[260px]"></div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-6">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body p-6">
                    <div class="flex flex-wrap items-center justify-between">
                        <div>
                            <h6 class="text-lg mb-1">Franchise-wise Comparison</h6>
                            <span class="text-sm font-medium text-secondary-light">Revenue by franchise</span>
                        </div>
                        <select class="form-select bg-white dark:bg-neutral-700 form-select-sm w-auto">
                            <option>Monthly</option>
                            <option>Quarterly</option>
                            <option>Yearly</option>
                        </select>
                    </div>
                    <div id="franchiseComparisonChart" class="pt-6 min-h-[260px]"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
