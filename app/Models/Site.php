<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string|null $address
 * @property float $latitude
 * @property float $longitude
 * @property string $timezone
 * @property bool $is_active
 */
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    public const TIMEZONES = [
        'WIB' => 'Asia/Jakarta',
        'WITA' => 'Asia/Makassar',
        'WIT' => 'Asia/Jayapura',
    ];

    protected $fillable = ['name', 'address', 'latitude', 'longitude', 'timezone', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function connections(): HasMany
    {
        return $this->hasMany(SiteConnection::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function primaryConnection(): ?SiteConnection
    {
        return $this->connections()->where('kind', SiteConnection::PRIMARY)->first();
    }
}
