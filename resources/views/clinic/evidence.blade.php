@extends('layout.layout')

@php
    $title='Evidence';
    $subTitle = 'Before / After';
@endphp

@section('content')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-primary-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Uploads Today</p>
                <h6 class="mb-0 dark:text-white">9</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">Images added</p>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Complete Sets</p>
                <h6 class="mb-0 dark:text-white">41</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">Before + After</p>
            </div>
        </div>
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 rounded-lg h-full bg-gradient-to-r from-warning-600/10 to-bg-white">
            <div class="card-body p-5">
                <p class="font-medium text-neutral-900 dark:text-white mb-1">Pending Review</p>
                <h6 class="mb-0 dark:text-white">6</h6>
                <p class="text-sm text-neutral-600 dark:text-white mt-3 mb-0">Awaiting approval</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-6">
        <div class="xl:col-span-7">
            <div class="card h-full border-0">
                <div class="card-header border-bottom bg-white dark:bg-neutral-700">
                    <h6 class="mb-0 font-semibold text-lg">Evidence Timeline</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Treatment</th>
                                    <th scope="col">Last Update</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Aisha Khan</td>
                                    <td>HydraFacial</td>
                                    <td>Today 11:10 AM</td>
                                    <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-3 py-1 rounded-full text-sm font-medium">Approved</span></td>
                                </tr>
                                <tr>
                                    <td>Meera Shah</td>
                                    <td>Laser</td>
                                    <td>Yesterday 05:40 PM</td>
                                    <td><span class="bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 px-3 py-1 rounded-full text-sm font-medium">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Noura Aziz</td>
                                    <td>Dermal Fillers</td>
                                    <td>Mon 02:20 PM</td>
                                    <td><span class="bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 px-3 py-1 rounded-full text-sm font-medium">Approved</span></td>
                                </tr>
                                <tr>
                                    <td>Sana Noor</td>
                                    <td>PRP</td>
                                    <td>Sun 10:05 AM</td>
                                    <td><span class="bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 px-3 py-1 rounded-full text-sm font-medium">Rejected</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="xl:col-span-5">
            <div class="card h-full border-0">
                <div class="card-header border-bottom bg-white dark:bg-neutral-700">
                    <h6 class="mb-0 font-semibold text-lg">Recent Before / After</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <img src="{{ asset('assets/images/users/user1.png') }}" alt="Before" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-600">
                            <p class="text-sm text-neutral-600 dark:text-neutral-200 mt-2 mb-0">Before</p>
                        </div>
                        <div class="text-center">
                            <img src="{{ asset('assets/images/users/user2.png') }}" alt="After" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-600">
                            <p class="text-sm text-neutral-600 dark:text-neutral-200 mt-2 mb-0">After</p>
                        </div>
                        <div class="text-center">
                            <img src="{{ asset('assets/images/users/user3.png') }}" alt="Before" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-600">
                            <p class="text-sm text-neutral-600 dark:text-neutral-200 mt-2 mb-0">Before</p>
                        </div>
                        <div class="text-center">
                            <img src="{{ asset('assets/images/users/user4.png') }}" alt="After" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-600">
                            <p class="text-sm text-neutral-600 dark:text-neutral-200 mt-2 mb-0">After</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
