<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SegmentList;
use Illuminate\Http\Request;

class ListsController extends Controller
{
    public function index(Request $request)
    {
        $q = SegmentList::query()->orderByDesc('id');
        if ($request->filled('q')) {
            $term = '%' . trim($request->string('q')->toString()) . '%';
            $q->where('name', 'like', $term);
        }
        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        $lists = $q->paginate(20)->appends($request->query());

        return view('panel.lists.index', [
            'lists' => $lists,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', 'in:lead,contact'],
        ]);

        SegmentList::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->to('/lists')->with('status', 'Liste oluşturuldu.');
    }
}

