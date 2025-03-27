<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotnev =  Dotenv::createImmutable(__DIR__);

$dotnev->load();
