<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MealController extends Controller
{
    /** Tenant-scoped catalogue. Waitress sees available only; manager can include unavailable. */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'department' => ['nullable', 'string'],
            'include_unavailable' => ['nullable'],
        ]);

        $includeUnavailable = filter_var($data['include_unavailable'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && $request->user()->hasAnyRole(['Manager', 'Super Admin', 'Admin']);

        $meals = Meal::query()
            ->when($data['department'] ?? null, fn ($q, $dept) => $q->where('department', $dept))
            ->when(! $includeUnavailable, fn ($q) => $q->where('is_available', true))
            ->orderBy('department')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $meals->map(fn (Meal $m) => $this->present($m))->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'department' => ['required', 'string', Rule::in(Meal::DEPARTMENTS)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'price_naira' => ['required', 'numeric', 'min:0'],
            'prep_time_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $this->assertUniqueName($data['department'], $data['name']);

        $meal = Meal::query()->create([
            'department' => $data['department'],
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'price' => (int) round($data['price_naira'] * 100),
            'prep_time_minutes' => $data['prep_time_minutes'],
            'is_available' => $data['is_available'] ?? true,
        ]);

        return response()->json(['data' => $this->present($meal)], 201);
    }

    public function update(Request $request, Meal $meal): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price_naira' => ['sometimes', 'numeric', 'min:0'],
            'prep_time_minutes' => ['sometimes', 'integer', 'min:1', 'max:600'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name']) && $data['name'] !== $meal->name) {
            $this->assertUniqueName($meal->department, $data['name'], $meal->id);
        }

        $update = $data;
        if (array_key_exists('price_naira', $data)) {
            $update['price'] = (int) round($data['price_naira'] * 100);
            unset($update['price_naira']);
        }

        $meal->update($update);

        return response()->json(['data' => $this->present($meal->fresh())]);
    }

    /**
     * Manager uploads a product image as base64 (works identically on native and
     * web). Stored on the public disk; the response carries a request-host URL.
     */
    public function uploadImage(Request $request, Meal $meal): JsonResponse
    {
        $data = $request->validate([
            'image_base64' => ['required', 'string'],
            'mime' => ['nullable', 'string', 'in:image/jpeg,image/png,image/webp'],
        ]);

        $raw = $data['image_base64'];
        if (str_contains($raw, ',')) {
            $raw = substr($raw, strpos($raw, ',') + 1); // strip data: prefix
        }
        $binary = base64_decode($raw, true);

        if ($binary === false || strlen($binary) === 0) {
            throw ValidationException::withMessages(['image_base64' => 'Invalid image data.']);
        }
        if (strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['image_base64' => 'Image is too large (max 5MB).']);
        }

        $ext = match ($data['mime'] ?? 'image/jpeg') {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        // Remove a previous image so we don't orphan files.
        if ($meal->image_path && Storage::disk('public')->exists($meal->image_path)) {
            Storage::disk('public')->delete($meal->image_path);
        }

        $path = 'meals/'.$meal->tenant_id.'/'.Str::uuid().'.'.$ext;
        Storage::disk('public')->put($path, $binary);

        $meal->update(['image_path' => $path]);

        return response()->json(['data' => $this->present($meal->fresh())]);
    }

    private function assertUniqueName(string $department, string $name, ?int $ignoreId = null): void
    {
        $exists = Meal::query()
            ->where('department', $department)
            ->where('name', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => "A meal named \"{$name}\" already exists in this station.",
            ]);
        }
    }

    private function present(Meal $meal): array
    {
        return [
            'id' => $meal->id,
            'department' => $meal->department,
            'name' => $meal->name,
            'category' => $meal->category,
            'price' => (int) $meal->price,
            'prep_time_minutes' => (int) $meal->prep_time_minutes,
            'is_available' => (bool) $meal->is_available,
            'image_url' => $meal->imageUrl(),
        ];
    }
}
