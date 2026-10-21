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
     * Spaces offered at no charge. Matched case-insensitively because the
     * category is a free-text field with suggestions, not a fixed enum.
     */
    public const COMPLIMENTARY_CATEGORY = 'Complimentary';

    public function isComplimentary(): bool
    {
        return strcasecmp(trim((string) $this->service_category), self::COMPLIMENTARY_CATEGORY) === 0;
    }

    /**
     * Whether this space carries no charge at all.
     *
     * Note the two conditions are NOT interchangeable, and the distinction
     * matters in Api\ServiceBookingController: a Complimentary space is
     * deliberately free, while a priced category sitting at 0.00 is almost
     * certainly a space whose price nobody filled in. The first should book
     * straight through; the second should be refused rather than quietly
     * taken for nothing.
     */
    public function isFree(): bool
    {
        return $this->isComplimentary() || (float) $this->service_price <= 0;
    }

    /**
     * How the price reads anywhere a human sees it.
     *
     * Formatted here, once, rather than in each of the admin table, the public
     * grid, the booking form and the report — those had drifted to four
     * different renderings of the same number, and "₦0.00" was meaningless on
     * a space that was never going to be charged for.
     */
    public function priceLabel(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return '₦' . number_format((float) $this->service_price, 2);
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
