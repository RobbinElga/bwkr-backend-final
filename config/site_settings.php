<?php

return [
    'groups' => [
        'hero' => [
            'label'  => 'Hero (Beranda)',
            'fields' => [
                ['key' => 'hero_badge',    'label' => 'Badge Kecil',  'type' => 'text'],
                ['key' => 'hero_title',    'label' => 'Judul',        'type' => 'text'],
                ['key' => 'hero_subtitle', 'label' => 'Subjudul',     'type' => 'textarea'],
                ['key' => 'hero_cta_text', 'label' => 'Teks Tombol',  'type' => 'text'],
                ['key' => 'hero_cta_link', 'label' => 'Link Tombol',  'type' => 'text'],
                ['key' => 'hero_image',    'label' => 'Gambar Latar', 'type' => 'image'],
            ],
        ],
        'identitas' => [
            'label'  => 'Identitas Situs',
            'fields' => [
                ['key' => 'site_name',      'label' => 'Nama Brand',     'type' => 'text'],
                ['key' => 'site_logo',      'label' => 'Logo',           'type' => 'image'],
                ['key' => 'footer_tagline', 'label' => 'Tagline Footer', 'type' => 'textarea'],
            ],
        ],
        'kontak' => [
            'label'  => 'Kontak & Sosial',
            'fields' => [
                ['key' => 'contact_email',    'label' => 'Email',         'type' => 'text'],
                ['key' => 'contact_phone',    'label' => 'No HP / WA',    'type' => 'text'],
                ['key' => 'contact_address',  'label' => 'Alamat',        'type' => 'textarea'],
                ['key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'text'],
                ['key' => 'social_facebook',  'label' => 'Facebook URL',  'type' => 'text'],
                ['key' => 'social_youtube',   'label' => 'YouTube URL',   'type' => 'text'],
            ],
        ],
        'tentang' => [
            'label'  => 'Halaman Tentang',
            'fields' => [
                ['key' => 'about_intro',   'label' => 'Intro',  'type' => 'textarea'],
                ['key' => 'about_vision',  'label' => 'Visi',   'type' => 'textarea'],
                ['key' => 'about_mission', 'label' => 'Misi',   'type' => 'textarea'],
                ['key' => 'about_image',   'label' => 'Gambar', 'type' => 'image'],
            ],
        ],
        'wa' => [
            'label'  => 'Pesan WhatsApp (bisa pakai [Sapaan] [Nama] [Nominal] [Ref] [Project])',
            'fields' => [
                ['key' => 'wa_pending_message',  'label' => 'Pesan saat donasi masuk (pending)', 'type' => 'textarea'],
                ['key' => 'wa_approved_message', 'label' => 'Pesan saat donasi disetujui',       'type' => 'textarea'],
            ],
        ],
    ],

    'defaults' => [
        'hero_badge'    => 'Dampak Nyata',
        'hero_title'    => 'Membangun Masa Depan Umat Melalui Wakaf Pesantren',
        'hero_subtitle' => 'Membantu ribuan santri mendapatkan fasilitas pendidikan yang layak demi melahirkan generasi rabbani yang berdaya.',
        'hero_cta_text' => 'Wakaf Sekarang',
        'hero_cta_link' => '/donasi',
        'hero_image'    => null,

        'site_name'      => 'BWKR',
        'site_logo'      => null,
        'footer_tagline' => 'Platform wakaf digital modern yang menghubungkan kebaikan dengan kebutuhan umat secara transparan dan amanah.',

        'contact_email'    => 'info@bwkr.id',
        'contact_phone'    => '',
        'contact_address'  => 'Pondok Pesantren Khulafaur Rasyidin',
        'social_instagram' => '',
        'social_facebook'  => '',
        'social_youtube'   => '',

        'about_intro'   => 'Pondok Pesantren Khulafaur Rasyidin hadir untuk menghubungkan kebaikan para wakif dengan kebutuhan nyata umat.',
        'about_vision'  => 'Mewujudkan kemandirian pesantren dan kesejahteraan umat melalui pengelolaan wakaf produktif.',
        'about_mission' => 'Mengelola wakaf secara amanah, transparan, dan berdampak.',
        'about_image'   => null,

        'wa_pending_message' => "Assalamualaikum [Sapaan] [Nama],\n\nTerima kasih. Donasi Anda sebesar [Nominal] (Ref: *[Ref]*) telah kami terima dan sedang diverifikasi.\n\nJazakumullah khairan.\n- BWKR",
        'wa_approved_message' => "Assalamualaikum [Sapaan] [Nama],\n\nAlhamdulillah, donasi Anda sebesar [Nominal] untuk *[Project]* telah kami verifikasi dan tersalurkan.\n\nSemoga menjadi amal jariyah. Jazakumullah khairan.\n- BWKR",
    ],
];
