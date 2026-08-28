<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\ServiceBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductionSqlCatalogSeeder extends Seeder
{
    private const DUMP = 'C:\\Users\\Tenstrings Music Ins\\Downloads\\u519226541_iceland.sql';

    public function run(): void
    {
        if (! File::exists(self::DUMP)) {
            throw new \RuntimeException('Production SQL dump was not found: '.self::DUMP);
        }

        $sql = File::get(self::DUMP);
        $rooms = $this->records($sql, 'room');
        $services = $this->records($sql, 'services');

        if ($rooms === []) {
            throw new \RuntimeException('No room records were found in the production SQL dump.');
        }

        Room::query()->delete();
        foreach ($rooms as $row) {
            Room::query()->create([
                'room_name' => trim($row['room_name']),
                'room_category' => trim($row['room_category']),
                'room_price' => (float) $row['room_price'],
                'room_image' => $this->assetPath($row['room_image']),
                'is_booked' => in_array($row['is_booked'], ['no', 'booked', 'rejected', 'expired'], true) ? $row['is_booked'] : 'no',
                'customer_name' => $row['customer_name'] ?: null,
                'email' => $row['email'] ?: null,
                'start_date' => $this->date($row['start_date']),
                'end_date' => $this->date($row['end_date']),
                'total_price' => (float) $row['total_price'],
                'rejection_reason' => $row['rejection_reason'] ?: null,
                'mailsent' => filter_var($row['mailsent'], FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        ServiceBooking::query()->delete();
        foreach ($services as $row) {
            ServiceBooking::query()->create([
                'service_name' => trim($row['service_name']),
                'service_category' => trim($row['service_category']),
                'service_price' => (float) $row['service_price'],
                'service_image' => $this->assetPath($row['service_image']),
                'customers_name' => $row['customers_name'] ?: null,
                'customers_email' => $row['customers_email'] ?: null,
                'signin' => $this->date($row['signin']),
                'signout' => $this->date($row['signout']),
                'is_booked' => in_array($row['is_booked'], ['no', 'booked', 'expired'], true) ? $row['is_booked'] : 'no',
                'no_of_people' => (int) $row['no_of_people'],
                'mailsent' => filter_var($row['mailsent'], FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $this->command?->info(sprintf('Imported %d rooms and %d services from the production SQL dump.', count($rooms), count($services)));
    }

    private function records(string $sql, string $table): array
    {
        if (! preg_match('/INSERT INTO `'.preg_quote($table, '/').'` \(([^)]+)\) VALUES\s*(.*?);/s', $sql, $match)) {
            return [];
        }
        $columns = array_map(fn (string $column) => trim($column, " `"), explode(',', $match[1]));
        preg_match_all("/\\((?:[^'()]++|'(?:\\\\.|[^'\\\\])*')*\\)/s", $match[2], $tuples);

        return array_map(function (string $tuple) use ($columns): array {
            $values = str_getcsv(substr($tuple, 1, -1), ',', "'", '\\');
            $values = array_map(function ($value) {
                $value = is_string($value) ? trim($value) : $value;
                return strtoupper((string) $value) === 'NULL' ? null : $value;
            }, $values);
            return array_combine($columns, $values);
        }, $tuples[0] ?? []);
    }

    private function date(?string $date): ?string
    {
        return ! $date || str_starts_with($date, '0000-00-00') ? null : $date;
    }

    private function assetPath(?string $path): ?string
    {
        $path = trim((string) $path);
        return $path === '' ? null : '/legacy-images/'.preg_replace('#^static/images/#', '', $path);
    }
}