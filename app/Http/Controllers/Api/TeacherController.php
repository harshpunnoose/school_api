<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    /**
     * GET /api/teachers
     */
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        $teachers = $query->orderBy('first_name')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    /**
     * POST /api/teachers
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'username'   => 'required|string|unique:teachers,username',
            'email'      => 'nullable|email|unique:teachers,email',
            'phone'      => 'nullable|string|max:20',
            'skype_id'   => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive',
            'password'   => 'required|string|min:6',
            'user_img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 🔐 Create login user
        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'] ?? $validated['username'].'@example.com',
            'password' => Hash::make($validated['password']),
            'role_id' => 3, // teacher role
        ]);

        // Handle image upload
        if ($request->hasFile('user_img')) {
            $validated['user_img'] = $request->file('user_img')->store('teachers', 'public');
        }

        // 👩‍🏫 Create teacher profile
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'skype_id' => $validated['skype_id'] ?? null,
            'status' => $validated['status'],
            'user_img' => $validated['user_img'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully',
            'data' => $teacher
        ], 201);
    }

    /**
     * GET /api/teachers/{id}
     */
    public function show($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    /**
     * PUT /api/teachers/{id}
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'sometimes|required|string|max:100',
            'username'   => [
                'sometimes',
                'required',
                Rule::unique('teachers')->ignore($teacher->id)
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('teachers')->ignore($teacher->id)
            ],
            'phone'    => 'nullable|string|max:20',
            'skype_id' => 'nullable|string|max:100',
            'status'   => 'in:active,inactive',
            'password' => 'nullable|string|min:6',
            'user_img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('user_img')) {

            // delete old image
            if ($teacher->user_img) {
                Storage::disk('public')->delete($teacher->user_img);
            }

            $validated['user_img'] =
                $request->file('user_img')->store('teachers', 'public');
        }

        $teacher->update($validated);

        // 🔐 Update login user
        if ($teacher->user) {
            $userData = [];

            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $userData['name'] =
                    ($validated['first_name'] ?? $teacher->first_name).' '.
                    ($validated['last_name'] ?? $teacher->last_name);
            }

            if (isset($validated['email'])) {
                $userData['email'] = $validated['email'];
            }

            if (isset($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            if (!empty($userData)) {
                $teacher->user->update($userData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully',
            'data' => $teacher->fresh()
        ]);
    }

    /**
     * DELETE /api/teachers/{id} (Soft delete)
     */
    public function destroy($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully'
        ]);
    }
}
