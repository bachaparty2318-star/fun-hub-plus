<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\FanSubmission;
use App\Rules\SafeUrl;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:pending,approved,rejected', 'category_id' => 'sometimes|integer|exists:categories,category_id',
            'q' => 'nullable|string|max:200', 'page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $query = FanSubmission::with(['user:user_id,name,email', 'category', 'reviewer:user_id,name'])->orderByDesc('submission_id');
        foreach (['status', 'category_id'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }
        if (! empty($data['q'])) {
            $query->where('title', 'like', '%'.$data['q'].'%');
        }
        $results = $query->paginate($data['per_page'] ?? 20)->withQueryString();
        foreach ($results as $submission) {
            $submission->body_html = app(HtmlSanitizer::class)->clean($submission->body_html);
        }

        return $results;
    }

    public function show(int $id, HtmlSanitizer $sanitizer)
    {
        $submission = FanSubmission::with(['user:user_id,name,email', 'category', 'reviewer:user_id,name', 'publishedArticle'])->findOrFail($id);
        // Untrusted submissions are safe even before moderation.
        $submission->body_html = $sanitizer->clean($submission->body_html);

        return response()->json(['data' => $submission]);
    }

    public function review(Request $request, int $id, HtmlSanitizer $sanitizer)
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected', 'review_notes' => 'nullable|string|max:500',
            'title' => 'sometimes|required|string|max:200', 'body_html' => 'sometimes|required|string|max:200000',
            'category_id' => 'sometimes|required|integer|exists:categories,category_id',
            'cover_image_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'is_featured' => 'sometimes|boolean',
        ]);

        return DB::transaction(function () use ($request, $id, $data, $sanitizer) {
            $submission = FanSubmission::lockForUpdate()->findOrFail($id);
            abort_unless($submission->status === 'pending', 409, 'This submission has already been reviewed.');
            $articleId = null;
            if ($data['decision'] === 'approved') {
                $body = $sanitizer->clean($data['body_html'] ?? $submission->body_html);
                if (trim($body) === '') {
                    throw ValidationException::withMessages(['body_html' => 'Safe article content is required before approval.']);
                }
                $cover = array_key_exists('cover_image_url', $data) ? $data['cover_image_url'] : $submission->cover_image_url;
                if ($cover !== null && ! SafeUrl::allowed($cover)) {
                    throw ValidationException::withMessages(['cover_image_url' => 'Replace the unsafe cover URL before approval.']);
                }
                $article = Article::create([
                    'title' => $data['title'] ?? $submission->title,
                    'category_id' => $data['category_id'] ?? $submission->category_id,
                    'body_html' => $body, 'cover_image_url' => $cover,
                    'author_id' => $submission->user_id, 'is_featured' => $data['is_featured'] ?? false,
                    'published_at' => now(),
                ]);
                $articleId = $article->getKey();
            }
            $submission->update([
                'status' => $data['decision'], 'reviewed_by' => $request->user()->getKey(),
                'review_notes' => $data['review_notes'] ?? null, 'reviewed_at' => now(),
                'published_article_id' => $articleId,
            ]);
            $submission->body_html = $sanitizer->clean($submission->body_html);

            return response()->json(['data' => $submission->load('publishedArticle')]);
        });
    }

    public function destroy(int $id)
    {
        return DB::transaction(function () use ($id) {
            $submission = FanSubmission::lockForUpdate()->findOrFail($id);
            $submission->delete();

            // Published articles are independent and are managed through articles.
            return response()->noContent();
        });
    }
}
