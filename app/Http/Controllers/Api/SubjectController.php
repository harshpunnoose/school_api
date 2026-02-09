<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * GET /api/subjects
     */
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('name')->paginate(10),
        ]);
    }

    /**
     * POST /api/subjects
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:50|unique:subjects,code',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $subject = Subject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject,
        ], 201);
    }

    /**
     * GET /api/subjects/{id}
     */
    public function show($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    /**
     * PUT /api/subjects/{id}
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found',
            ], 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'code'        => [
                'sometimes',
                'required',
                Rule::unique('subjects')->ignore($subject->id),
            ],
            'description' => 'nullable|string',
            'status'      => 'in:active,inactive',
        ]);

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject->fresh(),
        ]);
    }

    /**
     * DELETE /api/subjects/{id}
     * Soft delete via status change
     */
    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found',
            ], 404);
        }

        $subject->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Subject deactivated successfully',
        ]);
    }
}
