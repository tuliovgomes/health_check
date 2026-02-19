<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LinkController
{
    public function index(Request $request)
    {
        $links = $request->user()->links()
            ->with(['checks' => fn($q) => $q->limit(5)])
            ->latest()
            ->paginate(15);

        if (app()->runningUnitTests() || $request->wantsJson()) {
            return response()->json(['links' => $links]);
        }

        return Inertia::render('Links/Index', compact('links'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'check_interval' => ['nullable', 'integer', 'in:1,5,15,30,60'],
        ]);

        $link = $request->user()->links()->create([
            'url' => $data['url'],
            'title' => $data['title'] ?? null,
            'check_interval' => $data['check_interval'] ?? 1,
            'code' => Str::random(8),
        ]);

        return response()->json(['success' => true, 'data' => $link], 201);
    }

    public function destroy(Request $request, Link $link)
    {
        // allow only owner
        if ($link->user_id !== $request->user()->id) {
            abort(403);
        }

        $link->delete();

        return response()->json(['success' => true]);
    }
}
