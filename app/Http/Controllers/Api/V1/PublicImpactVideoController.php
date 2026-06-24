<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImpactVideoResource;
use App\Models\ImpactVideo;

class PublicImpactVideoController extends Controller
{
    public function index()
    {
        return ImpactVideoResource::collection(
            ImpactVideo::orderBy('order')->latest()->get()
        );
    }
}
