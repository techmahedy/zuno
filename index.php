#!/usr/bin/env php
<?php
require_once __DIR__ . '../../../../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Zuno\Console\Commands\MakeMiddlewareCommand;

$console = new Application();
$console->add(new MakeMiddlewareCommand());

$console->run();
