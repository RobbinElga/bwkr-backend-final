<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\News\StoreNewsRequest;
use App\Http\Requests\News\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Services\AuditService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class NewsController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request)
    {
        $news = News::query()
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return NewsResource::collection($news);
    }

    public function store(StoreNewsRequest $request)
    {
        $data = $request->validated();

        if (! empty($data['content'])) {
            $data['content'] = Purifier::clean($data['content']);
        }
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);

        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->images->store($request->file('featured_image'), 'news');
        }

        $news = News::create($data);
        $this->audit->log('created', $news, new: $news->toArray());

        return (new NewsResource($news))->response()->setStatusCode(201);
    }

    public function show(News $news)
    {
        return new NewsResource($news);
    }

    public function update(UpdateNewsRequest $request, News $news)
    {
        $old  = $news->toArray();
        $data = $request->validated();

        if (! empty($data['content'])) {
            $data['content'] = Purifier::clean($data['content']);
        }

        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $news->id);
        }

        if (($data['status'] ?? null) === 'published' && empty($news->published_at) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $this->images->delete($news->featured_image);
            $data['featured_image'] = $this->images->store($request->file('featured_image'), 'news');
        }

        $news->update($data);
        $this->audit->log('updated', $news, $old, $news->fresh()->toArray());

        return new NewsResource($news->fresh());
    }

    public function destroy(News $news)
    {
        $news->delete();
        $this->audit->log('deleted', $news);

        return response()->json(['message' => 'Berita berhasil dihapus.']);
    }

    private function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text);
        $slug = $base;
        $i = 1;

        while (
            News::withTrashed()->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
