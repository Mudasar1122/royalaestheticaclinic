<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Lead;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        return $this->crm();
    }

    public function crm()
    {
        $user = request()->user();
        $pakistanNow = now('Asia/Karachi');
        $now = $pakistanNow->copy()->utc();
        $todayStart = $pakistanNow->copy()->startOfDay()->utc();
        $todayEnd = $pakistanNow->copy()->endOfDay()->utc();

        $stageTotals = Lead::query()
            ->visibleTo($user)
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $sourceTotals = Lead::query()
            ->visibleTo($user)
            ->selectRaw('source_platform, COUNT(*) as total')
            ->groupBy('source_platform')
            ->pluck('total', 'source_platform');

        $engagedSourceTotals = Lead::query()
            ->visibleTo($user)
            ->whereNotIn('stage', ['new', 'initial'])
            ->selectRaw('source_platform, COUNT(*) as total')
            ->groupBy('source_platform')
            ->pluck('total', 'source_platform');

        $leadCards = [
            'new_leads' => $this->countStages($stageTotals, ['new', 'initial']),
            'whatsapp_leads' => (int) ($sourceTotals->get('whatsapp') ?? 0),
            'facebook_leads' => (int) ($sourceTotals->get('facebook') ?? 0),
            'tiktok_leads' => (int) ($sourceTotals->get('tiktok') ?? 0),
            'instagram_leads' => (int) ($sourceTotals->get('instagram') ?? 0),
            'google_business_leads' => (int) ($sourceTotals->get('google_business') ?? 0),
        ];

        $pipelineDefinitions = [
            ['label' => 'New', 'stages' => ['new', 'initial'], 'dot_class' => 'bg-primary-600'],
            ['label' => 'Contacted', 'stages' => ['contacted', 'visit'], 'dot_class' => 'bg-success-600'],
            ['label' => 'Proposal', 'stages' => ['negotiation', 'proposal'], 'dot_class' => 'bg-purple-600'],
            ['label' => 'Booked', 'stages' => ['booked', 'confirmed'], 'dot_class' => 'bg-pink-600'],
            ['label' => 'Procedure Attempted', 'stages' => ['procedure_attempted'], 'dot_class' => 'bg-info-600'],
            ['label' => 'Not Interested', 'stages' => ['not_interested'], 'dot_class' => 'bg-info-600'],
        ];

        $pipelineStats = collect($pipelineDefinitions)
            ->map(function (array $definition) use ($stageTotals): array {
                return [
                    'label' => $definition['label'],
                    'total' => $this->countStages($stageTotals, $definition['stages']),
                    'dot_class' => $definition['dot_class'],
                ];
            })
            ->values();

        $sourceDefinitions = [
            ['key' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'ic:baseline-whatsapp', 'icon_class' => 'text-success-500 dark:text-success-500', 'bar_class' => 'bg-success-500'],
            ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'ri:instagram-fill', 'icon_class' => 'text-purple-600 dark:text-purple-500', 'bar_class' => 'bg-purple-600'],
            ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'ri:facebook-fill', 'icon_class' => 'text-blue-600 dark:text-blue-500', 'bar_class' => 'bg-blue-600'],
            ['key' => 'meta', 'label' => 'Lead Form', 'icon' => 'simple-icons:meta', 'icon_class' => 'text-sky-600 dark:text-sky-500', 'bar_class' => 'bg-sky-600'],
            ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => 'simple-icons:tiktok', 'icon_class' => 'text-neutral-900 dark:text-neutral-200', 'bar_class' => 'bg-neutral-900'],
            ['key' => 'google_business', 'label' => 'Google Business', 'icon' => 'ri:google-fill', 'icon_class' => 'text-warning-600 dark:text-warning-500', 'bar_class' => 'bg-warning-600'],
            ['key' => 'manual', 'label' => 'Walk In Lead', 'icon' => 'mdi:walk', 'icon_class' => 'text-info-600 dark:text-info-500', 'bar_class' => 'bg-info-600'],
        ];

        $sourcePerformance = collect($sourceDefinitions)
            ->map(function (array $definition) use ($sourceTotals, $engagedSourceTotals): array {
                $total = (int) ($sourceTotals->get($definition['key']) ?? 0);
                $engaged = (int) ($engagedSourceTotals->get($definition['key']) ?? 0);
                $responseRate = $total > 0 ? (int) round(($engaged / $total) * 100) : 0;

                return [
                    'label' => $definition['label'],
                    'icon' => $definition['icon'],
                    'icon_class' => $definition['icon_class'],
                    'bar_class' => $definition['bar_class'],
                    'total' => $total,
                    'response_rate' => max(0, min(100, $responseRate)),
                ];
            })
            ->values();

        $procedureOptions = $this->procedureOptions();
        $procedureTotals = [];

        Lead::query()
            ->visibleTo($user)
            ->select(['id', 'meta'])
            ->get()
            ->each(function (Lead $lead) use (&$procedureTotals): void {
                $meta = is_array($lead->meta) ? $lead->meta : [];
                $procedureKeys = is_array($meta['procedures_of_interest'] ?? null) ? $meta['procedures_of_interest'] : [];

                foreach ($procedureKeys as $procedureKey) {
                    $key = trim((string) $procedureKey);
                    if ($key === '') {
                        continue;
                    }

                    $procedureTotals[$key] = ($procedureTotals[$key] ?? 0) + 1;
                }
            });

        arsort($procedureTotals);
        $topProcedureTotals = array_slice($procedureTotals, 0, 3, true);
        $totalProcedureSelections = array_sum($procedureTotals);
        $serviceMixDotClasses = ['bg-primary-600', 'bg-success-600', 'bg-warning-600'];
        $serviceMix = [];
        $serviceMixIndex = 0;

        foreach ($topProcedureTotals as $procedureKey => $total) {
            $serviceMix[] = [
                'label' => $this->procedureLabel((string) $procedureKey, $procedureOptions),
                'total' => (int) $total,
                'percentage' => $totalProcedureSelections > 0
                    ? (int) round(((int) $total / $totalProcedureSelections) * 100)
                    : 0,
                'dot_class' => $serviceMixDotClasses[$serviceMixIndex] ?? 'bg-primary-600',
            ];
            $serviceMixIndex++;
        }

        $todayFollowUps = FollowUp::query()
            ->visibleTo($user)
            ->with(['lead.contact', 'assignedTo'])
            ->where('status', 'pending')
            ->whereBetween('due_at', [$todayStart, $todayEnd])
            ->orderBy('due_at')
            ->limit(10)
            ->get()
            ->map(function (FollowUp $followUp): array {
                $lead = $followUp->lead;
                $leadStage = (string) ($lead?->stage ?? $followUp->stage_snapshot ?? 'new');

                return [
                    'lead_name' => $lead?->contact?->full_name ?? 'Unnamed lead',
                    'lead_code' => $lead?->id !== null ? 'LD-'.str_pad((string) $lead->id, 4, '0', STR_PAD_LEFT) : '-',
                    'stage_label' => $this->stageLabel($leadStage),
                    'stage_class' => $this->stageBadgeClass($leadStage),
                    'next_action' => trim((string) ($followUp->summary ?? '')) !== ''
                        ? (string) $followUp->summary
                        : 'Follow-up due',
                    'owner' => $followUp->assignedTo?->name ?? 'Unassigned',
                    'channel' => $this->sourceLabel((string) ($lead?->source_platform ?? '')),
                ];
            })
            ->values();

        $recentLeads = Lead::query()
            ->visibleTo($user)
            ->with([
                'contact',
                'followUps' => static fn ($query) => $query
                    ->where('status', 'pending')
                    ->orderBy('due_at'),
            ])
            ->orderByDesc('last_activity_at')
            ->limit(10)
            ->get()
            ->map(function (Lead $lead) use ($procedureOptions): array {
                $meta = is_array($lead->meta) ? $lead->meta : [];
                $procedureKeys = is_array($meta['procedures_of_interest'] ?? null) ? $meta['procedures_of_interest'] : [];
                $primaryProcedureKey = collect($procedureKeys)
                    ->map(static fn ($value): string => trim((string) $value))
                    ->first(static fn (string $value): bool => $value !== '');

                $nextFollowUp = $lead->followUps->first();
                $nextAction = 'No pending follow-up';

                if ($nextFollowUp !== null && trim((string) ($nextFollowUp->summary ?? '')) !== '') {
                    $nextAction = (string) $nextFollowUp->summary;
                } elseif ($nextFollowUp?->due_at !== null) {
                    $nextAction = 'Follow-up at '.$nextFollowUp->due_at
                        ->timezone('Asia/Karachi')
                        ->format('d M h:i A').' PKT';
                }

                return [
                    'lead_name' => $lead->contact?->full_name ?? 'Unnamed lead',
                    'lead_code' => 'LD-'.str_pad((string) $lead->id, 4, '0', STR_PAD_LEFT),
                    'service' => $primaryProcedureKey !== null && $primaryProcedureKey !== false
                        ? $this->procedureLabel($primaryProcedureKey, $procedureOptions)
                        : 'Not specified',
                    'source' => $this->sourceLabel((string) $lead->source_platform),
                    'stage_label' => $this->stageLabel((string) $lead->stage),
                    'stage_class' => $this->stageBadgeClass((string) $lead->stage),
                    'next_action' => $nextAction,
                ];
            })
            ->values();

        $summary = [
            'total_leads' => Lead::query()->visibleTo($user)->count(),
            'active_leads' => Lead::query()->visibleTo($user)->where('status', 'open')->count(),
            'today_follow_ups' => FollowUp::query()
                ->visibleTo($user)
                ->where('status', 'pending')
                ->whereBetween('due_at', [$todayStart, $todayEnd])
                ->count(),
            'pending_follow_ups' => FollowUp::query()
                ->visibleTo($user)
                ->where('status', 'pending')
                ->count(),
        ];

        return view('dashboard/index2', [
            'leadCards' => $leadCards,
            'summary' => $summary,
            'pipelineStats' => $pipelineStats,
            'sourcePerformance' => $sourcePerformance,
            'serviceMix' => $serviceMix,
            'todayFollowUps' => $todayFollowUps,
            'recentLeads' => $recentLeads,
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

    private function countStages(Collection $stageTotals, array $stages): int
    {
        return collect($stages)->sum(
            static fn (string $stage): int => (int) ($stageTotals->get($stage) ?? 0)
        );
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'google_business' => 'Google Business',
            'meta' => 'Lead Form',
            'manual' => 'Walk In Lead',
            default => ucfirst(str_replace('_', ' ', $source !== '' ? $source : 'unknown')),
        };
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'initial', 'new' => 'New',
            'contacted', 'visit' => 'Contacted',
            'negotiation', 'proposal' => 'Proposal',
            'booked', 'confirmed' => 'Booked',
            'procedure_attempted' => 'Procedure Attempted',
            'not_interested' => 'Not Interested',
            default => ucfirst(str_replace('_', ' ', $stage !== '' ? $stage : 'new')),
        };
    }

    private function stageBadgeClass(string $stage): string
    {
        return match ($stage) {
            'initial', 'new' => 'bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400',
            'contacted', 'visit' => 'bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400',
            'negotiation', 'proposal' => 'bg-purple-100 dark:bg-purple-600/25 text-purple-600 dark:text-purple-400',
            'booked', 'confirmed' => 'bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400',
            'procedure_attempted' => 'bg-info-100 dark:bg-info-600/25 text-info-600 dark:text-info-400',
            'not_interested' => 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200',
            default => 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200',
        };
    }

    /**
     * @return array<string, string>
     */
    private function procedureOptions(): array
    {
        return [
            'laser_hair_removal' => 'Laser Hair Removal',
            'acne_acne_scars' => 'Acne / Acne Scars',
            'pigmentation_melasma_freckles' => 'Pigmentation / Melasma / Freckles',
            'anti_aging_face_lifting' => 'Anti-Aging / Face Lifting',
            'botox_dermal_fillers' => 'Botox / Dermal Fillers',
            'prp_face_hair' => 'PRP (Face / Hair)',
            'hair_restoration_hair_fall_treatment' => 'Hair Restoration / Hair Fall Treatment',
            'skin_tightening_hifu_rf' => 'Skin Tightening (HIFU / RF)',
            'chemical_peels_carbon_peel' => 'Chemical Peels / Carbon Peel',
            'body_contouring_fat_reduction' => 'Body Contouring / Fat Reduction',
            'stretch_marks' => 'Stretch Marks',
            'keloid_hypertrophic_scars' => 'Keloid / Hypertrophic Scars',
            'skin_whitening_brightening' => 'Skin Whitening / Brightening',
            'cosmetic_surgical_consultation' => 'Cosmetic / Surgical Consultation',
            'other' => 'Other',
        ];
    }

    /**
     * @param array<string, string> $procedureOptions
     */
    private function procedureLabel(string $key, array $procedureOptions): string
    {
        return $procedureOptions[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
