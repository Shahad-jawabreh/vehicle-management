<?php

namespace App\Http\Controllers;

use App\Models\CompanyZone;
use Illuminate\Http\Request;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CompanyZonesController extends Controller
{

    public function index()
    {
        return response()->json(CompanyZone::with('company')->get());
    }

    /**
     * Store a newly created company zone.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:100',
            'latitude' => 'required|numeric|min:-90|max:90',
            'longitude' => 'required|numeric|min:-180|max:180',
            'radius' => 'required|numeric|min:1',
            'created_by' => 'nullable|exists:users,id',
        ]);

        $zone = new CompanyZone();
        $zone->company_id = $validated['company_id'];
        $zone->name = $validated['name'];
        $zone->radius = $validated['radius'];
        $zone->created_by = $validated['created_by'] ?? null;
        $zone->location = new Point($validated['latitude'], $validated['longitude']); // Y = latitude, X = longitude

        $zone->save();

        return response()->json($zone, 201);
    }

    
    public function update(Request $request, CompanyZone $companyZone)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'radius' => 'nullable|numeric|min:1',
            'created_by' => 'nullable|exists:users,id',
        ]);

        if (isset($validated['company_id'])) {
            $companyZone->company_id = $validated['company_id'];
        }
        if (isset($validated['name'])) {
            $companyZone->name = $validated['name'];
        }
        if (isset($validated['radius'])) {
            $companyZone->radius = $validated['radius'];
        }
        if (isset($validated['created_by'])) {
            $companyZone->created_by = $validated['created_by'];
        }
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $companyZone->center = new Point($validated['latitude'], $validated['longitude']);
        }

        $companyZone->save();

        return response()->json($companyZone);
    }

    /**
     * Remove the specified company zone.
     */
    public function destroy(CompanyZone $companyZone)
    {
        $companyZone->delete();

        return response()->json(['message' => 'Company zone deleted successfully']);
    }
}
