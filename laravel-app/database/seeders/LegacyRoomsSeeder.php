<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LegacyRoomsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = $this->readFromLegacyDatabase();

        if (empty($rows)) {
            $rows = $this->readFallbackFromLegacyRoomsPhp();
        }

        foreach ($rows as $row) {
            $roomName = trim((string) ($row['room_name'] ?? ''));
            if ($roomName === '') {
                continue;
            }

            Room::query()->updateOrCreate(
                ['room_name' => $roomName],
                [
                    'room_category' => $row['room_category'] ?? null,
                    'room_price' => (float) ($row['room_price'] ?? 0),
                    'room_image' => $row['room_image'] ?? null,
                    'is_booked' => $row['is_booked'] ?? 'no',
                ],
            );
        }
    }

    /**
     * Best source: legacy DB room table, accessed via env-driven credentials.
     */
    private function readFromLegacyDatabase(): array
    {
        $cfg = (array) config('resort.legacy_db', []);

        $host = (string) ($cfg['host'] ?? '');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');

        if ($host === '' || $database === '' || $username === '') {
            return [];
        }

        try {
            $pdo = new \PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $host,
                    (int) ($cfg['port'] ?? 3306),
                    $database,
                ),
                $username,
                (string) ($cfg['password'] ?? ''),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
            );

            $stmt = $pdo->query('SELECT room_name, room_category, room_price, room_image, is_booked FROM room');
            $rows = $stmt?->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return array_values(array_filter($rows, fn (array $row) => ! empty($row['room_name'])));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Fallback source: parse room categories from legacy rooms.php.
     * Pricing cannot be reliably extracted from this file, so fallback values are 0.
     */
    private function readFallbackFromLegacyRoomsPhp(): array
    {
        $legacyPath = (string) config('resort.legacy_app_path', base_path('..'));
        $roomsPhp = $legacyPath . DIRECTORY_SEPARATOR . 'rooms.php';

        if (! File::exists($roomsPhp)) {
            return [];
        }

        $content = File::get($roomsPhp);

        preg_match_all("/\['([^']+)'\s*,\s*'no'\]/", $content, $matches);
        $categories = collect($matches[1] ?? [])
            ->map(fn (string $category) => trim($category))
            ->filter()
            ->unique()
            ->values();

        return $categories
            ->map(fn (string $category) => [
                'room_name' => Str::of($category)->replace(' ', '')->append(' Room')->toString(),
                'room_category' => $category,
                'room_price' => 0,
                'room_image' => null,
                'is_booked' => 'no',
            ])
            ->all();
    }
}
