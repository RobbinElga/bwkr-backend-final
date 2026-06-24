<?php

namespace App\Http\Controllers\Api\V1\Donatur;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DonaturDonationResource;
use App\Models\DonationInput;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DonationHistoryController extends Controller
{
    /** GET /donatur/donations */
    public function index(Request $request)
    {
        $donations = $this->ownedQuery($request->user())
            ->with(['bankAccount', 'project', 'program'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return DonaturDonationResource::collection($donations);
    }

    /** GET /donatur/donations/{ref_no} */
    public function show(Request $request, string $refNo)
    {
        $donation = $this->ownedQuery($request->user())
            ->with(['bankAccount', 'project', 'program'])
            ->where('ref_no', strtoupper($refNo))
            ->firstOrFail();

        return new DonaturDonationResource($donation);
    }

    /** GET /donatur/summary — ringkasan agregat untuk dashboard donatur. */
    public function summary(Request $request)
    {
        $base     = $this->ownedQuery($request->user());
        $verified = (clone $base)->where('status', DonationStatus::Claimed); // hanya yg terverifikasi

        return response()->json([
            'total_verified'     => (int) (clone $verified)->sum('amount'),
            'count_verified'     => (clone $verified)->count(),
            'count_all'          => (clone $base)->count(),
            'projects_supported' => (clone $verified)->whereNotNull('project_id')->distinct()->count('project_id'),
        ]);
    }

    /** Query donasi milik user: cocok by user_id ATAU by hash nomor HP-nya. */
    private function ownedQuery(User $user): Builder
    {
        $hash = $user->phone ? User::hashPhone($user->phone) : null;

        return DonationInput::query()->where(function (Builder $q) use ($user, $hash) {
            $q->where('user_id', $user->id);
            if ($hash) {
                $q->orWhere('donor_phone_hash', $hash);
            }
        });
    }
}
