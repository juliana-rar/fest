<?php

/*
 | Spanish messages for the validation rules used by this application.
 | Any rule not listed here falls back to lang/en/validation.php.
 */

return [
    'accepted' => 'Debes aceptar el campo :attribute.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'array' => 'El campo :attribute debe ser una lista.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha igual o anterior a :date.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_format' => 'El campo :attribute no tiene el formato :format.',
    'dimensions' => 'La imagen :attribute no tiene las dimensiones mínimas.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'exists' => 'El valor de :attribute no es válido.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor de :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El archivo :attribute no puede superar los :max kB.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
        'string' => 'El campo :attribute debe tener como mínimo :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',
    'unique' => 'Este :attribute ya está en uso.',
    'url' => 'El campo :attribute debe ser una URL válida (https://…).',

    'attributes' => [
        'nombre' => 'nombre',
        'slug' => 'slug',
        'municipio_id' => 'municipio',
        'comarca_id' => 'comarca',
        'fecha_inicio' => 'fecha de inicio',
        'fecha_fin' => 'fecha de fin',
        'descripcion' => 'descripción',
        'imagen' => 'imagen',
        'provincia' => 'provincia',
        'lat' => 'latitud',
        'lng' => 'longitud',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'csv' => 'archivo CSV',
        'logo' => 'logotipo',
        'mes' => 'mes',
        'radio' => 'radio',
        'q' => 'búsqueda',
    ],
];
