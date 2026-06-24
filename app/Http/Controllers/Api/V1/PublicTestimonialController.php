<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;

class PublicTestimonialController extends Controller
{
    public function index()
    {
        return TestimonialResource::collection(
            Testimonial::where('is_visible', true)->orderBy('order')->latest()->get()
        );
    }
}
