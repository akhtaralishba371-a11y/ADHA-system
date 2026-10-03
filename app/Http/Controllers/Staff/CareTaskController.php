<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCareTaskRequest;
use App\Http\Requests\UpdateCareTaskRequest;
use App\Models\CareTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(CareTask::query()
            ->with(['patient:id,public_id,first_name,last_name', 'assignee:id,name'])
            ->when($request->user()->isClinician(), fn ($query) => $query->where('assigned_user_id', $request->user()->id))
            ->orderBy('due_at')
            ->paginate(50));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCareTaskRequest $request): JsonResponse
    {
        $task = CareTask::query()->create($request->validated());

        return response()->json($task->load(['patient:id,public_id,first_name,last_name', 'assignee:id,name']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CareTask $task): JsonResponse
    {
        $this->ensureVisible($task);

        return response()->json($task->load(['patient', 'appointment', 'assignee:id,name']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCareTaskRequest $request, CareTask $task): JsonResponse
    {
        $this->ensureVisible($task);
        $data = $request->validated();
        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
        }
        if (($data['status'] ?? null) === 'open') {
            $data['completed_at'] = null;
        }
        $task->update($data);

        return response()->json($task->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CareTask $task): JsonResponse
    {
        abort_unless(request()->user()->managesClinicians(), 403);
        $task->delete();

        return response()->json([], 204);
    }

    private function ensureVisible(CareTask $careTask): void
    {
        if (request()->user()->isClinician() && $careTask->assigned_user_id !== request()->user()->id) {
            abort(404);
        }
    }
}
