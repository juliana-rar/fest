<?php

use App\Services\HtmlSanitizer;

it('keeps whitelisted formatting', function () {
    expect((new HtmlSanitizer)->clean('<p>Hola <b>món</b> i <i>festa</i></p><ul><li>Un</li></ul>'))
        ->toBe('<p>Hola <strong>món</strong> i <em>festa</em></p><ul><li>Un</li></ul>');
});

it('removes scripts, event handlers and unsafe links', function () {
    $html = (new HtmlSanitizer)->clean('<p style="x" onclick="a()">A<script>alert(1)</script></p><a href="javascript:alert(1)">x</a><a href="https://example.org">ok</a>');

    expect($html)->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->toContain('<a href="https://example.org" rel="noopener nofollow">ok</a>');
});

it('unwraps unknown tags but keeps their text', function () {
    expect((new HtmlSanitizer)->clean('<div><span>Text</span></div>'))->toBe('Text');
});
