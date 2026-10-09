<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VenueTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Tenant-scoped tables. Any signed-in staff can list them (the waitress picks one
 * when ordering); only a manager can create, rename or retire them.
 */
class TableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $manage = filter_var($request->query('manage', false), FILTER_VALIDATE_BOOLEAN)
            && $request->user()->hasAnyRole(['Manager', 'Super Admin', 'Admin']);

        $tables = VenueTable::query()
            ->when(! $manage, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'zone', 'is_active']);

        return response()->json(['data' => $tables]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->assertUniqueName($data['name']);

        $table = VenueTable::query()->create([
            'name' => $data['name'],
            'zone' => $data['zone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => $table->only(['id', 'name', 'zone', 'is_active'])], 201);
    }

    public function update(Request $request, VenueTable $table): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'zone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name']) && $data['name'] !== $table->name) {
            $this->assertUniqueName($data['name'], $table->id);
        }

        $table->update($data);

        return response()->json(['data' => $table->only(['id', 'name', 'zone', 'is_active'])]);
    }

    private function assertUniqueName(string $name, ?int $ignoreId = null): void
    {
        $exists = VenueTable::query()
            ->where('name', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['name' => "A table named \"{$name}\" already exists."]);
        }
    }
}
