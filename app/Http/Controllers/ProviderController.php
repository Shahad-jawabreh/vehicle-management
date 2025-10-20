<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ProviderController extends Controller
{
    public function index(): JsonResponse
    {
        $providers = Provider::with('device')->get();

        return response()->json($providers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
            'data_key' => 'required|string|max:50|unique:providers,data_key',
            'device_id' => 'required|exists:devices,id', // Must belong to an existing device
        ]);

        $provider = Provider::create($validated);

        // Retrieve the provider with its relations for the response
        $provider->load('device');

        return response()->json([
            'message' => 'Provider created successfully.',
            'data' => $provider
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $provider = Provider::with('device')->findOrFail($id);
        return response()->json($provider);
    }
    
    public function update(Request $request, string $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|max:50',
            'data_key' => 'sometimes|string|max:50|unique:providers,data_key,' . $id,
            'device_id' => 'sometimes|exists:devices,id',
        ]);

        $provider->update($validated);
        $provider->load('device');

        return response()->json([
            'message' => 'Provider updated successfully.',
            'data' => $provider
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);

        $provider->delete();

        return response()->json(['message' => 'Provider deleted successfully.'], 204);
    }
}
