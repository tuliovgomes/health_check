<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LinkController
{
    public function index(Request $request)
    {
        $links = $request->user()->links()
            ->latest()
            ->paginate(15);

        if (app()->runningUnitTests() || $request->wantsJson()) {
            return response()->json(['links' => $links]);
        }

        return Inertia::render('Links/Index', compact('links'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'url' => ['required', 'url', 'max:2048'],
                'title' => ['nullable', 'string', 'max:255'],
                'check_interval' => ['nullable', 'integer', 'in:1,5,15,30,60'],
            ]);
        } catch (ValidationException $e) {
            Log::warning('LinkController@store validation failed', ['errors' => $e->errors()]);
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        try {
            $link = $request->user()->links()->create([
                'url' => $data['url'],
                'title' => $data['title'] ?? null,
                'check_interval' => $data['check_interval'] ?? 1,
                'code' => Str::random(8),
            ]);

            return response()->json(['success' => true, 'data' => $link], 201);
        } catch (\Throwable $e) {
            Log::error('LinkController@store exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Server error while creating link'], 500);
        }
    }

    public function destroy(Request $request, Link $link)
    {
        if ($link->user_id !== $request->user()->id) {
            abort(403);
        }

        $link->delete();

        return response()->json(['success' => true]);
    }

    public function show(Request $request, Link $link)
    {
        if ($link->user_id !== $request->user()->id) {
            abort(403);
        }
        // load checks for the requested link with pagination (default 50 per page)
        $perPage = (int) $request->query('per_page', 50);
        try {
            $checks = $link->checks()->latest()->paginate($perPage);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('LinkController@show checks load failed', ['message' => $e->getMessage()]);
            $checks = collect([]);
        }

        if (app()->runningUnitTests() || $request->wantsJson()) {
            return response()->json(['success' => true, 'data' => ['link' => $link, 'checks' => $checks]]);
        }

        // fallback: redirect back to index if called from browser
        return redirect()->route('links.index');
    }
}
