<?php

namespace Model;

use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Crud extends Model
{
    use HasTranslations, CreatedByObserver, SoftDeletes;

    public bool $inPermission = true;
    public array $specialOperations = ['restore', 'force-delete'];
    public array $translatable = ['name'];
    protected $fillable = ['name', 'description', 'created_by'];

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
