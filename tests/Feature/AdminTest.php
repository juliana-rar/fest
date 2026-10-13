<?php

use App\Models\Fiesta;
use App\Models\Setting;
use App\Models\Tipo;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->municipio = municipio('Reus', 41.1561, 1.1069, 'Baix Camp', 'Tarragona');
});

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids non-admin users', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('logs in an administrator', function () {
    $this->post('/admin/login', ['email' => $this->admin->email, 'password' => 'password'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($this->admin);
});

it('shows the dashboard', function () {
    Fiesta::factory()->for($this->municipio)->create(['visitas' => 99]);

    $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('99');
});

it('creates a festival with its programme and sanitises the description', function () {
    $this->actingAs($this->admin)->post('/admin/fiestas', [
        'nombre' => 'Festa Major de Sant Pere',
        'municipio_id' => $this->municipio->id,
        'fecha_inicio' => '2027-06-24',
        'fecha_fin' => '2027-06-29',
        'descripcion' => '<p onclick="x()">Hola <script>alert(1)</script><b>Reus</b></p>',
        'publicada' => '1',
        'actos' => [
            ['dia' => '2027-06-24', 'hora' => '20:00', 'titulo' => 'Pregó', 'lugar' => 'Plaça del Mercadal'],
            ['dia' => '2027-06-29', 'hora' => '', 'titulo' => '', 'lugar' => ''], // empty rows are ignored
        ],
    ])->assertRedirect();

    $fiesta = Fiesta::query()->firstWhere('slug', 'festa-major-de-sant-pere-2027');

    expect($fiesta)->not->toBeNull()
        ->and($fiesta->descripcion)->toBe('<p>Hola <strong>Reus</strong></p>')
        ->and($fiesta->actos)->toHaveCount(1);
});

it('validates festival dates and programme days', function () {
    $this->actingAs($this->admin)->post('/admin/fiestas', [
        'nombre' => 'X',
        'municipio_id' => $this->municipio->id,
        'fecha_inicio' => '2027-06-24',
        'fecha_fin' => '2027-06-20',
        'actos' => [['dia' => '2027-07-30', 'titulo' => 'Fora de dates']],
    ])->assertSessionHasErrors(['fecha_fin', 'actos.0.dia']);
});

it('duplicates a festival to next year', function () {
    $fiesta = Fiesta::factory()->for($this->municipio)->create(['nombre' => 'Sant Pere', 'fecha_inicio' => '2026-06-24', 'fecha_fin' => '2026-06-29']);

    $this->actingAs($this->admin)->post("/admin/fiestas/{$fiesta->slug}/duplicate")->assertRedirect();

    expect(Fiesta::query()->where('slug', 'sant-pere-2027')->exists())->toBeTrue();
});

it('deletes a festival', function () {
    $fiesta = Fiesta::factory()->for($this->municipio)->create();

    $this->actingAs($this->admin)->delete("/admin/fiestas/{$fiesta->slug}")->assertRedirect('/admin/fiestas');

    expect(Fiesta::query()->find($fiesta->id))->toBeNull();
});

it('refuses to delete a municipality that has festivals', function () {
    Fiesta::factory()->for($this->municipio)->create();

    $this->actingAs($this->admin)->delete("/admin/municipios/{$this->municipio->slug}")->assertSessionHas('error');

    expect($this->municipio->fresh())->not->toBeNull();
});

it('exports and imports festivals as CSV', function () {
    $fiesta = Fiesta::factory()->for($this->municipio)->create(['nombre' => 'Original']);

    $csv = $this->actingAs($this->admin)->get('/admin/fiestas/export')->assertOk()->streamedContent();
    expect($csv)->toContain('Original');

    $file = tmpfile();
    fwrite($file, "nombre;slug;municipio;fecha_inicio;fecha_fin;descripcion;destacada;publicada\n");
    fwrite($file, "Canviada;{$fiesta->slug};reus;2027-01-01;2027-01-02;;1;1\n");
    fwrite($file, "Nova;;reus;2027-02-01;2027-02-03;<p>Hola</p>;0;1\n");
    fwrite($file, "Dolenta;;poble-inexistent;2027-02-01;2027-02-03;;0;1\n");
    $path = stream_get_meta_data($file)['uri'];

    $this->actingAs($this->admin)
        ->post('/admin/fiestas/import', ['csv' => new \Illuminate\Http\UploadedFile($path, 'festes.csv', 'text/csv', null, true)])
        ->assertRedirect('/admin/fiestas')
        ->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1);

    expect($fiesta->fresh()->nombre)->toBe('Canviada')
        ->and(Fiesta::query()->where('nombre', 'Nova')->exists())->toBeTrue();
});

