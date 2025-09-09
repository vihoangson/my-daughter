<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogMediaController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'parent') {
            return response()->json(['message'=>'Forbidden'],403);
        }
        $request->validate([
            'image' => 'required|image|max:4096' // 4MB
        ]);
        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $name = Str::uuid()->toString().'.'.$ext;
        $path = $file->storeAs('blog_images', $name, 'public');
        $url = Storage::disk('public')->url($path);
        return response()->json([
            'url' => $url,
            'path' => $path
        ],201);
    }
}

