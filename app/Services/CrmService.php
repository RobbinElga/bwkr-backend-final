<?php

namespace App\Services;

use App\Enums\DonorTier;
use App\Models\DonationInput;
use App\Models\DonorProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CrmService
{
    /** Hash nomor donatur yang ber-tier premium. */
    public function premiumHashes(): array
    {
        return DonorProfile::where('tier', DonorTier::Premium->value)
            ->pluck('donor_phone_hash')->all();
    }

    /**
     * Resolusi penerima broadcast -> koleksi siap-placeholder.
     * @return Collection<int, array{hash:string,name:string,phone:?string,total:int,project:string}>
     */
    public function resolveRecipients(?array $phoneHashes, ?string $tier): Collection
    {
        $premium = $this->premiumHashes();

        $rows = DonationInput::query()
            ->selectRaw("donor_phone_hash, SUM(CASE WHEN status != 'rejected' THEN amount ELSE 0 END) as total_donated, MAX(id) as latest_id")
            ->whereNotNull('donor_phone_hash')
            ->when($phoneHashes, fn($q) => $q->whereIn('donor_phone_hash', $phoneHashes))
            ->when($tier === 'premium', fn($q) => $q->whereIn('donor_phone_hash', $premium ?: ['__none__']))
            ->when($tier === 'reguler', fn($q) => $q->whereNotIn('donor_phone_hash', $premium))
            ->groupBy('donor_phone_hash')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $latest   = DonationInput::whereIn('id', $rows->pluck('latest_id'))->get()->keyBy('id');
        $projects = $this->lastProjects($rows->pluck('donor_phone_hash')->all());

        return $rows->map(function ($row) use ($latest, $projects) {
            $rep = $latest->get($row->latest_id);

            return [
                'hash'    => $row->donor_phone_hash,
                'name'    => $rep?->donor_name ?? 'Donatur',
                'phone'   => $rep?->donor_phone,
                'total'   => (int) $row->total_donated,
                'project' => $projects[$row->donor_phone_hash] ?? '-',
                'salutation' => $rep?->salutation ?? '',
            ];
        })->filter(fn($r) => ! empty($r['phone']))->values();
    }

    /** Nama proyek dari klaim approved TERAKHIR per donatur (untuk placeholder [Project]). */
    private function lastProjects(array $hashes): array
    {
        return DB::table('donations_claim as dc')
            ->join('donations_input as di', 'di.id', '=', 'dc.donation_input_id')
            ->join('projects as p', 'p.id', '=', 'dc.project_id')
            ->where('dc.status', 'approved')
            ->whereIn('di.donor_phone_hash', $hashes)
            ->orderByDesc('dc.id')
            ->select('di.donor_phone_hash as h', 'p.name as name')
            ->get()
            ->unique('h')   // ambil yang pertama (terbaru) per donatur
            ->mapWithKeys(fn($r) => [$r->h => $r->name])
            ->all();
    }

    public function fillPlaceholders(string $template, array $r): string
    {
        return strtr($template, [
            '[Nama]'    => $r['name'],
            '[Nominal]' => 'Rp' . number_format($r['total'], 0, ',', '.'),
            '[Project]' => $r['project'],
            '[Sapaan]' => $r['salutation'] ?? '',
        ]);
    }
}
