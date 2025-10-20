<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    public function index(): JsonResponse
    {
        $vehicles = Vehicle::with('devices')->get();

        return response()->json($vehicles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'model_name' => 'required|string|max:100',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json([
            'message' => 'Vehicle created successfully.',
            'data' => $vehicle
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $vehicle = Vehicle::with('devices.providers')->findOrFail($id);

        return response()->json($vehicle);
    }


    public function update(Request $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'license_plate' => 'sometimes|string|max:20|unique:vehicles,license_plate,' . $id,
            'model_name' => 'sometimes|string|max:100',
        ]);

        $vehicle->update($validated);

        return response()->json([
            'message' => 'Vehicle updated successfully.',
            'data' => $vehicle
        ]);
    }


    public function destroy(string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully and cascading children deleted.'], 204);
    }
}
