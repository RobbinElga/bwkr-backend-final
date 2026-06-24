<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccount\StoreBankAccountRequest;
use App\Http\Requests\BankAccount\UpdateBankAccountRequest;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Services\AuditService;
use App\Services\ImageService;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index()
    {
        return BankAccountResource::collection(BankAccount::latest()->get());
    }

    public function store(StoreBankAccountRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->images->store($request->file('logo'), 'bank-logos');
        }

        if ($request->hasFile('qris_image')) {
            $data['qris_image'] = $this->images->store($request->file('qris_image'), 'qris');
        }

        $account = BankAccount::create($data);
        $this->audit->log('created', $account, new: $this->auditable($account));

        return (new BankAccountResource($account))->response()->setStatusCode(201);
    }

    public function show(BankAccount $bankAccount)
    {
        return new BankAccountResource($bankAccount);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount)
    {
        $old  = $this->auditable($bankAccount);
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->images->delete($bankAccount->logo);
            $data['logo'] = $this->images->store($request->file('logo'), 'bank-logos');
        }

        if ($request->hasFile('qris_image')) {
            $data['qris_image'] = $this->images->store($request->file('qris_image'), 'qris');
        }

        $bankAccount->update($data);
        $this->audit->log('updated', $bankAccount, $old, $this->auditable($bankAccount->fresh()));

        return new BankAccountResource($bankAccount->fresh());
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        $this->audit->log('deleted', $bankAccount);

        return response()->json(['message' => 'Rekening berhasil dihapus.']);
    }

    /** Data untuk audit — sembunyikan nomor rekening dari log. */
    private function auditable(BankAccount $account): array
    {
        return collect($account->toArray())->except('account_number')->all();
    }
}
