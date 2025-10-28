<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        return response()->json(Device::with('vehicle')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|unique:devices,serial_number',
            'device_type' => 'required|string',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $device = Device::create($validated);
        return response()->json($device, 201);
    }

    public function show(Device $device)
    {
        return response()->json($device->load('vehicle'));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'is_active' => 'boolean',
        ]);

        $device->update($validated);
        return response()->json($device);
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return response()->json(['message' => 'Device deleted successfully']);
    }
}
