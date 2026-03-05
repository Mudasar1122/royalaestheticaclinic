<?php

namespace App\Http\Controllers;

use App\Models\Lead;

class DashboardController extends Controller
{
    public function index()
    {
        return $this->crm();
    }

    public function crm()
    {
        $leadCards = [
            'new_leads' => Lead::query()->whereIn('stage', ['new', 'initial'])->count(),
            'whatsapp_leads' => Lead::query()->where('source_platform', 'whatsapp')->count(),
            'facebook_leads' => Lead::query()->where('source_platform', 'facebook')->count(),
            'tiktok_leads' => Lead::query()->where('source_platform', 'tiktok')->count(),
            'instagram_leads' => Lead::query()->where('source_platform', 'instagram')->count(),
            'google_business_leads' => Lead::query()->where('source_platform', 'google_business')->count(),
        ];

        return view('dashboard/index2', [
            'leadCards' => $leadCards,
        ]);
    }

    public function finance()
    {
        return view('dashboard/index');
    }

    public function index2()
    {
        return $this->crm();
    }

    public function index3()
    {
        return view('dashboard/index3');
    }

    public function index4()
    {
        return view('dashboard/index4');
    }

    public function index5()
    {
        return view('dashboard/index5');
    }

    public function index6()
    {
        return view('dashboard/index6');
    }

    public function index7()
    {
        return view('dashboard/index7');
    }

    public function index8()
    {
        return view('dashboard/index8');
    }

    public function index9()
    {
        return view('dashboard/index9');
    }
}
