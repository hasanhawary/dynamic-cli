
Brief explanation: a focused, powerful README template for packages/dynamic-cli. Replace placeholders and examples with real command names, config keys, and code snippets from the package.
# packages/dynamic-cli

> Short description: a flexible CLI utility for dynamic tasks and automation within the project.

Status: WIP | Stable | Beta (replace with actual status)

---

## Table of contents

- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Commands](#commands)
- [Examples](#examples)
- [Development](#development)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Maintainers](#maintainers)

---

## About

`packages/dynamic-cli` provides a small, composable command-line toolset to run dynamic tasks, scaffolding, and automation scripts used by this project. It is intended to be embedded in the monorepo and re-used by other packages.

Key goals:
- Simple developer ergonomics for repetitive tasks
- Extensible command definitions
- Consistent configuration and logging
- Cross-platform support (Windows, macOS, Linux)

---

## Features

- Register commands dynamically from configuration
- Support for plugins/extensions
- JSON/YAML input and output
- Dry-run mode and verbose logging
- Integration points for PHP and Node tooling (Composer, Yarn/NPM)

---

## Requirements

- PHP >= 8.x
- Composer
- Node.js >= 16.x (if JS helpers included)
- Yarn or NPM (optional)
- Works on Windows (tested), macOS, Linux

---

## Installation

Install the package in the monorepo (example using Composer if it is a PHP package):

```bash
composer require --dev vendor/dynamic-cli
If this package exposes a global binary or local script, add it to your composer.json scripts or use the provided binary:
# run via vendor bin (example)
vendor/bin/dynamic-cli --help
If the project uses a local package path, ensure packages/dynamic-cli is autoloaded by your repo configuration.
<hr></hr>
Quick start
Publish configuration (if applicable):
php artisan vendor:publish --tag=dynamic-cli-config
# or copy `packages/dynamic-cli/config/dynamic-cli.php` to `config/` manually
Run help to see available commands:
php artisan dynamic-cli:help
# or
vendor/bin/dynamic-cli --help
Run a command:
php artisan dynamic-cli:run example --env=local --dry-run
Replace dynamic-cli:run and flags with the package's real command names.
<hr></hr>
Configuration
Configuration file (example path: config/dynamic-cli.php):
commands — list of enabled commands and their config
paths — base paths used by commands (e.g. packages/, resources/)
logging — log level and file
plugins — list of plugin classes or packages to load
Example snippet (illustrative):
return [
    'commands' => [
        'scaffold' => [
            'enabled' => true,
            'template_path' => base_path('packages/dynamic-cli/templates'),
        ],
    ],
    'logging' => [
        'level' => env('DYNAMIC_CLI_LOG', 'info'),
    ],
];
<hr></hr>
Commands
Document the commands your package exposes. For each command provide:
Command signature
Short description
Options and arguments
Examples
Example format:
dynamic-cli:scaffold {name} {--force} — Scaffold a module.
Arguments:
name — name of the module to create
Options:
--force — overwrite existing files
dynamic-cli:run {task} — Run a named task from config.
Options:
--env — environment (local/staging/production)
--dry-run — simulate without writing
(Replace with your actual commands.)
<hr></hr>
Examples
Scaffold a module:
php artisan dynamic-cli:scaffold Blog --force
Run a preconfigured task:
php artisan dynamic-cli:run sync-data --env=local
Use JSON input:
echo '{"id":123,"name":"test"}' | vendor/bin/dynamic-cli import --format=json
<hr></hr>
Development
To set up a dev environment for working on packages/dynamic-cli:
Install PHP dependencies:
composer install
Install JS dependencies (if applicable):
yarn install
# or
npm install
Generate autoload files:
composer dump-autoload
Run linters / formatters:
# PHP CS Fixer / PHPStan example
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
Run local commands while developing (use path-based autoload if necessary):
php -d memory_limit=-1 vendor/bin/phpunit --filter DynamicCliTest
<hr></hr>
Testing
Tests are located in packages/dynamic-cli/tests (adjust if different).
Run tests:
composer test
# or
vendor/bin/phpunit
If JS unit tests exist:
yarn test
# or
npm test
Include CI configuration (GitHub Actions, GitLab CI) snippets if present.
<hr></hr>
Contributing
Follow the repository code style
Add tests for new features and bug fixes
Open pull requests against main (or develop) with clear description and changelog entry
Use conventional commits for easier changelog generation
<hr></hr>
Troubleshooting
If a command does nothing, try --verbose or --dry-run to inspect actions without side effects.
Ensure config/dynamic-cli.php is present and loaded.
On Windows, check path separators and make sure vendor/bin scripts are executed with php vendor/bin/... if necessary.
<hr></hr>
Changelog
Track changes in CHANGELOG.md. Use semantic versioning.
<hr></hr>
License
Specify license and link (e.g. MIT). Place LICENSE in the package root.
<hr></hr>
Maintainers
@your-github-handle (replace with actual maintainers)
<hr></hr>
Append real examples, command signatures, and configuration keys from packages/dynamic-cli to replace placeholders and ensure accurate documentation.
