<?php

return [

    'two_factor' => [
        // Nama yang muncul di aplikasi authenticator (Google Authenticator, dll)
        'issuer'                => env('APP_NAME', 'BWKR'),
        'backup_code_count'     => 8,
        'challenge_ttl_minutes' => 5,   // masa berlaku "tiket" verifikasi 2FA
    ],

    // Masa berlaku token login per role (dalam MENIT)
    'token_ttl' => [
        'super_admin' => 60 * 4,        // 4 jam
        'admin'       => 60 * 8,        // 8 jam
        'cs'          => 60 * 8,
        'fundraiser'  => 60 * 8,
        'donatur'     => 60 * 24 * 30,  // 30 hari
    ],

    'image' => [
        'driver'    => env('BWKR_IMAGE_DRIVER', 'gd'),
        'max_width' => 1600,
        'quality'   => 80,
    ],

    'expense' => [
        'materai_threshold'        => 5_000_000,  // > ini wajib materai
        'super_approval_threshold' => 5_000_000,  // > ini hanya SuperAdmin yang approve
    ],

];
