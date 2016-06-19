#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/scripts/create-command.php';

$application = new Application();

$createCommandCommand = new CreateCommand();

$application->add($createCommandCommand);
$application->setDefaultCommand('create-command');

$application->run();
