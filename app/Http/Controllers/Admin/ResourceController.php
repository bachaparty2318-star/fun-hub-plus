<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\AdminDeletion;
use App\Services\AdminResources;
use App\Services\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResourceController extends Controller
{
    public function __construct(private AdminResources $resources) {}

    public function index(Request $request, string $resource)
    {
        $definition = $this->resources->get($resource);
        $model = new $definition['model'];
        $rules = [
            'q' => 'nullable|string|max:200', 'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort' => ['sometimes', Rule::in(array_merge([$model->getKeyName()], $definition['sort']))],
            'direction' => 'sometimes|in:asc,desc', 'release_year' => 'sometimes|integer|min:1000|max:9999',
        ];
        foreach ($definition['filter'] as $field) {
            $fieldRules = $definition['rules'][$field];
            $rules[$field] = array_values(array_filter(is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules), fn ($rule) => $rule !== 'required'));
        }
        $input = $request->validate($rules);
        $query = $model->newQuery()->with($definition['with']);
        if (! empty($input['q']) && $definition['search']) {
            $query->where(function ($query) use ($definition, $input) {
                foreach ($definition['search'] as $field) {
                    $query->orWhere($field, 'like', '%'.$input['q'].'%');
                }
            });
        }
        foreach ($definition['filter'] as $field) {
            if (isset($input[$field])) {
                $query->where($field, $input[$field]);
            }
        }
        if (isset($input['release_year']) && array_key_exists('release_date', $definition['rules'])) {
            $query->whereBetween('release_date', [$input['release_year'].'-01-01', $input['release_year'].'-12-31']);
        }
        $sort = $input['sort'] ?? $model->getKeyName();
        $query->orderBy($sort, $input['direction'] ?? 'desc');
        if ($sort !== $model->getKeyName()) {
            $query->orderBy($model->getKeyName());
        }

        return $query->paginate($input['per_page'] ?? 20)->withQueryString();
    }

    public function show(string $resource, int $id)
    {
        $definition = $this->resources->get($resource);

        return response()->json(['data' => $definition['model']::with($definition['with'])->findOrFail($id)]);
    }

    public function store(Request $request, string $resource)
    {
        return DB::transaction(function () use ($request, $resource) {
            $definition = $this->resources->get($resource);
            $data = $this->validated($request, $definition, null);
            $tags = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);
            if ($definition['creator']) {
                $data[$definition['creator']] = $request->user()->getKey();
            }
            $record = $definition['model']::create($data);
            if ($tags !== null) {
                $record->tags()->sync($tags);
            }

            return response()->json(['data' => $record->refresh()->load($definition['with'])], 201);
        });
    }

    public function update(Request $request, string $resource, int $id)
    {
        return DB::transaction(function () use ($request, $resource, $id) {
            $definition = $this->resources->get($resource);
            $record = $definition['model']::lockForUpdate()->findOrFail($id);
            $definition = $this->resources->get($resource, $record);
            $data = $this->validated($request, $definition, $record);
            $tags = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);
            $record->update($data);
            if ($tags !== null) {
                $record->tags()->sync($tags);
            }

            return response()->json(['data' => $record->refresh()->load($definition['with'])]);
        });
    }

    public function destroy(string $resource, int $id, AdminDeletion $deletion)
    {
        return DB::transaction(function () use ($resource, $id, $deletion) {
            $definition = $this->resources->get($resource);
            $record = $definition['model']::lockForUpdate()->findOrFail($id);
            $deletion->delete($record);

            return response()->noContent();
        });
    }

    private function validated(Request $request, array $definition, $record): array
    {
        $rules = $definition['rules'];
        if ($record) {
            foreach ($rules as $field => &$rule) {
                if (! str_contains($field, '*')) {
                    $rule = array_merge(['sometimes'], is_array($rule) ? $rule : explode('|', $rule));
                }
            }
            unset($rule);
        }
        $data = $request->validate($rules);
        if (isset($data['body_html'])) {
            $data['body_html'] = app(HtmlSanitizer::class)->clean($data['body_html']);
            if (trim($data['body_html']) === '') {
                throw ValidationException::withMessages(['body_html' => 'Please provide safe article content.']);
            }
        }
        if ($definition['model'] === Event::class) {
            $merged = array_merge($record?->getAttributes() ?? [], $data);
            if (! empty($merged['end_date']) && Carbon::parse($merged['end_date'])->lt(Carbon::parse($merged['event_date']))) {
                throw ValidationException::withMessages(['end_date' => 'End date must be on or after the start date.']);
            }
            if (isset($merged['latitude']) !== isset($merged['longitude'])) {
                throw ValidationException::withMessages(['latitude' => 'Supply both latitude and longitude, or neither.']);
            }
        }

        return $data;
    }
}
