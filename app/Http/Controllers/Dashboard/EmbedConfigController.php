<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmbedConfigRequest;
use App\Models\EmbedConfig;
use App\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmbedConfigController extends Controller
{
    public function index(Request $request, Space $space): View
    {
        $this->authorize('view', $space);

        $configs = $space->embedConfigs()->orderByDesc('created_at')->get();

        return view('dashboard.embeds.index', [
            'space' => $space,
            'configs' => $configs,
        ]);
    }

    public function create(Request $request, Space $space): View
    {
        $this->authorize('update', $space);

        return view('dashboard.embeds.create', [
            'space' => $space,
            'config' => null,
        ]);
    }

    public function store(StoreEmbedConfigRequest $request, Space $space): RedirectResponse
    {
        $this->authorize('update', $space);

        $embed = $space->embedConfigs()->create($request->validated());

        return redirect()
            ->route('dashboard.embeds.index', $space)
            ->with('status', 'Embed created.');
    }

    public function edit(Request $request, Space $space, EmbedConfig $embedConfig): View
    {
        $this->authorize('update', $space);

        return view('dashboard.embeds.edit', [
            'space' => $space,
            'config' => $embedConfig,
        ]);
    }

    public function update(Request $request, Space $space, EmbedConfig $embedConfig): RedirectResponse
    {
        $this->authorize('update', $space);

        $data = $request->validate([
            'layout' => ['required', 'in:masonry,carousel'],
            'theme_mode' => ['required', 'in:light,dark'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'animation_enabled' => ['required', 'boolean'],
        ]);

        $embedConfig->update($data);

        return back()->with('status', 'Embed updated.');
    }

    public function destroy(Request $request, Space $space, EmbedConfig $embedConfig): RedirectResponse
    {
        $this->authorize('update', $space);

        $embedConfig->delete();

        return back()->with('status', 'Embed deleted.');
    }
}
