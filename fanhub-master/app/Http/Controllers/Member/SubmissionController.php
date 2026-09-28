<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FanSubmission;
use App\Models\UserActivityLog;
use App\Rules\SafeUrl;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    public function index(Request $request, HtmlSanitizer $sanitizer)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:pending,approved,rejected', 'per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1',
        ]);
        $query = $request->user()->submissions()->with('category:category_id,name,slug')->orderByDesc('submission_id');
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return $query->paginate($data['per_page'] ?? 20)->through(function ($submission) use ($sanitizer) {
            $submission->body_html = $sanitizer->clean($submission->body_html);

            return $submission;
        });
    }

    public function show(Request $request, int $id, HtmlSanitizer $sanitizer)
    {
        $submission = $request->user()->submissions()->with('category:category_id,name,slug')->findOrFail($id);
        $submission->body_html = $sanitizer->clean($submission->body_html);

        return response()->json(['data' => $submission]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        return DB::transaction(function () use ($request, $data) {
            $submission = FanSubmission::create(array_merge($data, ['user_id' => $request->user()->getKey(), 'status' => 'pending']));
            UserActivityLog::create(['user_id' => $request->user()->getKey(), 'activity_type' => 'submit_fan_content', 'reference_type' => 'submission', 'reference_id' => $submission->getKey()]);

            return response()->json(['data' => $submission->refresh()], 201);
        });
    }

    public function update(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $submission = $request->user()->submissions()->lockForUpdate()->findOrFail($id);
            abort_unless($submission->status === 'pending', 409, 'Only pending submissions can be edited. Submit a new entry after rejection.');
            $submission->update($this->validated($request, true));

            return response()->json(['data' => $submission->refresh()]);
        });
    }

    public function destroy(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $submission = $request->user()->submissions()->lockForUpdate()->findOrFail($id);
            abort_unless($submission->status === 'pending', 409, 'Only pending submissions can be withdrawn.');
            $submission->delete();

            return response()->noContent();
        });
    }

    public function image(Request $request)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,webp,gif|max:5120']);
        $path = $request->file('file')->store('submissions/'.$request->user()->getKey(), 'public');
        abort_unless($path, 500, 'Image could not be saved.');

        return response()->json(['data' => ['url' => Storage::disk('public')->url($path), 'path' => $path]], 201);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        $presence = $updating ? 'sometimes' : 'required';
        $data = $request->validate([
            'category_id' => [$presence, 'required', 'integer', 'exists:categories,category_id'],
            'title' => [$presence, 'required', 'string', 'max:200'],
            'body_html' => [$presence, 'required', 'string', 'max:200000'],
            'cover_image_url' => ['nullable', 'string', 'max:500', new SafeUrl],
        ]);
        if (isset($data['body_html'])) {
            $data['body_html'] = app(HtmlSanitizer::class)->clean($data['body_html']);
            if (trim($data['body_html']) === '') {
                throw ValidationException::withMessages(['body_html' => 'Provide safe article content.']);
            }
        }

        return $data;
    }
}
