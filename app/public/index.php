<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// The entry point of the HTTP API: a web server sends every request here.
require __DIR__ . '/../../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Request::capture());
