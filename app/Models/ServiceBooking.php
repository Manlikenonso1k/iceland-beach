<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceBooking extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'service_name',
        'service_category',
        'service_price',
        'service_image',
        'main_image',
        'gallery_images',
        'description',
        'capacity',
        'is_booked',
        'customers_name',
        'customers_email',
        'signin',
        'signout',
        'no_of_people',
        'mailsent',
    ];

    protected function casts(): array
    {
        return [
            'service_price' => 'decimal:2',
            'signin' => 'datetime',
            'signout' => 'datetime',
            'mailsent' => 'boolean',
            'gallery_images' => 'array',
        ];
    }

    /**
     * Public URL for the space's main photo.
     *
     * Prefers a Filament upload on the `public` disk; falls back to the legacy
     * `service_image` public path. Mirrors Room::displayImage().
     */
    public function displayImage(): ?string
    {
        if (filled($this->main_image)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->main_image);
        }

        $legacy = preg_replace('/(\\[rn]|\s)+$/', '', (string) $this->service_image) ?? '';

        return $legacy !== '' ? $legacy : null;
    }

    /** @return list<string> */
    public function galleryUrls(): array
    {
        $urls = [];

        if ($main = $this->displayImage()) {
            $urls[] = $main;
        }

        foreach ((array) ($this->gallery_images ?? []) as $path) {
            if (filled($path)) {
                $urls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        }

        return array_values(array_unique($urls));
    }
}
