<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache validation rules
    |--------------------------------------------------------------------------
    |
    | If this is set to true then the AutoValidation trait will Cache the
    | validation rules forever. (At least till the cache isn't cleared.)
    |
    */
    'cache' => env('VALIDATION_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Dump
    |--------------------------------------------------------------------------
    |
    | If this is set to true then the validation will dump useful values.
    | This is for debugging purposes only.
    |
    */
    'dump' => env('VALIDATION_DUMP', false),
];