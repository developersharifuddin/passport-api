<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        }

        $perPage = $request->input('per_page', 5);
        $users = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'User retrieved successfully.',
            'data' => $users
        ], 200);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:4',
            ]);

            // Return validation errors as JSON response if validation fails
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
            ]);

            return response()->json([
                'data' =>  $user,
                'success' => true,
                'statusCode' => 200,
                'message' => 'User created successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while create.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Show a specific user
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    // Update a specific user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'password' => 'nullable|string|min:8'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->name = $request->input('name');
            $user->email = $request->input('email');

            if ($request->input('password')) {
                $user->password = bcrypt($request->input('password'));
            }

            $user->save();

            return response()->json(['message' => 'User updated successfully', 'data' => $user]);
        } catch (Exception $e) {
            return response()->json(['error' => 'User not found or update failed'], 404);
        }
    }

    // Delete a specific user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->delete();

            return response()->json(['message' => 'User deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'User not found or deletion failed'], 404);
        }
    }
}
