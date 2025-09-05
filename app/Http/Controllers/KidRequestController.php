<?php

namespace App\Http\Controllers;

use App\Models\KidRequest;
use App\Models\UserKid;
use App\Models\UserParents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KidRequestController extends Controller
{
    /**
     * Get all requests for the current kid user
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            Log::info('KidRequestController@index start', [
                'user_id' => Auth::id(),
                'auth_user_type' => Auth::user()?->type
            ]);
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            if (Auth::user()->type !== 'child') {
                return response()->json(['message' => 'Only kid user allowed'], 403);
            }
            $requests = KidRequest::where('child_id', Auth::id())
                ->with('parent:id,name,avatar')
                ->orderBy('created_at', 'desc')
                ->get();
            Log::info('KidRequestController@index found requests', ['count' => $requests->count()]);
            return response()->json($requests);
        } catch (\Throwable $e) {
            Log::error('KidRequestController@index error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Server error','error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:toy,food,playground,activity',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $kidRequest = new KidRequest();
        $kidRequest->child_id = $user->id;
        $kidRequest->title = $request->title;
        $kidRequest->description = $request->description;
        $kidRequest->type = $request->type;
        $kidRequest->status = KidRequest::STATUS_PENDING;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('kid_requests', 'public');
            $kidRequest->image = $path;
        }

        $kidRequest->save();

        return response()->json($kidRequest, 201);
    }

    /**
     * Get a specific request
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $kidRequest = KidRequest::where('id', $id)
            ->where('child_id', $user->id)
            ->with('parent:id,name,avatar')
            ->firstOrFail();

        return response()->json($kidRequest);
    }

    /**
     * Get all pending requests for parent
     *
     * @return \Illuminate\Http\Response
     */
    public function parentPendingRequests()
    {
        $user = Auth::user();

        // Get all kids associated with this parent
        $parent = UserParents::findOrFail($user->id);
        $kidIds = $parent->kids()->pluck('child_id')->toArray();

        // Get all pending requests from these kids
        $requests = KidRequest::whereIn('child_id', $kidIds)
            ->where('status', KidRequest::STATUS_PENDING)
            ->with('child:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Get all requests for parent
     *
     * @return \Illuminate\Http\Response
     */
    public function parentAllRequests()
    {
        $user = Auth::user();

        // Get all kids associated with this parent
        $parent = UserParents::findOrFail($user->id);
        $kidIds = $parent->kids()->pluck('users.id')->toArray();

        // Get all requests from these kids
        $requests = KidRequest::whereIn('child_id', $kidIds)
            ->with('child:id,name,email,avatar')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Get all requests for the parent dashboard
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function parentRequests(Request $request)
    {
        $user = $request->user();

        try {
            // Get all kids associated with this parent
            $parent = UserParents::findOrFail($user->id);
            $kidIds = $parent->kids()->pluck('users.id')->toArray();

            // Get all requests from these kids
            $requests = KidRequest::whereIn('child_id', $kidIds)
                ->with('child')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'requests' => $requests
            ]);
        } catch (\Exception $e) {
            Log::error('Error in parentRequests: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all requests for a specific kid (parent view)
     *
     * @param \Illuminate\Http\Request $request
     * @param int $kidId
     * @return \Illuminate\Http\Response
     */
    public function kidRequests(Request $request, $kidId)
    {
        $user = $request->user();

        // Make sure the kid belongs to this parent
        $parent = UserParents::findOrFail($user->id);
        $kidExists = $parent->kids()->where('users.id', $kidId)->exists();

        if (!$kidExists) {
            return response()->json(['message' => 'Kid not found or not related to this parent'], 404);
        }

        // Get all requests from this kid
        $requests = KidRequest::where('child_id', $kidId)
            ->with('child')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'requests' => $requests
        ]);
    }

    /**
     * Process a request (approve/reject)
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function processRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'scheduled_time' => 'nullable|date',
            'parent_note' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Find the request
        $kidRequest = KidRequest::findOrFail($id);

        // Check if the kid belongs to this parent
        $parent = UserParents::findOrFail($user->id);
        $kidExists = $parent->kids()->where('users.id', $kidRequest->child_id)->exists();

        if (!$kidExists) {
            return response()->json(['message' => 'Request not found or not related to your kids'], 404);
        }

        // Update the request
        $kidRequest->status = $request->status;
        $kidRequest->parent_id = $user->id;
        $kidRequest->scheduled_time = $request->scheduled_time;
        $kidRequest->parent_note = $request->parent_note;
        $kidRequest->save();

        return response()->json([
            'message' => 'Request processed successfully',
            'request' => $kidRequest
        ]);
    }

    /**
     * Mark a request as completed
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function completeRequest(Request $request, $id)
    {
        $user = $request->user();

        // Find the request
        $kidRequest = KidRequest::findOrFail($id);

        // Check if the kid belongs to this parent
        $parent = UserParents::findOrFail($user->id);
        $kidExists = $parent->kids()->where('users.id', $kidRequest->child_id)->exists();

        if (!$kidExists) {
            return response()->json(['message' => 'Request not found or not related to your kids'], 404);
        }

        // Make sure the request is in approved status
        if ($kidRequest->status !== KidRequest::STATUS_APPROVED) {
            return response()->json(['message' => 'Only approved requests can be marked as completed'], 422);
        }

        // Update the request
        $kidRequest->status = KidRequest::STATUS_COMPLETED;
        $kidRequest->save();

        return response()->json([
            'message' => 'Request marked as completed',
            'request' => $kidRequest
        ]);
    }

    /**
     * Update the status of a request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,completed',
            'scheduled_time' => 'nullable|date',
            'parent_note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $kidRequest = KidRequest::findOrFail($id);

        // Get all kids associated with this parent
        $parent = UserParents::findOrFail($user->id);
        $kidIds = $parent->kids()->pluck('child_id')->toArray();

        // Check if the request belongs to one of parent's kids
        if (!in_array($kidRequest->child_id, $kidIds)) {
            return response()->json(['message' => 'Unauthorized. This request does not belong to your child.'], 403);
        }

        $kidRequest->status = $request->status;
        $kidRequest->parent_id = $user->id;

        if ($request->filled('scheduled_time')) {
            $kidRequest->scheduled_time = $request->scheduled_time;
        }

        if ($request->filled('parent_note')) {
            $kidRequest->parent_note = $request->parent_note;
        }

        $kidRequest->save();

        return response()->json($kidRequest);
    }

    /**
     * Kid classifies their own request (need / want / none to clear)
     */
    public function classify(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'classification' => 'required|in:need,want,none'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = $request->user();
        $kidRequest = KidRequest::where('id', $id)->where('child_id', $user->id)->firstOrFail();
        $kidRequest->classification = $request->classification === 'none' ? null : $request->classification;
        $kidRequest->save();
        return response()->json([
            'message' => 'Classification updated',
            'request' => $kidRequest
        ]);
    }
}
