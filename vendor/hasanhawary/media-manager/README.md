# Media Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Powerful, developer-friendly media management for Laravel.  
Store, organize, and serve files from **any source** (UploadedFile, base64, raw content, local path, remote URL) to **any disk** (local, public, S3, etc.).  
Includes smart naming strategies, safe replacement, trash-based deletion, rich metadata, and convenient URL helpers.

---

## 🚀 Features

- Accepts multiple sources: UploadedFile, base64, raw content, local path, remote URL
- Works with any Laravel filesystem disk (`local`, `public`, `s3`, ...)
- Smart filenames: original, UUID, hash, timestamp, or custom callback
- **Safe replace**: delete old file only after successful store
- **Safe delete**: move to trash instead of permanent deletion
- URL helpers: absolute, temporary, signed URLs
- Rich metadata: size, mime, extension, dirname, modified time, hash, dimensions
- Facade (`Media`) for zero-boilerplate usage

---

## 📦 Installation

```bash
composer require hasanhawary/media-manager
```

The package auto-discovers the service provider and facade.  
Facade alias: `Media` → `HasanHawary\MediaManager\Facades\Media`.

Ensure your disks are configured in `config/filesystems.php`.

---

## ⚡ Quick Start

```php
use HasanHawary\MediaManager\Facades\Media;

// Store file
$path = Media::fromFile(request()->file('avatar'))
    ->to('uploads/avatars')
    ->on('public')
    ->generateName('uuid')
    ->store();

// URLs
$url    = Media::on('public')->url($path);
$tmp    = Media::on('s3')->temporaryUrl($path, 5);
$signed = Media::on('s3')->signedUrl($path, now()->addMinutes(10));
```

---

## 📂 Supported Sources

```php
Media::fromFile($file);                  // UploadedFile
Media::fromBase64($data);                // Base64 / Data URL
Media::fromContent('raw text');          // Raw content
Media::fromLocalPath('/tmp/file.pdf');   // Local file path
Media::fromUrl('https://img.com/x.png'); // Remote URL

// Auto-detect (works with any of the above)
Media::from($mixedInput)->store(); 
// $mixedInput can be UploadedFile, base64 string, content, path, or URL

---

## 🔑 Naming Strategies

```php
->keepOriginalName()
->generateName('uuid')       // default
->generateName('hash')
->generateName('timestamp')
->withName('custom-name.png')
->fallbackExtension('txt')   // when no extension detected
```

---

## 🛡 Safe Replace & Delete

```php
// Safe replace (delete old only if new saved)
$new = Media::fromFile(request()->file('avatar'))
    ->to('uploads/avatars')
    ->replace('uploads/avatars/old.png')
    ->store();

// Safe delete (moves to trash)
Media::safeDelete('uploads/avatars/old.png');
```

---

## 🔗 URL Helpers

```php
Media::on('public')->url($path);                  // Absolute URL
Media::on('s3')->temporaryUrl($path, 10);         // Time-limited URL
Media::on('s3')->signedUrl($path, now()->addDay());
```

---

## 📊 Metadata

```php
$meta = Media::on('public')->meta('uploads/docs/report.pdf');

$meta->path();        // string|null
$meta->url();         // string|null
$meta->size();        // int (bytes)
$meta->mime();        // string (mime type)
$meta->extension();   // string|null
$meta->basename();    // file name with extension
$meta->filename();    // file name without extension
$meta->dirname();     // directory path
$meta->lastModified();// timestamp
$meta->hash();        // md5 hash of contents
$meta->dimensions();  // [width, height] for images
$meta->toArray();     // full metadata as array
```

---

## 🌐 Controller Example

```php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use HasanHawary\MediaManager\Facades\Media;

Route::post('/upload-avatar', function (Request $request) {
    $request->validate(['avatar' => 'required|image|max:2048']);

    $path = Media::fromFile($request->file('avatar'))->to('uploads/avatars')->store();

    return response()->json([
        'path' => $path,
        'url'  => Media::url($path),
    ]);
});
```

---

## ✅ Version Support

- **PHP**: 8.0 – 8.5
- **Laravel**: 8 – 12

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
