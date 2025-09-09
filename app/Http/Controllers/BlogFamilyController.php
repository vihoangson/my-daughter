<?php

namespace App\Http\Controllers;

use App\Models\BlogFamily;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogFamilyController extends Controller
{
    protected function ensureFamily($user): Family
    {
        if ($user->family) return $user->family;
        abort(400,'Chưa có gia đình');
    }

    protected function assertParent($user)
    {
        if ($user->type !== 'parent') {
            abort(403,'Forbidden');
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);
        $family = $this->ensureFamily($user);
        $q = BlogFamily::with(['author:id,name'])
            ->where('family_id',$family->id);
        if ($search = $request->get('search')) {
            $q->where(function($sub) use ($search){
                $sub->where('title','like',"%$search%")
                    ->orWhere('content','like',"%$search%")
                    ->orWhereJsonContains('tags', $search);
            });
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($visibility = $request->get('visibility')) {
            $q->where('visibility',$visibility);
        }
        $posts = $q->orderByDesc('pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);
        return response()->json(['posts'=>$posts]);
    }

    public function show(Request $request, BlogFamily $post)
    {
        $user = $request->user();
        $this->assertParent($user);
        $family = $this->ensureFamily($user);
        if ($post->family_id !== $family->id) {
            return response()->json(['message'=>'Không tìm thấy'],404);
        }
        $post->load(['author:id,name']);
        return response()->json(['post'=>$post]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);
        $family = $this->ensureFamily($user);
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'content' => 'nullable|string',
            'visibility' => 'nullable|in:public,members,private',
            'status' => 'nullable|in:draft,published,archived',
            'tags' => 'nullable', // comma string or array
            'pinned' => 'sometimes|boolean',
        ]);
        $slugBase = Str::slug($data['title']);
        if (!$slugBase) $slugBase = 'post';
        $slug = $slugBase;
        $i=1;
        while (BlogFamily::where('slug',$slug)->exists()) {
            $slug = $slugBase.'-'.$i++;
        }
        $tags = [];
        if (isset($data['tags'])) {
            if (is_string($data['tags'])) {
                $tags = collect(explode(',', $data['tags']))->map(fn($t)=>trim($t))->filter()->unique()->values()->all();
            } elseif (is_array($data['tags'])) {
                $tags = collect($data['tags'])->map(fn($t)=>trim($t))->filter()->unique()->values()->all();
            }
        }
        $post = BlogFamily::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'] ?? null,
            'visibility' => $data['visibility'] ?? 'members',
            'status' => $data['status'] ?? 'draft',
            'tags' => $tags,
            'pinned' => (bool)($data['pinned'] ?? false),
            'published_at' => isset($data['status']) && $data['status']==='published' ? now() : null,
        ]);
        return response()->json(['message'=>'Đã tạo','post'=>$post],201);
    }

    public function update(Request $request, BlogFamily $post)
    {
        $user = $request->user();
        $this->assertParent($user);
        $family = $this->ensureFamily($user);
        if ($post->family_id !== $family->id) return response()->json(['message'=>'Không tìm thấy'],404);
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:180',
            'content' => 'nullable|string',
            'visibility' => 'nullable|in:public,members,private',
            'status' => 'nullable|in:draft,published,archived',
            'tags' => 'nullable',
            'pinned' => 'sometimes|boolean',
        ]);
        if (isset($data['title']) && $data['title'] !== $post->title) {
            $slugBase = Str::slug($data['title']);
            if (!$slugBase) $slugBase = 'post';
            $slug = $slugBase;
            $i=1;
            while (BlogFamily::where('slug',$slug)->where('id','!=',$post->id)->exists()) {
                $slug = $slugBase.'-'.$i++;
            }
            $post->slug = $slug;
            $post->title = $data['title'];
        }
        if (array_key_exists('content',$data)) $post->content = $data['content'];
        if (array_key_exists('visibility',$data)) $post->visibility = $data['visibility'];
        if (array_key_exists('status',$data)) {
            $prev = $post->status;
            $post->status = $data['status'];
            if ($prev !== 'published' && $data['status']==='published' && !$post->published_at) {
                $post->published_at = now();
            }
            if ($data['status']!=='published') {
                // Optionally null published_at if unpublish
            }
        }
        if (array_key_exists('pinned',$data)) $post->pinned = (bool)$data['pinned'];
        if (array_key_exists('tags',$data)) {
            $tags = [];
            if (is_string($data['tags'])) {
                $tags = collect(explode(',', $data['tags']))->map(fn($t)=>trim($t))->filter()->unique()->values()->all();
            } elseif (is_array($data['tags'])) {
                $tags = collect($data['tags'])->map(fn($t)=>trim($t))->filter()->unique()->values()->all();
            }
            $post->tags = $tags;
        }
        $post->save();
        return response()->json(['message'=>'Đã cập nhật','post'=>$post]);
    }

    public function destroy(Request $request, BlogFamily $post)
    {
        $user = $request->user();
        $this->assertParent($user);
        $family = $this->ensureFamily($user);
        if ($post->family_id !== $family->id) return response()->json(['message'=>'Không tìm thấy'],404);
        $post->delete();
        return response()->json(['message'=>'Đã xoá']);
    }
}

