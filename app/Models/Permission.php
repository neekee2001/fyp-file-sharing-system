<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    use HasFactory;

    public function sharedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class);
    }

    protected $fillable = [
        'name',
        'description',
    ];
}
