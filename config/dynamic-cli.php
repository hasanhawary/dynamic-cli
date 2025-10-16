<?php

return [
    // Stub paths to be used for generation
    'base' => [
        'model' => __DIR__ . '/../stubs/model.stub',
        'controller' => __DIR__ . '/../stubs/controller.stub',
        'migration' => __DIR__ . '/../stubs/migration.stub',
        'request' => __DIR__ . '/../stubs/request.stub',
        'resource' => __DIR__ . '/../stubs/resource.stub',
        'enum' => __DIR__ . '/../stubs/enum.stub',
        'seeder' => __DIR__ . '/../stubs/seeder.stub',
        'policy' => __DIR__ . '/../stubs/policy.stub',
    ],

    // Stub paths to be used for generation
    'namespaces' => [
        'model' => 'App\Models',
        'controller' => 'App\Http\Controllers\API',
        'migration' => 'database\migrations',
        'request' => 'App\Http\Requests',
        'resource' => 'App\Http\Resources',
        'enum' => 'App\Enum',
        'seeder' => 'Database\Seeders'
    ],

    'path' => [
        'model' => 'app/Models',
        'controller' => 'app/Http/Controllers/API',
        'migration' => 'database/migrations',
        'request' => 'app/Http/Requests',
        'resource' => 'app/Http/Resources',
        'enum' => 'app/Enum',
        'seeder' => 'database/seeders',
        'route' => 'routes'
    ],

    // Default options for generators (to be extended later)
    'options' => [
        'force' => false,
    ],
];
