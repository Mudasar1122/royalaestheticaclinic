<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\Messaging\TwilioWhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class HomeController extends Controller
{
        public function calendarMain()
        {
            return view('calendarMain');
        }

        public function chatEmpty()
        {
            return view('chatEmpty');
        }

        public function chatMessage()
        {
            return view('chatMessage');
        }

        public function chatProfile()
        {
            return view('chatProfile');
        }

        public function email()
        {
            $totalCustomers = Contact::query()->count();
            $emailableCustomers = Contact::query()
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->count();
            $whatsAppCustomers = Contact::query()
                ->where(function (Builder $query): void {
                    $query
                        ->whereNotNull('normalized_phone')
                        ->where('normalized_phone', '!=', '')
                        ->orWhere(function (Builder $nested): void {
                            $nested
                                ->whereNotNull('phone')
                                ->where('phone', '!=', '');
                        });
                })
                ->count();

            $recentCustomers = Contact::query()
                ->select(['id', 'full_name', 'email', 'phone'])
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $audienceOptions = $this->audienceOptions();
            $audienceCounts = [];
            foreach (array_keys($audienceOptions) as $audienceKey) {
                $audienceCounts[$audienceKey] = $this->countAudienceContacts($audienceKey);
            }

            return view('email', [
                'totalCustomers' => $totalCustomers,
                'emailableCustomers' => $emailableCustomers,
                'whatsAppCustomers' => $whatsAppCustomers,
                'recentCustomers' => $recentCustomers,
                'audienceOptions' => $audienceOptions,
                'audienceCounts' => $audienceCounts,
            ]);
        }

        public function sendCampaign(
            Request $request,
            TwilioWhatsAppService $twilioWhatsAppService
        ): RedirectResponse
        {
            $validated = $request->validate([
                'channel' => ['required', 'string', 'in:email,whatsapp'],
                'audience' => ['required', 'string', 'in:all,new,contacted,negotiation,booked,not_interested'],
                'subject' => ['nullable', 'string', 'max:160'],
                'message' => ['required', 'string', 'max:5000'],
                'manual_emails' => ['nullable', 'string', 'max:5000'],
                'manual_numbers' => ['nullable', 'string', 'max:5000'],
            ]);

            $channel = (string) $validated['channel'];
            $audience = (string) $validated['audience'];
            $subject = trim((string) ($validated['subject'] ?? ''));
            $message = trim((string) $validated['message']);
            $manualEmails = trim((string) ($validated['manual_emails'] ?? ''));
            $manualNumbers = trim((string) ($validated['manual_numbers'] ?? ''));

            if (
                $channel === 'email'
                && !($request->user()?->hasModulePermission('campaign_management', 'send_email_campaign') ?? false)
            ) {
                abort(403, 'You do not have permission to send email campaigns.');
            }

            if (
                $channel === 'whatsapp'
                && !($request->user()?->hasModulePermission('campaign_management', 'send_whatsapp_campaign') ?? false)
            ) {
                abort(403, 'You do not have permission to send WhatsApp campaigns.');
            }

            if ($channel === 'email' && $subject === '') {
                return back()
                    ->withErrors(['subject' => 'Campaign subject is required for email campaigns.'])
                    ->withInput();
            }

            if ($channel === 'email') {
                return $this->sendEmailCampaign(
                    $subject,
                    $message,
                    $audience,
                    $manualEmails
                );
            }

            return $this->sendWhatsAppCampaign(
                $message,
                $audience,
                $manualNumbers,
                $twilioWhatsAppService
            );
        }

        private function sendEmailCampaign(
            string $subject,
            string $message,
            string $audience,
            string $manualEmails
        ): RedirectResponse {
            $recipients = [];
            $recipientQuery = Contact::query()
                ->select(['id', 'full_name', 'email'])
                ->whereNotNull('email')
                ->where('email', '!=', '');

            $this->applyAudienceToContactQuery($recipientQuery, $audience);

            $recipientQuery
                ->orderBy('id')
                ->chunkById(200, function ($contacts) use (&$recipients): void {
                    foreach ($contacts as $contact) {
                        $email = strtolower(trim((string) $contact->email));

                        if ($email === '') {
                            continue;
                        }

                        $recipients[$email] = [
                            'address' => $email,
                            'name' => trim((string) ($contact->full_name ?? '')) !== ''
                                ? trim((string) $contact->full_name)
                                : 'Customer',
                            'contact_id' => $contact->id,
                        ];
                    }
                });

            $manualParse = $this->parseManualEmails($manualEmails);

            foreach ($manualParse['valid'] as $manualEmail) {
                $recipients[$manualEmail] = [
                    'address' => $manualEmail,
                    'name' => 'Manual Recipient',
                    'contact_id' => null,
                ];
            }

            if (count($recipients) === 0) {
                return back()
                    ->withErrors(['audience' => 'No email recipients found for selected audience.'])
                    ->withInput();
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    Mail::raw($message, function ($mail) use ($recipient, $subject): void {
                        $mail->to((string) $recipient['address'], (string) $recipient['name'])
                            ->subject($subject);
                    });

                    $sentCount++;
                } catch (Throwable $exception) {
                    $failedCount++;

                    Log::warning('Campaign email send failed.', [
                        'contact_id' => $recipient['contact_id'],
                        'email' => $recipient['address'],
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $status = "Email campaign sent to {$sentCount} recipient(s). Failed: {$failedCount}.";
            if (!empty($manualParse['invalid'])) {
                $status .= ' Invalid manual emails skipped: '.count($manualParse['invalid']).'.';
            }

            return redirect()
                ->route('email')
                ->with('campaign_status', $status);
        }

        private function sendWhatsAppCampaign(
            string $message,
            string $audience,
            string $manualNumbers,
            TwilioWhatsAppService $twilioWhatsAppService
        ): RedirectResponse {
            $twilioSid = trim((string) config('services.twilio.account_sid', ''));
            $twilioToken = trim((string) config('services.twilio.auth_token', ''));
            $twilioFrom = trim((string) config('crm.whatsapp.twilio.from', ''));

            if ($twilioSid === '' || $twilioToken === '' || $twilioFrom === '') {
                return back()
                    ->withErrors(['channel' => 'Twilio WhatsApp configuration is missing. Please set SID, token, and sender number.'])
                    ->withInput();
            }

            $recipients = [];
            $recipientQuery = Contact::query()
                ->select(['id', 'full_name', 'phone', 'normalized_phone'])
                ->where(function (Builder $query): void {
                    $query
                        ->whereNotNull('normalized_phone')
                        ->where('normalized_phone', '!=', '')
                        ->orWhere(function (Builder $nested): void {
                            $nested
                                ->whereNotNull('phone')
                                ->where('phone', '!=', '');
                        });
                });

            $this->applyAudienceToContactQuery($recipientQuery, $audience);

            $recipientQuery
                ->orderBy('id')
                ->chunkById(200, function ($contacts) use (&$recipients): void {
                    foreach ($contacts as $contact) {
                        $rawPhone = trim((string) ($contact->normalized_phone ?: $contact->phone));
                        $normalizedPhone = $this->normalizeCampaignPhone($rawPhone);

                        if ($normalizedPhone === null) {
                            continue;
                        }

                        $key = preg_replace('/\D+/', '', $normalizedPhone) ?? $normalizedPhone;
                        $recipients[$key] = [
                            'phone' => $normalizedPhone,
                            'contact_id' => $contact->id,
                        ];
                    }
                });

            $manualParse = $this->parseManualNumbers($manualNumbers);
            foreach ($manualParse['valid'] as $manualPhone) {
                $key = preg_replace('/\D+/', '', $manualPhone) ?? $manualPhone;
                $recipients[$key] = [
                    'phone' => $manualPhone,
                    'contact_id' => null,
                ];
            }

            if (count($recipients) === 0) {
                return back()
                    ->withErrors(['audience' => 'No WhatsApp recipients found for selected audience.'])
                    ->withInput();
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    $twilioWhatsAppService->sendTextMessage((string) $recipient['phone'], $message);
                    $sentCount++;
                } catch (Throwable $exception) {
                    $failedCount++;

                    Log::warning('Campaign WhatsApp send failed.', [
                        'contact_id' => $recipient['contact_id'],
                        'phone' => $recipient['phone'],
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $status = "WhatsApp campaign sent to {$sentCount} recipient(s). Failed: {$failedCount}.";
            if (!empty($manualParse['invalid'])) {
                $status .= ' Invalid manual numbers skipped: '.count($manualParse['invalid']).'.';
            }

            return redirect()
                ->route('email')
                ->with('campaign_status', $status);
        }

        public function pageError()
        {
            return view('pageError');
        }

        public function faq()
        {
            return view('faq');
        }

        public function gallery()
        {
            return view('gallery');
        }

        public function imageUpload()
        {
            return view('imageUpload');
        }

        public function kanban()
        {
            return view('kanban');
        }

        public function pricing()
        {
            return view('pricing');
        }

        public function starred()
        {
            return view('starred');
        }

        public function termsCondition()
        {
            return view('termsCondition');
        }

        public function typography()
        {
            return view('typography');
        }

        public function veiwDetails()
        {
            return view('veiwDetails');
        }

        public function widgets()
        {
            return view('widgets');
        }

        /**
         * @return array<string, string>
         */
        private function audienceOptions(): array
        {
            return [
                'all' => 'All Users',
                'new' => 'New',
                'contacted' => 'Contacted',
                'negotiation' => 'Negotiation',
                'booked' => 'Booked',
                'not_interested' => 'Not Interested',
            ];
        }

        private function countAudienceContacts(string $audience): int
        {
            $query = Contact::query();
            $this->applyAudienceToContactQuery($query, $audience);

            return $query->count();
        }

        private function applyAudienceToContactQuery(Builder $query, string $audience): void
        {
            $stages = $this->audienceStages($audience);

            if (empty($stages)) {
                return;
            }

            $query->whereHas('leads', function (Builder $leadQuery) use ($stages): void {
                $leadQuery->whereIn('stage', $stages);
            });
        }

        /**
         * @return array<int, string>
         */
        private function audienceStages(string $audience): array
        {
            return match ($audience) {
                'new' => ['new', 'initial'],
                'contacted' => ['contacted', 'visit'],
                'negotiation' => ['negotiation', 'proposal'],
                'booked' => ['booked', 'confirmed'],
                'not_interested' => ['not_interested'],
                default => [],
            };
        }

        /**
         * @return array{valid: array<int, string>, invalid: array<int, string>}
         */
        private function parseManualEmails(string $raw): array
        {
            $valid = [];
            $invalid = [];
            $tokens = preg_split('/[\r\n,;]+/', $raw) ?: [];

            foreach ($tokens as $token) {
                $candidate = strtolower(trim((string) $token));
                if ($candidate === '') {
                    continue;
                }

                if (!filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    $invalid[] = $candidate;
                    continue;
                }

                $valid[$candidate] = $candidate;
            }

            return [
                'valid' => array_values($valid),
                'invalid' => $invalid,
            ];
        }

        /**
         * @return array{valid: array<int, string>, invalid: array<int, string>}
         */
        private function parseManualNumbers(string $raw): array
        {
            $valid = [];
            $invalid = [];
            $tokens = preg_split('/[\r\n,;]+/', $raw) ?: [];

            foreach ($tokens as $token) {
                $candidate = trim((string) $token);
                if ($candidate === '') {
                    continue;
                }

                $normalized = $this->normalizeCampaignPhone($candidate);
                if ($normalized === null) {
                    $invalid[] = $candidate;
                    continue;
                }

                $key = preg_replace('/\D+/', '', $normalized) ?? $normalized;
                $valid[$key] = $normalized;
            }

            return [
                'valid' => array_values($valid),
                'invalid' => $invalid,
            ];
        }

        private function normalizeCampaignPhone(string $phone): ?string
        {
            $digits = preg_replace('/\D+/', '', $phone);

            if ($digits === null || $digits === '') {
                return null;
            }

            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            }

            if (str_starts_with($digits, '0')) {
                $digits = '92'.ltrim($digits, '0');
            }

            if (!str_starts_with($digits, '92') && strlen($digits) === 10) {
                $digits = '92'.$digits;
            }

            if (strlen($digits) < 10) {
                return null;
            }

            return '+'.$digits;
        }

}
