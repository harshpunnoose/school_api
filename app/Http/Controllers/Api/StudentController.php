<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /**
     * GET /api/students
     * List students with search, sort & pagination
     */
    public function index(Request $request)
    {
        $query = Student::query();

        // 🔍 Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // ↕ Sorting
        if ($request->filled('sort_by')) {
            $query->orderBy(
                $request->sort_by,
                $request->get('order', 'asc')
            );
        } else {
            $query->orderBy('first_name');
        }

        $students = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $students
        ], 200);
    }

    /**
     * POST /api/students
     * Create new student
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|unique:students,username',
            'email' => 'nullable|email|unique:students,email',
            'phone' => 'nullable|string|max:20',
            'alt_phone' => 'nullable|string|max:20',
            'parent' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'user_img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'password' => 'nullable|string|min:6'
        ]);

        // 1️⃣ Create user account for login
        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'] ?? $validated['username'].'@example.com',
            'password' => Hash::make($validated['password'] ?? 'password'), // default password
            'role_id' => 2, // student role
        ]);

        // 2️⃣ Handle image
        if ($request->hasFile('user_img')) {
            $validated['user_img'] = $request->file('user_img')->store('students', 'public');
        }

        // 3️⃣ Create student
        $student = Student::create([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'alt_phone' => $validated['alt_phone'] ?? null,
            'parent' => $validated['parent'] ?? null,
            'status' => $validated['status'],
            'user_img' => $validated['user_img'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $student
        ], 201);
    }

    /**
     * GET /api/students/{id}
     * Show student details
     */
    public function show($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ], 200);
    }

    /**
     * PUT /api/students/{id}
     * Update student
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'sometimes|required|string|max:100',
            'username'   => [
                'sometimes',
                'required',
                Rule::unique('students')->ignore($student->id)
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('students')->ignore($student->id)
            ],
            'phone'     => 'nullable|string|max:20',
            'alt_phone' => 'nullable|string|max:20',
            'parent'    => 'nullable|string|max:100',
            'status'    => 'in:active,inactive',
            'user_img'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'password'  => 'nullable|string|min:6', // optional password change
        ]);

        // Handle image upload
        if ($request->hasFile('user_img')) {
            if ($student->user_img) {
                Storage::disk('public')->delete($student->user_img);
            }

            $validated['user_img'] = $request->file('user_img')->store('students', 'public');
        }

        // Update student
        $student->update($validated);

        // 🔹 Update linked User for login
        $user = $student->user;
        if ($user) {
            $userData = [];
            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $userData['name'] = ($validated['first_name'] ?? $student->first_name)
                                . ' '
                                . ($validated['last_name'] ?? $student->last_name);
            }
            if (isset($validated['email'])) {
                $userData['email'] = $validated['email'];
            }
            if (isset($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            if (!empty($userData)) {
                $user->update($userData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $student->fresh()
        ], 200);
    }

    /**
     * DELETE /api/students/{id}
     * Delete student
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        // 🗑 Delete image
        // if ($student->user_img) {
        //     Storage::disk('public')->delete($student->user_img);
        // }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ], 200);
    }
}
