<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportKnowledgeBaseController extends Controller
{
    public function index()
    {
        $categories = KbCategory::query()
            ->with(['articles' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('admin.support.kb-index', [
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $slug = Str::slug($data['name']);
        if (KbCategory::where('slug', $slug)->exists()) {
            $slug .= '-' . now()->format('His');
        }

        KbCategory::create([
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => 0,
        ]);

        return redirect()->route('admin.support.kb.index')->with('success', 'Category created.');
    }

    public function destroyCategory(KbCategory $category)
    {
        $category->delete();
        return redirect()->route('admin.support.kb.index')->with('success', 'Category deleted.');
    }

    public function create()
    {
        $categories = KbCategory::query()->orderBy('sort_order')->get();

        return view('admin.support.kb-create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:kb_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'audience' => ['required', 'in:all,creative,trades'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($data['title']);
        if (KbArticle::where('slug', $slug)->exists()) {
            $slug .= '-' . now()->format('His');
        }

        KbArticle::create([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'audience' => $data['audience'],
            'is_published' => (bool) ($data['is_published'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.support.kb.index')->with('success', 'Article created.');
    }

    public function edit(KbArticle $article)
    {
        $categories = KbCategory::query()->orderBy('sort_order')->get();

        return view('admin.support.kb-edit', [
            'article' => $article,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, KbArticle $article)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:kb_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'audience' => ['required', 'in:all,creative,trades'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $article->update([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'audience' => $data['audience'],
            'is_published' => (bool) ($data['is_published'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.support.kb.index')->with('success', 'Article updated.');
    }

    public function destroy(KbArticle $article)
    {
        $article->delete();
        return redirect()->route('admin.support.kb.index')->with('success', 'Article deleted.');
    }
}
