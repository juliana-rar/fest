<?php

namespace App\Http\Controllers;

use App\Models\Fiesta;
use App\Models\Setting;
use App\Services\StoryImage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StoryController extends Controller
{
    /**
     * GET /story/{slug}?t=nit|senyera|minimal[&download=1]
     * Returns the 1080×1920 JPEG, rendered once per day/template and cached on disk.
     */
    public function __invoke(Request $request, Fiesta $fiesta, StoryImage $story): BinaryFileResponse
    {
        abort_unless($fiesta->publicada || $request->user()?->isAdmin(), 404);

        $templates = Setting::storyTemplates();
        $template = $request->query('t');
        if (! is_string($template) || ! isset($templates[$template])) {
            $template = Setting::defaultStoryTemplate();
        }

        $response = response()->file($story->path($fiesta, $template), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);

        if ($request->boolean('download')) {
            $response->setContentDisposition('attachment', "story-{$fiesta->slug}-{$template}.jpg");
        }

        return $response;
    }
}
