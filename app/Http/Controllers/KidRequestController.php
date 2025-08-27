<?php

namespace App\Http\Controllers;

use App\Models\KidRequest;
use App\Models\UserKid;
use App\Models\UserParents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class KidRequestController extends Controller
{
    /**
     * Get all requests for the current kid user
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $requests = KidRequest::where('child_id', $user->id)
            ->with('parent:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
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
        $kidIds = $parent->kids()->pluck('child_id')->toArray();

        // Get all requests from these kids
        $requests = KidRequest::whereIn('child_id', $kidIds)
            ->with('child:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Update request status (for parents)
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
            'parent_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Get all kids associated with this parent
        $parent = UserParents::findOrFail($user->id);
        $kidIds = $parent->kids()->pluck('child_id')->toArray();

        $kidRequest = KidRequest::where('id', $id)
            ->whereIn('child_id', $kidIds)
            ->firstOrFail();

        $kidRequest->status = $request->status;
        $kidRequest->parent_id = $user->id;

        if ($request->has('scheduled_time')) {
            $kidRequest->scheduled_time = $request->scheduled_time;
        }

        if ($request->has('parent_note')) {
            $kidRequest->parent_note = $request->parent_note;
        }

        $kidRequest->save();

        return response()->json($kidRequest);
    }
}
