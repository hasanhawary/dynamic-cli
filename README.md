Got it! Let’s make your README **look like a top-tier Laravel package**: clean, readable, with icons, badges, and structured sections. I’ll also keep the **CLI flow and prompts** but in a visually appealing format.

Here’s a polished version:

````markdown
# 🧠 Dynamic CLI CRUD Generator for Laravel

[![Latest Version](https://img.shields.io/github/release/HasanHawary/dynamic-cli.svg?style=flat-square)](https://github.com/hasanhawary/dynamic-cli/releases)
[![License](https://img.shields.io/github/license/HasanHawary/dynamic-cli.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/dynamic-cli.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/dynamic-cli)

⚡ **Build smart CRUDs in seconds with interactive CLI and schema detection**.  

This package allows you to generate **models, controllers, requests, resources, migrations, enums, and more** interactively, saving you hours of boilerplate coding.

---

## ✨ Features

- ⚡ Interactive CLI for generating full CRUD  
- 🧠 Smart schema detection with metadata:
  - Translatable fields (`ar`, `en`, etc.) 🌍  
  - Enums 🎯  
  - File uploads 🖼️  
  - Relations 🔗  
- ✅ JSON schema support for custom definitions  
- 💪 Force overwrite existing files (`--force`)  
- 🖥️ Cross-platform system editor support  

---

## 🛠 Requirements

- PHP >= 8.1  
- Laravel >= 10.x  
- Composer  

---

## ⚙️ Installation

```bash
composer require hasanhawary/dynamic-cli --dev
````

---

## 🚀 Usage

```bash
php artisan cli:crud {name?} {--force}
```

* **name**: Optional model/base name (e.g., `Product`)
* **--force**: Overwrite existing files

### CLI Flow Example

```text
==============================================

   🧠 Dynamic CLI CRUD Generator
        Build smart CRUDs in seconds
      ⚡ Powered by Hassan Elhawary

==============================================

👋 Welcome to the Dynamic CRUD Generator!

Enter group name (default: DataEntry) [DataEntry]:
> 

Custom table name? (press Enter for default) [products]:
> 

Do you have a custom JSON schema? (yes/no) [no]:
> yes
```

### Schema Reference Guide

```text
Symbol-based field modifiers used during meta parsing:
-------------------------------------------------------------
  * => required field (not nullable)
  ^ => unique field
  enum[...] => enumeration

Examples:
  '*price'  => 'float'       // required float
  '^email'  => 'string'      // unique string
  'state'   => 'enum[draft,published,archived]'
-------------------------------------------------------------
Additional Field Guidelines:
-------------------------------------------------------------
  'name' => ['ar' => '...', 'en' => '...']  // Translatable fields
  'photo' => 'file'                         // File uploads
  'country_id' => 1                         // Foreign keys
-------------------------------------------------------------
```

After writing or editing the JSON schema, the CLI analyzes it:

```text
🧠 Analyzing schema...

📋 Final Schema Mapping:
 - name                 → json       (🌍 translatable, 🚫 not null)
 - description          → json       (🌍 translatable)
 - phone                → string     (🔑 unique)
 - photo                → file       (🖼️ file(jpg|jpeg|png|pdf|doc|docx))
 - status               → string     (🎯 enum[pending|approved|rejected])
 - country_id           → foreignId  (🔗 relation(Country))
```

### Generate CRUD

```text
Do you want to continue and generate CRUD files? (yes/no) [yes]:
> yes

⚙️ Generating files...
Generating CRUD for Product (table: products)...
- Skipped enum (exists): app/Enum/DataEntry/StatusEnum.php
- Skipped model (exists): app/Models/Product.php
- Skipped controller (exists): app/Http/Controllers/API/DataEntry/ProductController.php
- Skipped request (exists): app/Http/Requests/DataEntry/ProductRequest.php
- Skipped resource (exists): app/Http/Resources/DataEntry/ProductResource.php
- Created migration -> database/migrations/2025_10_22_172830_create_products_table.php

Next steps:
 - php artisan migrate
 - Review and customize generated files as needed
 - Enjoy your new CRUD! 🚀
```

---

## 📄 JSON Schema Example

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

**Field Symbols**:

| Symbol      | Meaning        |
| ----------- | -------------- |
| `*`         | Required field |
| `^`         | Unique field   |
| `enum[...]` | Enumeration    |

---

## 🏁 Next Steps After Generation

1. Run migrations:

```bash
php artisan migrate
```

2. Review generated files (controller, request, resource)
3. Customize as needed
4. Enjoy your new CRUD! 🚀

---

## 🤝 Contribution

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to branch (`git push origin feature/my-feature`)
5. Open a pull request

---

## 📜 License

MIT License © Hassan Elhawary

---

## 🔗 Links

* GitHub: [https://github.com/hasanhawary/dynamic-cli](https://github.com/hasanhawary/dynamic-cli)
* Packagist: [https://packagist.org/packages/hasanhawary/dynamic-cli](https://packagist.org/packages/hasanhawary/dynamic-cli)

```

✅ This version is:  
- Fully structured for **Laravel developer readability**  
- Uses **icons and badges**  
- Reflects the **exact CLI flow and schema guide**  
- Highlights **next steps, JSON schema, and contribution**  

---

If you want, I can **also add a visual section with screenshots or ASCII banners** that shows the interactive CLI in action, like professional Laravel packages. This makes the README **look premium**.  

Do you want me to do that next?
```
