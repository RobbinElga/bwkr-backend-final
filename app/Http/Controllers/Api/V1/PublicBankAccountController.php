<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;

class PublicBankAccountController extends Controller
{
    public function index()
    {
        return BankAccountResource::collection(
            BankAccount::where('is_active', true)->latest()->get()
        );
    }
}
