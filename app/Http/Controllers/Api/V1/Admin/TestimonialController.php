<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Testimonial\StoreTestimonialRequest;
use App\Http\Requests\Testimonial\UpdateTestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Services\AuditService;
use App\Services\ImageService;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index()
    {
        return TestimonialResource::collection(
            Testimonial::orderBy('order')->latest()->paginate(15)
        );
    }

    public function store(StoreTestimonialRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->images->store($request->file('photo'), 'testimonials');
        }

        $testimonial = Testimonial::create($data);
        $this->audit->log('created', $testimonial, new: $testimonial->toArray());

        return (new TestimonialResource($testimonial))->response()->setStatusCode(201);
    }

    public function show(Testimonial $testimonial)
    {
        return new TestimonialResource($testimonial);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial)
    {
        $old  = $testimonial->toArray();
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $this->images->delete($testimonial->photo);
            $data['photo'] = $this->images->store($request->file('photo'), 'testimonials');
        }

        $testimonial->update($data);
        $this->audit->log('updated', $testimonial, $old, $testimonial->fresh()->toArray());

        return new TestimonialResource($testimonial->fresh());
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();
        $this->audit->log('deleted', $testimonial);

        return response()->json(['message' => 'Testimoni berhasil dihapus.']);
    }
}
