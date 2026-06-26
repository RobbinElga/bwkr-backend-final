<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\StoreProgramRequest;
use App\Http\Requests\Program\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use App\Services\AuditService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request)
    {
        $programs = Program::query()
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderBy('order')->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return ProgramResource::collection($programs);
    }

    public function store(StoreProgramRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), 'programs');
        }

        $program = Program::create($data);
        $this->audit->log('created', $program, new: $program->toArray());

        return (new ProgramResource($program))->response()->setStatusCode(201);
    }

    public function show(Program $program)
    {
        return new ProgramResource($program);
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $old  = $program->toArray();
        $data = $request->validated();

        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $program->id);
        }

        if ($request->boolean('remove_image')) {
            $this->images->delete($program->image);
            $data['image'] = null;
        } elseif ($request->hasFile('image')) {
            $this->images->delete($program->image);
            $data['image'] = $this->images->store($request->file('image'), 'programs');
        }

        $program->update($data);
        $this->audit->log('updated', $program, $old, $program->fresh()->toArray());

        return new ProgramResource($program->fresh());
    }

    public function destroy(Program $program)
    {
        $program->delete();   // soft delete
        $this->audit->log('deleted', $program);

        return response()->json(['message' => 'Program berhasil dihapus.']);
    }

    public function trashed()
    {
        return ProgramResource::collection(
            Program::onlyTrashed()->orderByDesc('deleted_at')->get()
        );
    }

    public function restore(int $id)
    {
        $program = Program::onlyTrashed()->findOrFail($id);
        $program->restore();
        $this->audit->log('restored', $program);

        return response()->json(['message' => 'Program berhasil dipulihkan.']);
    }

    public function forceDelete(int $id)
    {
        $program = Program::onlyTrashed()->findOrFail($id);
        $this->images->delete($program->image);   // hapus gambar permanen
        $program->forceDelete();
        $this->audit->log('force_deleted', $program);

        return response()->json(['message' => 'Program dihapus permanen.']);
    }

    /** Slug unik dari teks; tambah sufiks angka bila bentrok (cek termasuk yang soft-deleted). */
    private function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text);
        $slug = $base;
        $i = 1;

        while (
            Program::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
