<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicPartnerResource;
use App\Models\Partner;

class PublicPartnerController extends Controller
{
    public function index()
    {
        return PublicPartnerResource::collection(
            Partner::where('is_visible', true)->latest()->get()
        );
    }
}
