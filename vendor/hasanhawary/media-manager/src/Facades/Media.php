<?php

namespace HasanHawary\MediaManager\Facades;

use HasanHawary\MediaManager\MediaManager;
use Illuminate\Support\Facades\Facade;

/**
 * Fluent static facade for MediaManager.
 *
 * Sources:
 * @method static MediaManager from(mixed $item)
 * @method static MediaManager fromBase64(string $base64)
 * @method static MediaManager fromUrl(string $url, bool $storedLocal = false)
 * @method static MediaManager fromLocalPath(string $path, bool $copy = true)
 * @method static MediaManager fromFile(\Illuminate\Http\UploadedFile $file)
 * @method static MediaManager fromContent(string $content)
 *
 * Options:
 * @method static MediaManager to(string $path)
 * @method static MediaManager on(?string $disk)
 * @method static MediaManager visibility(?string $visibility)
 * @method static MediaManager fallbackExtension(?string $extension)
 * @method static MediaManager keepOriginalName()
 * @method static MediaManager generateName(string $strategy = 'uuid')
 * @method static MediaManager withName(string|\Closure $nameOrCallback)
 *
 * Actions:
 * @method static string|array|null store()
 * @method static void delete(array|string|null $files = null)
 * @method static void safeDelete(array|string|null $files = null)
 * @method static array|string|null url(string|array|null $paths = null)
 * @method static array|string|null temporaryUrl(string|array|null $paths = null, int $minutes = 5)
 * @method static array|string|null signedUrl(string|array|null $paths = null, \DateTimeInterface $expiresAt = null)
 * @method static \HasanHawary\MediaManager\Support\MediaMeta meta(mixed $paths)
 */
class Media extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MediaManager::class;
    }
}