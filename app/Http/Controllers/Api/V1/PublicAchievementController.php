<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;

class PublicAchievementController extends Controller
{
    public function index()
    {
        return AchievementResource::collection(
            Achievement::orderBy('order')->latest()->get()
        );
    }
}
