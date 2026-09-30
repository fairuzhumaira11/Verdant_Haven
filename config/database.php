<?php
// Copy local values into environment variables or edit these for your XAMPP installation.
return [
    'host' => getenv('VH_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('VH_DB_PORT') ?: '3306',
    'database' => getenv('VH_DB_NAME') ?: 'verdant_haven',
    'user' => getenv('VH_DB_USER') ?: 'root',
    'password' => getenv('VH_DB_PASSWORD') ?: '',
];
