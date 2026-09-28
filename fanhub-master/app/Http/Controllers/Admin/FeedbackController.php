<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved', 'type' => 'sometimes|in:bug,suggestion,query',
            'q' => 'nullable|string|max:200', 'page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $query = Feedback::with('user:user_id,name,email')->orderByDesc('feedback_id');
        foreach (['status', 'type'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }
        if (! empty($data['q'])) {
            $query->where('message', 'like', '%'.$data['q'].'%');
        }

        return $query->paginate($data['per_page'] ?? 20)->withQueryString();
    }

    public function show(int $id)
    {
        return response()->json(['data' => Feedback::with('user:user_id,name,email')->findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|in:open,in_progress,resolved']);
        $feedback = Feedback::findOrFail($id);
        $data['resolved_at'] = $data['status'] === 'resolved' ? ($feedback->resolved_at ?? now()) : null;
        $feedback->update($data);

        return response()->json(['data' => $feedback->refresh()]);
    }

    public function destroy(int $id)
    {
        Feedback::findOrFail($id)->delete();

        return response()->noContent();
    }
}
