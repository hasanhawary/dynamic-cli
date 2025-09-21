<?php

return [
    // Package configuration for Dynamic CLI

    // Default namespace where generated classes will be placed
    'namespace' => 'App\\Dynamic',

    // Stub paths to be used for generation
    'stubs' => [
        'model' => __DIR__ . '/../stubs/model.stub',
        'controller' => __DIR__ . '/../stubs/controller.stub',
        'migration' => __DIR__ . '/../stubs/migration.stub',
        'request' => __DIR__ . '/../stubs/request.stub',
        'resource' => __DIR__ . '/../stubs/resource.stub',
        'enum' => __DIR__ . '/../stubs/enum.stub',
        'seeder' => __DIR__ . '/../stubs/seeder.stub',
    ],

    // Default options for generators (to be extended later)
    'options' => [
        'force' => false,
    ],
];
