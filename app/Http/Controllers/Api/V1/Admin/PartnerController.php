<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\StorePartnerRequest;
use App\Http\Requests\Partner\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use App\Services\AuditService;
use App\Services\ImageService;

class PartnerController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index()
    {
        return PartnerResource::collection(Partner::latest()->paginate(15));
    }

    public function store(StorePartnerRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->images->storeImageOrSvg($request->file('logo'), 'partners');
        }

        $partner = Partner::create($data);
        $this->audit->log('created', $partner, new: $partner->toArray());

        return (new PartnerResource($partner))->response()->setStatusCode(201);
    }

    public function show(Partner $partner)
    {
        return new PartnerResource($partner);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        $old  = $partner->toArray();
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->images->delete($partner->logo);
            $data['logo'] = $this->images->storeImageOrSvg($request->file('logo'), 'partners');
        }

        $partner->update($data);
        $this->audit->log('updated', $partner, $old, $partner->fresh()->toArray());

        return new PartnerResource($partner->fresh());
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        $this->audit->log('deleted', $partner);

        return response()->json(['message' => 'Mitra berhasil dihapus.']);
    }
}
