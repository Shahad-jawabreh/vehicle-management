<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{

    public function index(): JsonResponse
    {
        $devices = Device::with(['vehicle', 'providers'])->get();

        return response()->json($devices);
    }


    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:50|unique:devices,serial_number',
            'device_type' => 'required|string|max:50',
            'is_active' => 'sometimes|boolean',
            'vehicle_id' => 'required|exists:vehicles,id', // Must belong to an existing vehicle
        ]);

        $device = Device::create($validated);
        $device->load(['vehicle', 'providers']);

        return response()->json([
            'message' => 'Device created successfully.',
            'data' => $device
        ], 201);
    }


    public function show(string $id): JsonResponse
    {
        $device = Device::with(['vehicle', 'providers'])->findOrFail($id);

        return response()->json($device);
    }


    public function update(Request $request, string $id): JsonResponse
    {
        $device = Device::findOrFail($id);

        $validated = $request->validate([
            'serial_number' => 'sometimes|string|max:50|unique:devices,serial_number,' . $id,
            'device_type' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
            'vehicle_id' => 'sometimes|exists:vehicles,id',
        ]);

        $device->update($validated);
        $device->load(['vehicle', 'providers']);

        return response()->json([
            'message' => 'Device updated successfully.',
            'data' => $device
        ]);
    }


    public function destroy(string $id): JsonResponse
    {
        $device = Device::findOrFail($id);
        $device->delete();

        return response()->json(['message' => 'Device deleted successfully and related providers deleted.'], 204);
    }
}
