<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | This key is used to sign and verify JWT tokens. It should be a long,
    | random string. You can generate one using:
    | php artisan sjwt:generate-secret
    |
    | For security, never commit this key to version control. Use environment
    | variables instead.
    |
    */

    'secret' => env('SECRET_JWT', env('JWT_SECRET')),

    /*
    |--------------------------------------------------------------------------
    | Default Token Expiration
    |--------------------------------------------------------------------------
    |
    | Default expiration time in minutes for JWT tokens.
    |
    */

    'default_expiration' => env('JWT_EXPIRATION', 60),

    /*
    |--------------------------------------------------------------------------
    | Authorization Header Name
    |--------------------------------------------------------------------------
    |
    | The name of the HTTP header that contains the JWT token.
    |
    */

    'header_name' => env('JWT_HEADER_NAME', 'Authorization'),

    /*
    |--------------------------------------------------------------------------
    | Token Index in Header
    |--------------------------------------------------------------------------
    |
    | When the header value is space-separated (e.g., "Bearer {token}"),
    | this specifies which part contains the actual token.
    | 0 = first part, 1 = second part, etc.
    |
    */

    'token_index' => env('JWT_TOKEN_INDEX', 1),
];

