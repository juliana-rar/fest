<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hex = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

        return [
            'hero_title_ca' => ['required', 'string', 'max:120'],
            'hero_title_es' => ['required', 'string', 'max:120'],
            'hero_subtitle_ca' => ['nullable', 'string', 'max:300'],
            'hero_subtitle_es' => ['nullable', 'string', 'max:300'],
            'logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:2048'],
            'eliminar_logo' => ['nullable', 'boolean'],
            'color_primary' => $hex,
            'color_secondary' => $hex,
            'color_background' => $hex,
            'social' => ['array'],
            'social.*' => ['nullable', 'url:https', 'max:255'],
            'seo_title' => ['required', 'string', 'max:70'],
            'seo_description' => ['required', 'string', 'max:170'],
            'og_image' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:4096'],
            'short_url' => ['required', 'string', 'max:60'],
            'story_default' => ['required', Rule::in(Setting::STORY_TEMPLATES)],
            'story_templates' => ['required', 'array'],
            'story_templates.*.name' => ['required', 'string', 'max:40'],
            'story_templates.*.accent' => $hex,
            'story_templates.*.enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $enabled = collect($this->input('story_templates', []))->filter(fn ($t) => ! empty($t['enabled']));
            if ($enabled->isEmpty()) {
                $validator->errors()->add('story_templates', __('admin.story_one_required'));
            } elseif (! $enabled->has($this->input('story_default'))) {
                $validator->errors()->add('story_default', __('admin.story_default_enabled'));
            }
        });
    }
}
