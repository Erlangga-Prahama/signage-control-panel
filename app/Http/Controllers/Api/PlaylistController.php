<?php

namespace App\Http\Controllers\Api;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class PlaylistController extends Controller
{
    public function index()
    {
        return Playlist::with('items.content')->latest()->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $playlist = Playlist::create($validator->validated());
        
        return response()->json($playlist, 201);
    }

    public function show(Playlist $playlist)
    {
        return $playlist->load('items.content', 'devices');
    }

    public function update(Request $request, Playlist $playlist)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $playlist->update($validator->validated());

        return response()->json($playlist->fresh());
    }

       public function destroy(Playlist $playlist)
    {
        $playlist->delete();
 
        return response()->json(['message' => 'Playlist dihapus.']);
    }
 
    /**
     * Replace the playlist's item list wholesale — simplest mental model
     * for a drag-and-drop reorder UI: send the full ordered array back.
     * Body: { items: [{ content_id, durasi_detik }, ...] }
     */
    public function syncItems(Request $request, Playlist $playlist)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'present|array',
            'items.*.content_id' => 'required|exists:contents,id',
            'items.*.durasi_detik' => 'nullable|integer|min:1|max:3600',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
 
        $playlist->items()->delete();
 
        foreach ($request->input('items', []) as $index => $item) {
            PlaylistItem::create([
                'playlist_id' => $playlist->id,
                'content_id' => $item['content_id'],
                'urutan' => $index,
                'durasi_detik' => $item['durasi_detik'] ?? 10,
            ]);
        }
 
        return response()->json($playlist->fresh()->load('items.content'));
    }
}