it('updates the site settings', function () {
    $templates = collect(Setting::allStoryTemplates())->map(fn ($t) => ['name' => $t['name'], 'accent' => $t['accent'], 'enabled' => '1'])->all();

    $this->actingAs($this->admin)->put('/admin/settings', [
        'hero_title_ca' => 'Visca la festa',
        'hero_title_es' => 'Viva la fiesta',
        'color_primary' => '#AA0000',
        'color_secondary' => '#FFCC00',
        'color_background' => '#101010',
        'seo_title' => 'SEO',
        'seo_description' => 'Descripció',
        'short_url' => 'festes.test',
        'story_default' => 'minimal',
        'story_templates' => $templates,
        'social' => ['instagram' => 'https://instagram.com/festes'],
    ])->assertRedirect('/admin/settings');

    expect(Setting::get('hero_title_ca'))->toBe('Visca la festa')
        ->and(Setting::defaultStoryTemplate())->toBe('minimal');

    $this->get('/')->assertSee('Visca la festa')->assertSee('--brand-red:#AA0000', false);
});

it('assigns festival types from the form', function () {
    $fm = Tipo::factory()->create();
    $barraques = Tipo::factory()->create();

    $this->actingAs($this->admin)->post('/admin/fiestas', [
        'nombre' => 'Festa Major de Reus',
        'municipio_id' => $this->municipio->id,
        'fecha_inicio' => '2027-06-24',
        'fecha_fin' => '2027-06-29',
        'tipos' => [$fm->id, $barraques->id],
    ])->assertRedirect();

    $fiesta = Fiesta::query()->firstWhere('nombre', 'Festa Major de Reus');
    expect($fiesta->tipos->pluck('id')->sort()->values()->all())->toBe(collect([$fm->id, $barraques->id])->sort()->values()->all());

    // Unchecking everything detaches all types.
    $this->actingAs($this->admin)->put("/admin/fiestas/{$fiesta->slug}", [
        'nombre' => $fiesta->nombre,
        'slug' => $fiesta->slug,
        'municipio_id' => $this->municipio->id,
        'fecha_inicio' => '2027-06-24',
        'fecha_fin' => '2027-06-29',
    ])->assertRedirect();

    expect($fiesta->fresh()->tipos)->toHaveCount(0);
});

it('manages festival types', function () {
    $this->actingAs($this->admin)->post('/admin/tipos', ['nombre' => 'Barraques', 'nombre_es' => 'Barraques', 'color' => '#A78BFA'])
        ->assertRedirect('/admin/tipos');

    $tipo = Tipo::query()->firstWhere('slug', 'barraques');
    expect($tipo)->not->toBeNull()->and($tipo->orden)->toBe(0);

    $fiesta = Fiesta::factory()->for($this->municipio)->create();
    $fiesta->tipos()->attach($tipo);

    $this->actingAs($this->admin)->get('/admin/tipos')->assertOk()->assertSee('Barraques');
    $this->actingAs($this->admin)->delete('/admin/tipos/barraques')->assertRedirect('/admin/tipos');

    expect(Tipo::query()->count())->toBe(0)
        ->and($fiesta->fresh())->not->toBeNull()
        ->and($fiesta->fresh()->tipos)->toHaveCount(0);
});

it('imports festival types from the CSV tipos column', function () {
    Tipo::factory()->create(['slug' => 'festa-major']);
    Tipo::factory()->create(['slug' => 'barraques']);

    $file = tmpfile();
    fwrite($file, "nombre;municipio;fecha_inicio;fecha_fin;tipos\n");
    fwrite($file, "Amb tipus;reus;2027-03-01;2027-03-02;festa-major|barraques|inexistent\n");
    $path = stream_get_meta_data($file)['uri'];

    $this->actingAs($this->admin)
        ->post('/admin/fiestas/import', ['csv' => new \Illuminate\Http\UploadedFile($path, 'festes.csv', 'text/csv', null, true)])
        ->assertRedirect('/admin/fiestas');

    expect(Fiesta::query()->firstWhere('nombre', 'Amb tipus')->tipos->pluck('slug')->sort()->values()->all())
        ->toBe(['barraques', 'festa-major']);
});
