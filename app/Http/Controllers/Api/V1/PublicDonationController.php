<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DonationSource;
use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\StoreDonationRequest;
use App\Models\DonationInput;
use App\Services\ProofFileService;

class PublicDonationController extends Controller
{
    public function __construct(private readonly ProofFileService $proofs) {}

    /** POST /donations — submit donasi dari landing page (tamu / login). */
    public function store(StoreDonationRequest $request)
    {
        // donatur login terhubung otomatis; tamu -> null
        $donor = auth('sanctum')->user();

        $data = collect($request->validated())->except('proof')->all();
        $data['ref_no']     = DonationInput::generateRefNo();
        $data['source']     = DonationSource::Online->value;
        $data['status']     = DonationStatus::Pending->value;
        $data['user_id']    = ($donor && $donor->role === UserRole::Donatur) ? $donor->id : null;
        $data['proof_file'] = $this->proofs->store($request->file('proof'));

        $donation = DonationInput::create($data);

        return response()->json([
            'message' => 'Donasi diterima. Mohon menunggu verifikasi.',
            'ref_no'  => $donation->ref_no,
            'status'  => $donation->status->value,
        ], 201);
    }

    /** GET /donations/{ref_no}/status — cek status by nomor referensi. */
    public function status(string $refNo)
    {
        $donation = DonationInput::where('ref_no', strtoupper($refNo))->firstOrFail();

        return response()->json([
            'ref_no'     => $donation->ref_no,
            'donor_name' => $donation->donor_name,
            'amount'     => $donation->amount,
            'status'     => $donation->status->value,
            'created_at' => $donation->created_at,
        ]);
    }
}
