<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use Illuminate\Http\Request;

class EventTypeController extends Controller
{
    public function index()
    {
        $events = EventType::all();
        return response()->json($events);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_special' => 'boolean',
        ]);

        $event = EventType::create($validated);

        return response()->json([
            'message' => 'EventType created successfully.',
            'event' => $event,
        ], 201);
    }

    /**
     * Display the specified event.
     */
    public function show(EventType $event)
    {
        return response()->json($event);
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, EventType $event)
    {
        $validated = $request->validate([
            'type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_special' => 'boolean',
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'EventType updated successfully.',
            'event' => $event,
        ]);
    }

    /**
     * Remove the specified event.
     */
    public function destroy(EventType $event)
    {
        $event->delete();

        return response()->json(['message' => 'EventType deleted successfully.']);
    }
    public function activeEvent() {
        $event = EventType::where('is_active', true)->get();
        return response()->json($event);
    }
}
