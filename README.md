# Dynamic CLI (Skeleton)

A starter package for a dynamic command-line interface to generate CRUD resources.

This repository currently contains only the minimal structure to begin development.

## Structure
- composer.json: Package metadata and autoloading
- src/DynamicCliServiceProvider.php: Laravel service provider
- config/dynamic-cli.php: Base configuration (skeleton)

## Next steps
- Implement console commands under `src/Console` (e.g., `DynamicCrudMakeCommand`)
- Add stub files under `stubs/` for models, controllers, requests, resources, and migrations
- Wire the commands in the service provider's `$this->commands([...])` within the `boot()` method

## Installation (local path repository)
- Ensure your main app's composer is aware of the `packages` directory (if using path repositories)
- Run `composer dump-autoload`
- Optionally publish the config: `php artisan vendor:publish --tag=dynamic-cli-config`
