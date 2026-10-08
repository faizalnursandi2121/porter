<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $label
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const EOS = 'EOS';

    public const SUPERVISI = 'SUPERVISI';

    public const HR = 'HR';

    public const ADMINISTRATOR = 'ADMINISTRATOR';

    protected $fillable = ['code', 'label'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
