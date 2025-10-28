<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return response()->json(Company::with('vehicles', 'users')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $company = Company::create($validated);

        return response()->json($company, 201);
    }

    public function show(Company $company)
    {
        return response()->json($company->load('users', 'vehicles'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate(['name' => 'required|string|max:100']);
        $company->update($validated);
        return response()->json($company);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['message' => 'Company deleted successfully']);
    }
}
