<?php

/*
 | Catalan messages for the validation rules used by this application.
 | Any rule not listed here falls back to lang/en/validation.php.
 */

return [
    'accepted' => 'Cal acceptar el camp :attribute.',
    'after_or_equal' => 'El camp :attribute ha de ser una data igual o posterior a :date.',
    'alpha_dash' => 'El camp :attribute només pot contenir lletres, números, guions i guions baixos.',
    'array' => 'El camp :attribute ha de ser una llista.',
    'before_or_equal' => 'El camp :attribute ha de ser una data igual o anterior a :date.',
    'between' => [
        'numeric' => 'El camp :attribute ha d’estar entre :min i :max.',
        'string' => 'El camp :attribute ha de tenir entre :min i :max caràcters.',
    ],
    'boolean' => 'El camp :attribute ha de ser cert o fals.',
    'date' => 'El camp :attribute no és una data vàlida.',
    'date_format' => 'El camp :attribute no té el format :format.',
    'dimensions' => 'La imatge :attribute no té les dimensions mínimes.',
    'email' => 'El camp :attribute ha de ser una adreça electrònica vàlida.',
    'exists' => 'El valor de :attribute no és vàlid.',
    'file' => 'El camp :attribute ha de ser un fitxer.',
    'image' => 'El camp :attribute ha de ser una imatge.',
    'in' => 'El valor de :attribute no és vàlid.',
    'integer' => 'El camp :attribute ha de ser un nombre enter.',
    'max' => [
        'array' => 'El camp :attribute no pot tenir més de :max elements.',
        'file' => 'El fitxer :attribute no pot superar els :max kB.',
        'numeric' => 'El camp :attribute no pot ser més gran que :max.',
        'string' => 'El camp :attribute no pot superar els :max caràcters.',
    ],
    'mimes' => 'El camp :attribute ha de ser un fitxer de tipus: :values.',
    'min' => [
        'numeric' => 'El camp :attribute ha de ser com a mínim :min.',
        'string' => 'El camp :attribute ha de tenir com a mínim :min caràcters.',
    ],
    'numeric' => 'El camp :attribute ha de ser un número.',
    'regex' => 'El format de :attribute no és vàlid.',
    'required' => 'El camp :attribute és obligatori.',
    'string' => 'El camp :attribute ha de ser un text.',
    'unique' => 'Aquest :attribute ja està en ús.',
    'url' => 'El camp :attribute ha de ser una URL vàlida (https://…).',

    'attributes' => [
        'nombre' => 'nom',
        'slug' => 'slug',
        'municipio_id' => 'municipi',
        'comarca_id' => 'comarca',
        'fecha_inicio' => 'data d’inici',
        'fecha_fin' => 'data de fi',
        'descripcion' => 'descripció',
        'imagen' => 'imatge',
        'provincia' => 'província',
        'lat' => 'latitud',
        'lng' => 'longitud',
        'email' => 'correu electrònic',
        'password' => 'contrasenya',
        'csv' => 'fitxer CSV',
        'logo' => 'logotip',
        'mes' => 'mes',
        'radio' => 'radi',
        'q' => 'cerca',
    ],
];
