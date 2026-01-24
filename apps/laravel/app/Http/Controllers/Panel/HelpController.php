<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $q = KnowledgeBaseArticle::query()->orderByDesc('id');
        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        if ($request->filled('q')) {
            $term = '%' . trim($request->string('q')->toString()) . '%';
            $q->where('title', 'like', $term);
        }
        $articles = $q->paginate(20)->appends($request->query());

        return view('panel.help.index', [
            'articles' => $articles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:knowledge,res_ad_copy'],
            'title' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:50000'],
            'language' => ['required', 'in:tr,en'],
        ]);

        KnowledgeBaseArticle::query()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'content' => $data['content'],
            'language' => $data['language'],
        ]);

        return redirect()->to('/help')->with('status', 'İçerik eklendi.');
    }
}

