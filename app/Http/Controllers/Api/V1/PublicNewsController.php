<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\NewsStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;

class PublicNewsController extends Controller
{
    public function index(Request $request)
    {
        $news = News::where('status', NewsStatus::Published)
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->latest('published_at')
            ->paginate($request->integer('per_page', 10));

        return NewsResource::collection($news);
    }

    public function show(News $news)
    {
        abort_if($news->status !== NewsStatus::Published, 404);

        return new NewsResource($news);
    }

    public function like(News $news)
    {
        abort_if($news->status !== NewsStatus::Published, 404);
        $news->increment('likes_count');

        return response()->json(['likes_count' => (int) $news->likes_count]);
    }

    public function unlike(News $news)
    {
        abort_if($news->status !== NewsStatus::Published, 404);
        if ($news->likes_count > 0) {
            $news->decrement('likes_count');
        }

        return response()->json(['likes_count' => (int) $news->likes_count]);
    }
}
