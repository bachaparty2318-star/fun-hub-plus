<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        abort_if($request->user() && ! $request->user()->is_active, 403, 'Your account is inactive.');
        $data = $request->validate(['type' => 'required|in:bug,suggestion,query', 'message' => 'required|string|max:10000']);
        $feedback = Feedback::create(array_merge($data, ['user_id' => $request->user()?->getKey(), 'status' => 'open']));

        return response()->json(['data' => $feedback->refresh()], 201);
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved', 'per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1',
        ]);
        $query = $request->user()->feedback()->orderByDesc('feedback_id');
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return $query->paginate($data['per_page'] ?? 20);
    }

    public function show(Request $request, int $id)
    {
        return response()->json(['data' => $request->user()->feedback()->findOrFail($id)]);
    }
}
