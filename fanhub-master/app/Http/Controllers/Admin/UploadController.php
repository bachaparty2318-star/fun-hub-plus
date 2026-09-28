<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['kind' => 'required|in:image,video,audio', 'file' => 'required|file']);
        $rules = match ($request->input('kind')) {
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'video' => ['required', 'file', 'mimes:mp4,webm', 'max:51200'],
            'audio' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:20480'],
        };
        $request->validate(['file' => $rules]);
        $path = $request->file('file')->store('admin/'.$request->input('kind'), 'public');
        abort_unless($path, 500, 'Upload could not be saved.');

        return response()->json(['data' => [
            'path' => $path, 'url' => Storage::disk('public')->url($path),
            'mime_type' => $request->file('file')->getMimeType(), 'size' => $request->file('file')->getSize(),
        ]], 201);
    }
}
