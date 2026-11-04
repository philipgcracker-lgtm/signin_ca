<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Public feedback form, reached from a signed per-visitor link (see FeedbackLink).
 * Step 1 asks about the home (1-5 and comments, which feed the seller recap).
 * Step 2 is the thank-you page with the agent's Google review link, shown to
 * everyone whatever they rated: no filtering by score, no suggested review text.
 */
class FeedbackController extends Controller
{
    public function show(Request $request, int $visitor): View|RedirectResponse
    {
        $visitor = $this->visitor($visitor);

        if ($visitor->feedback_at !== null) {
            return redirect($this->thanksUrl($visitor));
        }

        return view('feedback.show', [
            'visitor' => $visitor,
            'event' => $visitor->event,
            'action' => URL::temporarySignedRoute('feedback.store', now()->addDays(2), ['visitor' => $visitor->id]),
        ]);
    }

    public function store(Request $request, int $visitor): RedirectResponse
    {
        $visitor = $this->visitor($visitor);

        // One answer per link: a second submit never overwrites the first.
        if ($visitor->feedback_at === null) {
            $data = $request->validate([
                'score' => ['required', 'integer', 'between:1,5'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ], ['score.required' => 'Please pick a rating from 1 to 5.']);

            $visitor->forceFill([
                'feedback_score' => (int) $data['score'],
                'feedback_notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
                'feedback_at' => now(),
            ])->save();
        }

        return redirect($this->thanksUrl($visitor));
    }

    public function thanks(Request $request, int $visitor): View|RedirectResponse
    {
        $visitor = $this->visitor($visitor);

        // Someone who opens this address without having answered goes to the form.
        if ($visitor->feedback_at === null) {
            return redirect(URL::temporarySignedRoute('feedback.show', now()->addDays(2), ['visitor' => $visitor->id]));
        }

        return view('feedback.thanks', [
            'event' => $visitor->event,
            'agent' => $visitor->event->agent,
            'reviewUrl' => $visitor->event->agent->google_review_url,
        ]);
    }

    private function thanksUrl(Visitor $visitor): string
    {
        return URL::temporarySignedRoute('feedback.thanks', now()->addDays(2), ['visitor' => $visitor->id]);
    }

    /**
     * Visitors whose details were removed, or on cancelled events, have no feedback page.
     */
    private function visitor(int $id): Visitor
    {
        $visitor = Visitor::query()->with('event.agent')->findOrFail($id);

        abort_if($visitor->anonymized_at !== null || $visitor->event === null || $visitor->event->isCancelled(), 404);

        return $visitor;
    }
}
