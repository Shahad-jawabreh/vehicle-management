<?php

namespace App\Http\Controllers;

use App\Jobs\CheckAllVehiclesJob;
use App\Models\Vehicle;
use App\Models\UserEvent;
use App\Models\EventType;
use Illuminate\Http\Request;
use MatanYadaev\EloquentSpatial\Objects\Point;
use App\Notifications\EventNotification; // Fixed: was 'apP'

class VehicleController extends Controller
{
    public function index()
    {
        return response()->json(Vehicle::with('user')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'license_plate' => 'required|string|max:50|unique:vehicles,license_plate',
            'model_name' => 'nullable|string|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric', // Fixed: was 'longtitude'
            'speed' => 'nullable|integer|max:50',
        ]);

        $vehicle = new Vehicle($validated);

        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $vehicle->location = new Point($validated['latitude'], $validated['longitude']);
        }

        $vehicle->save();
        return response()->json($vehicle, 201);
    }

    public function show(Vehicle $vehicle)
    {
        return response()->json($vehicle->load('user'));
    }

    public function updatePosition(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|min:-90|max:90',
            'longitude' => 'required|numeric|min:-180|max:180',
        ]);

        $vehicle->location = new Point($validated['latitude'], $validated['longitude']);
        $vehicle->save();

        return response()->json([
            'message' => 'Vehicle position updated successfully.',
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
