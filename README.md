Absolutely! I’ve written a **full, highly detailed, professional README** for your **Dynamic CLI CRUD** package, with all advanced features, CLI flow, schema examples, enums, translatable fields, relations, file uploads, and best Laravel package practices.

Here’s the **complete README** you can directly use:

---

# 🧠 Dynamic CLI CRUD Generator for Laravel

[![Version](https://img.shields.io/packagist/v/hasanhawary/dynamic-cli.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/dynamic-cli)
[![Total Downloads](https://img.shields.io/packagist/dt/hasanhawary/dynamic-cli.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/dynamic-cli)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/dynamic-cli.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/dynamic-cli)
[![License](https://img.shields.io/github/license/hasanhawary/dynamic-cli.svg?style=flat-square)](LICENSE)

**Dynamic CLI CRUD** is a **smart, interactive generator** for Laravel that creates **production-ready CRUD modules** (models, controllers, requests, resources, migrations, enums) with **automatic schema detection, relations, enums, translatable fields, and file handling**.

---

## ✨ Key Features

* 🧠 **Smart Schema Detection**: Detects field types, required fields, unique constraints, enums, file uploads, and relations automatically.
* 🎯 **Enum Handling**: Automatically generates Enum classes for fields like `status = enum[pending,approved,rejected]`.
* 🌍 **Translatable Fields**: Detects multilingual fields (`ar`, `en`, etc.) and stores them in JSON columns.
* 🔗 **Relations Awareness**: Detects `belongsTo` foreign keys (e.g., `country_id`) and sets up metadata for CRUD.
* 🖼️ **File Uploads**: Supports file fields (`file`, `image`, etc.) with category and allowed file types.
* ⚡ **Interactive CLI**: Step-by-step guidance for CRUD generation.
* 📦 **Full CRUD Generation**: Creates Model, Controller, Request, Resource, Migration, and Enum classes automatically.
* 💪 **Force Overwrite**: Re-generate existing files safely using `--force`.
* 🌐 **Optional Frontend Integration**: Specify a frontend path to scaffold integration-ready modules.
* 📝 **Detailed Schema Analysis**: Shows a complete mapping of all fields with metadata in CLI before generation.

---

## 📦 Installation

Install via Composer:

```bash
composer require hasanhawary/dynamic-cli --dev
```

> ✅ Auto-discovers the service provider. No manual registration needed.

---

## ⚡ Quick Start

Generate a CRUD module interactively:

```bash
php artisan cli:crud Product
```

Or provide the name directly and force overwrite if needed:

```bash
php artisan cli:crud Product --force
```

---

### 🔹 Example CLI Flow

```text
==============================================

   🧠 Dynamic CLI CRUD Generator
        Build smart CRUDs in seconds
      ⚡ Powered by Hasan Hawary

==============================================

👋 Welcome!

Enter group name (default: DataEntry) [DataEntry]:
> 

Custom table name? (press Enter for default) [products]:
> 

Do you have a custom JSON schema? (yes/no) [no]:
> yes

💡 Schema Reference Guide
-------------------------------------------------------------
Symbol-based field modifiers:
  * => required field (is_nullable = false)
  ^ => unique field (is_unique = true)
  enum[...] => enumeration field (is_enum = true)
Examples:
  '*price'  => 'float'
  '^email'  => 'string'
  'state'   => 'enum[draft,published,archived]'
  'name'    => ['ar' => '...', 'en' => '...'] // translatable field
  'photo'   => 'file'
  'country_id' => 1 // foreign key
-------------------------------------------------------------

Opening temporary file... Write your JSON schema and save/close.
```

---

### 🔹 JSON Schema Example

```json
{
  "*name": {
    "ar": "اسم المنتج",
    "en": "Product Name"
  },
  "description": {
    "ar": "وصف المنتج",
    "en": "Product Description"
  },
  "^phone": "string",
  "photo": "file",
  "status": "enum[pending,approved,rejected]",
  "country_id": 1
}
```

---

### 🔹 Final Schema Mapping (CLI Output)

```text
🧠 Analyzing schema...

📋 Final Schema Mapping:
 - name        → json       (🌍 translatable, 🚫 not null)
 - description → json       (🌍 translatable)
 - phone       → string     (🔑 unique)
 - photo       → file       (🖼️ file(jpg|jpeg|png|pdf|doc|docx))
 - status      → string     (🎯 enum[pending|approved|rejected])
 - country_id  → foreignId  (🔗 relation(Country))
```

---

## 🏗 CRUD File Generation

After confirmation, the generator creates:

| File Type  | Path Example                                                      |
| ---------- | ----------------------------------------------------------------- |
| Enum       | `app/Enum/DataEntry/StatusEnum.php`                               |
| Model      | `app/Models/Product.php`                                          |
| Controller | `app/Http/Controllers/API/DataEntry/ProductController.php`        |
| Request    | `app/Http/Requests/DataEntry/ProductRequest.php`                  |
| Resource   | `app/Http/Resources/DataEntry/ProductResource.php`                |
| Migration  | `database/migrations/2025_10_22_172830_create_products_table.php` |

> Next steps:

```bash
php artisan migrate
```

---

## 🔹 Advanced Features

### Enum Handling 🎯

* Automatically generates Enum classes for any field defined as `enum[...]`.
* Enum classes include a static `resolve($value)` method for easy mapping.

```php
$status = StatusEnum::resolve('pending'); // returns 'pending'
```

---

### Translatable Fields 🌍

* Detects JSON structures with language keys (`ar`, `en`).
* Stores translations in **JSON column** for multilingual support.

```json
"*name": { "ar": "اسم المنتج", "en": "Product Name" }
```

---

### Relations Detection 🔗

* Detects fields ending with `_id` or typed as `foreignId`.
* Prepares metadata for `belongsTo` relationships automatically.

```json
"country_id": 1
```

* Generates relation info for controller, resource, and request.

---

### File Uploads 🖼️

* Supports file fields (`file`, `image`, `pdf`, `docx`).
* Detects category and allowed file types.
* Integrates with Laravel file handling in requests.

```json
"photo": "file"
```

---

### CLI Options & Metadata

* **Force**: `--force` to overwrite existing files.
* **Group**: Organize CRUD modules by logical group (e.g., DataEntry).
* **Table Name**: Auto-generated from model name but customizable.
* **Route**: Default `api`, can be extended to `web`.

---

## 🔧 Optional Frontend Integration

The CLI can scaffold integration-ready modules for your frontend:

```text
Would you like to integrate this module with a frontend project? (yes/no)
> yes

Please specify the absolute path to your frontend project:
> /path/to/frontend
```

---

## 🤝 Contribution

1. Fork the repository
2. Create a feature branch:

```bash
git checkout -b feature/my-feature
```

3. Commit your changes:

```bash
git commit -am "Add new feature"
```

4. Push the branch:

```bash
git push origin feature/my-feature
```

5. Open a pull request

---

## 📦 Version Support

* **PHP**: 8.1 – 8.3
* **Laravel**: 10.x

---

## 📜 License

MIT © Hasan Hawary

---

## 🔗 Links

* GitHub: [https://github.com/hasanhawary/dynamic-cli](https://github.com/hasanhawary/dynamic-cli)
* Packagist: [https://packagist.org/packages/hasanhawary/dynamic-cli](https://packagist.org/packages/hasanhawary/dynamic-cli)

---