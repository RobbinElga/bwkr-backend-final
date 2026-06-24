<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Project;
use Illuminate\Database\Seeder;
use App\Models\Achievement;
use App\Models\ImpactVideo;
use App\Models\News;
use App\Models\Partner;
use App\Models\Testimonial;
use App\Models\Report;

class DemoPublicSeeder extends Seeder
{
    public function run(): void
    {
        $air = Program::firstOrCreate(['slug' => 'wakaf-air-bersih'], [
            'name' => 'Wakaf Air Bersih',
            'description' => 'Sumur bor & sanitasi untuk desa krisis air.',
            'status' => 'aktif',
            'order' => 1,
        ]);
        $edu = Program::firstOrCreate(['slug' => 'wakaf-pendidikan'], [
            'name' => 'Wakaf Pendidikan',
            'description' => 'Membangun fasilitas pendidikan pesantren.',
            'status' => 'aktif',
            'order' => 2,
        ]);
        Program::firstOrCreate(['slug' => 'wakaf-al-quran'], [
            'name' => "Wakaf Al-Qur'an",
            'description' => "Distribusi Al-Qur'an ke pelosok negeri.",
            'status' => 'aktif',
            'order' => 3,
        ]);

        Project::firstOrCreate(['slug' => 'sumur-bor-sumba'], [
            'program_id' => $air->id,
            'name' => 'Sumur Bor untuk Desa Tandus Sumba',
            'description' => 'Membantu 500+ KK mendapatkan akses air bersih yang layak.',
            'target_amount' => 100_000_000,
            'status' => 'berjalan',
        ]);
        Project::firstOrCreate(['slug' => 'madrasah-digital'], [
            'program_id' => $edu->id,
            'name' => 'Pembangunan Madrasah Digital',
            'description' => 'Ruang kelas modern untuk mencetak santri unggul.',
            'target_amount' => 250_000_000,
            'status' => 'berjalan',
        ]);
        Achievement::firstOrCreate(['label' => 'Adik Asuh'], ['count' => 2230, 'period' => '2025', 'order' => 1]);
        Achievement::firstOrCreate(['label' => 'Pesantren'], ['count' => 150, 'period' => '2025', 'order' => 2]);
        Achievement::firstOrCreate(['label' => 'Titik Sumur'], ['count' => 85, 'period' => '2025', 'order' => 3]);
        Achievement::firstOrCreate(['label' => 'Muzakki'], ['count' => 12000, 'period' => '2025', 'order' => 4]);

        ImpactVideo::firstOrCreate(
            ['youtube_url' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4'],
            ['caption' => 'Senyum Baru di Desa Watumbaka', 'order' => 1]
        );
        Partner::firstOrCreate(['name' => 'Baznas'], ['type' => 'Lembaga', 'is_visible' => true]);
        Partner::firstOrCreate(['name' => 'Dompet Dhuafa'], ['type' => 'Lembaga', 'is_visible' => true]);

        Testimonial::firstOrCreate(['name' => 'Bpk. Ahmad Fauzi'], [
            'title' => 'Donatur Premium',
            'content' => 'Transparansi laporan BWKR membuat saya tenang berwakaf di sini. Progress real-time sangat membantu.',
            'is_visible' => true,
            'order' => 1,
        ]);
        Testimonial::firstOrCreate(['name' => 'Ustadzah Maryam'], [
            'title' => 'Mitra Program',
            'content' => 'Dukungan BWKR sangat profesional — bukan hanya dana, tapi juga pendampingan manajemen.',
            'is_visible' => true,
            'order' => 2,
        ]);

        News::firstOrCreate(['slug' => '7-keutamaan-wakaf-jariyah'], [
            'title' => '7 Keutamaan Wakaf Jariyah bagi Masa Depan',
            'content' => '<p>Wakaf adalah salah satu investasi terbaik di dunia dan akhirat. Pahalanya terus mengalir meski kita telah tiada...</p>',
            'category' => 'Edukasi',
            'meta_desc' => 'Pelajari mengapa wakaf dianggap sebagai investasi terbaik dunia akhirat.',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $reports = [
            ['title' => 'Laporan Tahunan 2024',  'slug' => 'laporan-tahunan-2024',  'category' => 'tahunan',  'year' => 2024, 'description' => 'Tinjauan komprehensif pencapaian wakaf sepanjang 2024.'],
            ['title' => 'Laporan Tahunan 2023',  'slug' => 'laporan-tahunan-2023',  'category' => 'tahunan',  'year' => 2023, 'description' => 'Rekapitulasi dampak sosial & transparansi operasional 2023.'],
            ['title' => 'Laporan Keuangan 2024', 'slug' => 'laporan-keuangan-2024', 'category' => 'keuangan', 'year' => 2024, 'description' => 'Rincian pengelolaan dana wakaf periode 2024.'],
        ];
        foreach ($reports as $r) {
            Report::firstOrCreate(['slug' => $r['slug']], $r + ['is_published' => true]);
        }
    }
}
