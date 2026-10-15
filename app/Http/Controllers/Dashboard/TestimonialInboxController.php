<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\ConsentGuard;
use App\Services\InboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialInboxController extends Controller
{
    public function __construct(
        private readonly InboxService $inbox,
        private readonly ConsentGuard $consent,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->only([
            'space_id', 'rating', 'consent_state', 'status',
            'is_favorite', 'is_wall_of_love', 'is_hidden',
            'from', 'to', 'q',
        ]);

        $testimonials = $this->inbox->query($user, $filters);
        $spaces = $user->spaces()->orderBy('title')->get(['id', 'title']);

        return view('dashboard.inbox.index', [
            'testimonials' => $testimonials,
            'spaces' => $spaces,
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $action = $request->input('action');

        return match ($action) {
            'favorite' => $this->toggle($testimonial, 'is_favorite', $request, 'Favorite'),
            'wall_of_love' => $this->toggleWallOfLove($testimonial, $request),
            'hidden' => $this->toggle($testimonial, 'is_hidden', $request, 'Hide'),
            'edit' => $this->editText($testimonial, $request),
            default => back()->withErrors(['action' => 'Unknown action.']),
        };
    }

    public function destroy(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);
        $testimonial->delete();

        return redirect()
            ->route('dashboard.inbox.index')
            ->with('status', 'Testimonial deleted.');
    }

    private function toggle(Testimonial $t, string $field, Request $request, string $label): RedirectResponse
    {
        $t->{$field} = ! $t->{$field};
        $t->save();

        $onOff = $t->{$field} ? 'on' : 'off';

        return back()->with('status', "{$label} toggled {$onOff}.");
    }

    private function toggleWallOfLove(Testimonial $t, Request $request): RedirectResponse
    {
        $newValue = ! $t->is_wall_of_love;

        // The consent guard is the application-level enforcement of the
        // data-model §6 invariant. We only fire it when toggling TO true.
        // PRD §7: testimonials where consent was declined MUST NOT be
        // added to Wall of Love.
        if ($newValue) {
            if (! $this->consent->canPublish($t)) {
                return back()->withErrors([
                    'consent' => 'This testimonial does not have consent to be published on the Wall of Love.',
                ]);
            }
        }

        $t->is_wall_of_love = $newValue;
        $t->save();

        return back()->with('status', $newValue
            ? 'Added to Wall of Love.'
            : 'Removed from Wall of Love.');
    }

    private function editText(Testimonial $t, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'testimonial_text' => ['required', 'string', 'max:10000'],
            'submitter_name' => ['required', 'string', 'max:255'],
            'submitter_company' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $t->update($data);

        return back()->with('status', 'Testimonial updated.');
    }
}
