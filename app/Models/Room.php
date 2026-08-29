<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Room extends Model
{
    use SoftDeletes;

    protected $table = 'room';

    protected $fillable = [
        'room_name',
        'room_category',
        'room_price',
        'room_image',
        'main_image',
        'gallery_images',
        'is_booked',
        'customer_name',
        'email',
        'start_date',
        'end_date',
        'total_price',
        'rejection_reason',
        'mailsent',
    ];

    protected function casts(): array
    {
        return [
            'room_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'mailsent' => 'boolean',
            'gallery_images' => 'array',
        ];
    }

    /**
     * Public URL for the room's main photo.
     *
     * Prefers an image uploaded through Filament (stored on the `public` disk);
     * falls back to the legacy `room_image` path, which is a plain public-folder
     * path like "/legacy-images/rooms/Gold Room 1.jpeg" and is served as-is.
     * Some legacy values carry trailing whitespace, hence the trim.
     */
    public function displayImage(): ?string
    {
        if (filled($this->main_image)) {
            return Storage::disk('public')->url($this->main_image);
        }

        return $this->cleanLegacyPath($this->room_image);
    }

    /**
     * Normalise a legacy image path.
     *
     * At least one row was imported with a literal "\r\n" on the end — the two
     * characters backslash-r, not a real carriage return — so plain trim() does
     * not remove it and the URL 404s. Strip both forms.
     */
    private function cleanLegacyPath(?string $path): ?string
    {
        $clean = preg_replace('/(\\\\[rn]|\s)+$/', '', (string) $path) ?? '';

        return $clean !== '' ? $clean : null;
    }

    /**
     * Every photo for the room, main first.
     *
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        $urls = [];

        if ($main = $this->displayImage()) {
            $urls[] = $main;
        }

        foreach ((array) ($this->gallery_images ?? []) as $path) {
            if (filled($path)) {
                $urls[] = Storage::disk('public')->url($path);
            }
        }

        return array_values(array_unique($urls));
    }
}
