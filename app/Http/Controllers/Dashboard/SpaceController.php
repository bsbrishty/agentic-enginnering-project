<?php

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\SpaceLimitReachedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpaceRequest;
use App\Http\Requests\UpdateSpaceRequest;
use App\Models\Space;
use App\Services\SpaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaceController extends Controller
{
    public function __construct(private readonly SpaceService $spaces)
    {
    }

    public function index(Request $request): View
    {
        $spaces = $request->user()->spaces()
            ->withCount('testimonials')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.spaces.index', ['spaces' => $spaces]);
    }

    public function create(): View
    {
        return view('dashboard.spaces.create', [
            'defaultFieldsConfig' => $this->defaultFieldsConfig(),
        ]);
    }

    public function store(StoreSpaceRequest $request): RedirectResponse
    {
        try {
            $space = $this->spaces->create($request->user(), $request->validated());
        } catch (SpaceLimitReachedException $e) {
            return back()
                ->withErrors(['limit' => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('dashboard.spaces.show', $space)
            ->with('status', 'Space created. Share the URL below to start collecting testimonials.');
    }

    public function show(Request $request, Space $space): View
    {
        $this->authorize('view', $space);

        $space->loadCount('testimonials');
        $embedConfigs = $space->embedConfigs()->orderByDesc('created_at')->get();

        return view('dashboard.spaces.show', [
            'space' => $space,
            'embedConfigs' => $embedConfigs,
        ]);
    }

    public function edit(Request $request, Space $space): View
    {
        $this->authorize('update', $space);

        return view('dashboard.spaces.edit', ['space' => $space]);
    }

    public function update(UpdateSpaceRequest $request, Space $space): RedirectResponse
    {
        $this->authorize('update', $space);

        $this->spaces->update($space, $request->validated());

        return redirect()
            ->route('dashboard.spaces.show', $space)
            ->with('status', 'Space updated.');
    }

    public function destroy(Request $request, Space $space): RedirectResponse
    {
        $this->authorize('delete', $space);

        $space->delete();

        return redirect()
            ->route('dashboard.spaces.index')
            ->with('status', 'Space deleted.');
    }

    /**
     * The default `fields_config` array for a freshly-created space.
     * Per docs/data-model.md §3.2.1.
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultFieldsConfig(): array
    {
        return [
            ['key' => 'name',             'type' => 'text',     'enabled' => true,  'required' => true],
            ['key' => 'email',            'type' => 'email',    'enabled' => true,  'required' => true],
            ['key' => 'testimonial_text', 'type' => 'textarea', 'enabled' => true,  'required' => true],
            ['key' => 'company',          'type' => 'text',     'enabled' => false, 'required' => false],
            ['key' => 'social_links',     'type' => 'url',      'enabled' => false, 'required' => false],
            ['key' => 'profile_photo',    'type' => 'file',     'enabled' => false, 'required' => false],
        ];
    }
}
