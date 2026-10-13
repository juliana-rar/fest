<?php

use App\Models\Fiesta;

beforeEach(function () {
    $this->fiesta = Fiesta::factory()->for(municipio('Berga', 42.1036, 1.8456, 'Berguedà'))->create([
        'nombre' => 'La Patum de Berga',
        'descripcion' => '<p>Foc, salts i tabals.</p>',
        'fecha_inicio' => today()->addDays(20),
        'fecha_fin' => today()->addDays(24),
    ]);
});

it('renders the home page', function () {
    $this->get('/')->assertOk()->assertSee('La Patum de Berga')->assertSee('id="calendari"', false);
});

it('renders a festival with Open Graph and Event JSON-LD', function () {
    $this->get("/festa/{$this->fiesta->slug}")
        ->assertOk()
        ->assertSee('<meta property="og:title"', false)
        ->assertSee('"@type":"Event"', false)
        ->assertSee('Foc, salts i tabals.');

    expect($this->fiesta->fresh()->visitas)->toBe(1);
});

it('hides drafts from the public', function () {
    $this->fiesta->update(['publicada' => false]);

    $this->get("/festa/{$this->fiesta->slug}")->assertNotFound();
});

it('exports an iCalendar file', function () {
    $this->get("/festa/{$this->fiesta->slug}/calendari.ics")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VEVENT', false)
        ->assertSee('DTSTART;VALUE=DATE:'.$this->fiesta->fecha_inicio->format('Ymd'), false)
        ->assertSee('DTEND;VALUE=DATE:'.$this->fiesta->fecha_fin->copy()->addDay()->format('Ymd'), false);
});

it('serves a 1080x1920 story image', function () {
    $response = $this->get("/story/{$this->fiesta->slug}?t=senyera")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    [$width, $height] = getimagesize($response->baseResponse->getFile()->getPathname());
    expect([$width, $height])->toBe([1080, 1920]);
});

it('publishes a sitemap', function () {
    $this->get('/sitemap.xml')->assertOk()->assertSee("/festa/{$this->fiesta->slug}", false);
});

it('switches the language', function () {
    $this->from('/')->get('/idioma/es')->assertRedirect('/');
    $this->get('/')->assertSee('Próximas fiestas');
});
