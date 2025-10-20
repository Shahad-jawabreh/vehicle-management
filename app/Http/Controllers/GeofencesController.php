<?php

namespace App\Http\Controllers;

use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\geofences ;
class GeofencesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'center_latitude' => 'required|numeric|min:-90|max:90',
            'center_longitude' => 'required|numeric|min:-180|max:180',
            'radius_meters' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $geofence = Geofences::create($validator->validated());

        return response()->json([
            'message' => 'Geofence created successfully',
            'geofence' => $geofence
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(geofences $geofences)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(geofences $geofences)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(UpdategeofencesRequest $request, geofences $geofences)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(geofences $geofences)
    {
        //
    }
}
