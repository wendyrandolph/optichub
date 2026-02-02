<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->route('tenant');
        $workspaceType = $tenant?->workspace_type ?? 'creative';
        $search = trim((string) $request->query('q', ''));

        $articles = KbArticle::query()
            ->where('is_published', true)
            ->whereIn('audience', ['all', $workspaceType])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->get();

        $categories = KbCategory::query()
            ->with(['articles' => function ($query) use ($workspaceType) {
                $query->where('is_published', true)
                    ->whereIn('audience', ['all', $workspaceType])
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('tenant.support-kb-index', [
            'tenant' => $tenant,
            'categories' => $categories,
            'articles' => $articles,
            'search' => $search,
        ]);
    }

    public function show(Request $request, KbArticle $article)
    {
        $tenant = $request->route('tenant');
        $workspaceType = $tenant?->workspace_type ?? 'creative';

        abort_unless($article->is_published, 404);
        abort_unless(in_array($article->audience, ['all', $workspaceType], true), 404);

        return view('tenant.support-kb-show', [
            'tenant' => $tenant,
            'article' => $article,
        ]);
    }
}
