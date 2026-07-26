<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    public function index()
    {
        return Content::latest()->get()->map(fn ($c) => $this->withUrl($c));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:image,video,url,text',
            'file' => 'required_if:tipe,image,video|file|max:51200', // 50MB
            'payload' => 'required_if:tipe,url,text|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = $request->input('payload');

        if (in_array($request->tipe, ['image', 'video']) && $request->hasFile('file')) {
            $payload = $request->file('file')->store('contents', 'public');
        }

        $content = Content::create([
            'judul' => $request->judul,
            'tipe' => $request->tipe,
            'payload' => $payload,
        ]);

        return response()->json($this->withUrl($content), 201);
    }

    public function show(Content $content)
    {
        return $this->withUrl($content);
    }

    public function update(Request $request, Content $content)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'tipe' => 'sometimes|required|in:image,video,url,text',
            'file' => 'nullable|file|max:51200',
            'payload' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->safe()->only(['judul', 'tipe']);

        $tipe = $request->input('tipe', $content->tipe);

        if (in_array($tipe, ['image', 'video']) && $request->hasFile('file')) {
            if ($content->payload && Storage::disk('public')->exists($content->payload)) {
                Storage::disk('public')->delete($content->payload);
            }
            $data['payload'] = $request->file('file')->store('contents', 'public');
        } elseif ($request->filled('payload')) {
            $data['payload'] = $request->input('payload');
        }

        $content->update($data);

        return response()->json($this->withUrl($content->fresh()));
    }

    public function destroy(Content $content)
    {
        if (in_array($content->tipe, ['image', 'video']) && Storage::disk('public')->exists($content->payload)) {
            Storage::disk('public')->delete($content->payload);
        }

        $content->delete();

        return response()->json(['message' => 'Content dihapus.']);
    }

    protected function withUrl(Content $content): array
    {
        return [
            ...$content->toArray(),
            'url' => $content->resolved_url,
        ];
    }
}