<?php

namespace App\Http\Controllers;

use App\Models\Contact;
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

            $recentCustomers = Contact::query()
                ->select(['id', 'full_name', 'email', 'phone'])
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            return view('email', [
                'totalCustomers' => $totalCustomers,
                'emailableCustomers' => $emailableCustomers,
                'recentCustomers' => $recentCustomers,
            ]);
        }

        public function sendCampaign(Request $request): RedirectResponse
        {
            $validated = $request->validate([
                'subject' => ['required', 'string', 'max:160'],
                'message' => ['required', 'string', 'max:5000'],
            ]);

            $subject = trim((string) $validated['subject']);
            $message = trim((string) $validated['message']);

            $recipientQuery = Contact::query()
                ->whereNotNull('email')
                ->where('email', '!=', '');

            $recipientCount = (clone $recipientQuery)->count();

            if ($recipientCount === 0) {
                return back()
                    ->withErrors(['subject' => 'No customer email addresses found.'])
                    ->withInput();
            }

            $sentCount = 0;
            $failedCount = 0;

            $recipientQuery
                ->orderBy('id')
                ->chunkById(100, function ($contacts) use (&$sentCount, &$failedCount, $subject, $message): void {
                    foreach ($contacts as $contact) {
                        try {
                            Mail::raw($message, function ($mail) use ($contact, $subject): void {
                                $mail->to((string) $contact->email, (string) ($contact->full_name ?? 'Customer'))
                                    ->subject($subject);
                            });

                            $sentCount++;
                        } catch (Throwable $exception) {
                            $failedCount++;

                            Log::warning('Campaign send failed for contact.', [
                                'contact_id' => $contact->id,
                                'email' => $contact->email,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    }
                });

            return redirect()
                ->route('email')
                ->with('campaign_status', "Campaign sent to {$sentCount} customer(s). Failed: {$failedCount}.");
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

}
