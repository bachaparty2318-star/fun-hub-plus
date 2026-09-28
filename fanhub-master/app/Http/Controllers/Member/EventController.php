<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:200', 'city' => 'nullable|string|max:100',
            'category_id' => 'sometimes|integer|exists:categories,category_id',
            'event_type' => 'sometimes|in:convention,premiere,release,meetup,screening',
            'from' => 'sometimes|date_format:Y-m-d', 'to' => 'sometimes|date_format:Y-m-d',
            'include_past' => 'sometimes|boolean',
            'latitude' => 'required_with:longitude,radius_km|nullable|numeric|between:-90,90',
            'longitude' => 'required_with:latitude,radius_km|nullable|numeric|between:-180,180',
            'radius_km' => 'sometimes|numeric|min:1|max:500',
            'per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1',
        ]);
        if (isset($data['from'], $data['to']) && $data['to'] < $data['from']) {
            throw ValidationException::withMessages(['to' => 'End of the range must be on or after its start.']);
        }
        $query = Event::with('category:category_id,name,slug')->select([
            'event_id', 'category_id', 'title', 'description', 'event_type', 'event_date', 'end_date',
            'city', 'venue', 'latitude', 'longitude', 'ticket_link',
        ]);
        foreach (['city', 'category_id', 'event_type'] as $field) {
            if (isset($data[$field])) {
                $query->where($field, $data[$field]);
            }
        }
        if (! empty($data['q'])) {
            $query->where('title', 'like', '%'.$data['q'].'%');
        }
        // Include multi-day events overlapping the calendar window.
        if (isset($data['from'])) {
            $query->whereRaw('COALESCE(end_date, event_date) >= ?', [$data['from'].' 00:00:00']);
        } elseif (empty($data['include_past'])) {
            $query->whereRaw('COALESCE(end_date, event_date) >= ?', [now()]);
        }
        if (isset($data['to'])) {
            $query->where('event_date', '<=', $data['to'].' 23:59:59');
        }
        if (isset($data['latitude'], $data['longitude'])) {
            // Bound values, with ACOS clamped for floating-point precision at zero distance.
            $distance = '6371 * ACOS(LEAST(1, GREATEST(-1, COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude)))))';
            $bindings = [$data['latitude'], $data['longitude'], $data['latitude']];
            $query->whereNotNull('latitude')->whereNotNull('longitude')
                ->selectRaw($distance.' AS distance_km', $bindings)
                ->whereRaw($distance.' <= ?', [...$bindings, $data['radius_km'] ?? 50])
                ->orderBy('distance_km');
        }

        return $query->orderBy('event_date')->orderBy('event_id')->paginate($data['per_page'] ?? 20)->withQueryString();
    }

    public function show(int $id)
    {
        $event = Event::with('category:category_id,name,slug')->findOrFail($id);

        return response()->json(['data' => $event->makeHidden('created_by')]);
    }
}
