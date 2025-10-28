<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index()
    {
        return response()->json(Provider::with('device')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'type' => 'required|string',
            'name' => 'nullable|string',
            'meta' => 'nullable|json',
        ]);

        $provider = Provider::create($validated);
        return response()->json($provider, 201);
    }

    public function show(Provider $provider)
    {
        return response()->json($provider->load('device'));
    }

    public function update(Request $request, Provider $provider)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'meta' => 'nullable|json',
        ]);

        $provider->update($validated);
        return response()->json($provider);
    }

    public function destroy(Provider $provider)
    {
        $provider->delete();
        return response()->json(['message' => 'Provider deleted successfully']);
    }
}
