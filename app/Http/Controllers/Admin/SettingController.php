<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Services\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('update', Setting::class);

        return view('admin.settings', [
            'settings' => Setting::allValues(),
            'templates' => Setting::allStoryTemplates(),
        ]);
    }

    public function update(SettingRequest $request, ImageStore $images): RedirectResponse
    {
        $data = $request->safe()->except(['logo', 'og_image', 'eliminar_logo', 'social', 'story_templates']);

        $data['social'] = collect(Setting::SOCIAL_NETWORKS)
            ->mapWithKeys(fn ($network) => [$network => (string) $request->input("social.{$network}", '')])
            ->all();

        $data['story_templates'] = collect(Setting::STORY_TEMPLATES)->mapWithKeys(fn ($key) => [$key => [
            'name' => $request->input("story_templates.{$key}.name"),
            'accent' => strtoupper($request->input("story_templates.{$key}.accent")),
            'enabled' => $request->boolean("story_templates.{$key}.enabled"),
        ]])->all();

        if ($request->hasFile('logo')) {
            $images->delete(Setting::get('logo'));
            $data['logo'] = $images->store($request->file('logo'), 'site', 480, null);
        } elseif ($request->boolean('eliminar_logo')) {
            $images->delete(Setting::get('logo'));
            $data['logo'] = null;
        }

        if ($request->hasFile('og_image')) {
            $images->delete(Setting::get('og_image'));
            $data['og_image'] = $images->store($request->file('og_image'), 'site', 1200, null);
        }

        Setting::put($data);

        return redirect()->route('admin.settings.edit')->with('status', __('admin.settings_saved'));
    }
}
