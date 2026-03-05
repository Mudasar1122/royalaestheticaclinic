@extends('layout.layout')

@php
    $title='Treatments';
    $subTitle = 'Worksheets';
@endphp

@section('content')

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-primary-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Active Plans</p>
                <h6 class="mb-0 dark:text-white">26</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">Across all treatments</p>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Sessions Today</p>
                <h6 class="mb-0 dark:text-white">14</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">Scheduled sessions</p>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-warning-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Paused</p>
                <h6 class="mb-0 dark:text-white">3</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">Patient requested</p>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-danger-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Completed</p>
                <h6 class="mb-0 dark:text-white">8</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">This week</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-6">
        <div class="xl:col-span-8">
            <div class="card h-full border-0">
                <div class="card-header border-bottom bg-white dark:bg-neutral-700">
                    <h6 class="mb-0 font-semibold text-lg">Active Treatment Plans</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Treatment</th>
                                    <th scope="col">Sessions</th>
                                    <th scope="col">Next Session</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Meera Shah</td>
                                    <td>HydraFacial</td>
                                    <td>2/6</td>
                                    <td>Fri 10:00 AM</td>
                                    <td><span class="bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400 px-3 py-1 rounded-full text-sm font-medium">In Progress</span></td>
                                </tr>
                                <tr>
                                    <td>Aisha Khan</td>
                                    <td>Laser</td>
                                    <td>1/4</td>
                                    <td>Thu 12:30 PM</td>
                                    <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-3 py-1 rounded-full text-sm font-medium">Scheduled</span></td>
                                </tr>
                                <tr>
                                    <td>Noura Aziz</td>
                                    <td>Dermal Fillers</td>
                                    <td>1/2</td>
                                    <td>Mon 02:00 PM</td>
                                    <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-3 py-1 rounded-full text-sm font-medium">Active</span></td>
                                </tr>
                                <tr>
                                    <td>Sana Noor</td>
                                    <td>PRP</td>
                                    <td>3/6</td>
                                    <td>Sat 11:15 AM</td>
                                    <td><span class="bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 px-3 py-1 rounded-full text-sm font-medium">Paused</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="xl:col-span-4">
            <div class="card h-full border-0">
                <div class="card-header border-bottom bg-white dark:bg-neutral-700">
                    <h6 class="mb-0 font-semibold text-lg">Worksheet Templates</h6>
                </div>
                <div class="card-body">
                    <ul class="flex flex-col gap-4">
                        <li class="flex items-center justify-between">
                            <span class="text-neutral-600 dark:text-neutral-200">HydraFacial</span>
                            <span class="text-sm font-semibold">6 fields</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-neutral-600 dark:text-neutral-200">Laser</span>
                            <span class="text-sm font-semibold">9 fields</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-neutral-600 dark:text-neutral-200">PRP</span>
                            <span class="text-sm font-semibold">7 fields</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-neutral-600 dark:text-neutral-200">Dermal Fillers</span>
                            <span class="text-sm font-semibold">5 fields</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-neutral-600 dark:text-neutral-200">Fat Dissolving</span>
                            <span class="text-sm font-semibold">6 fields</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-neutral-600 dark:text-neutral-200">Cryolipolysis</span>
                            <span class="text-sm font-semibold">8 fields</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
