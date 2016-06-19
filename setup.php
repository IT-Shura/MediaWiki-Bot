#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/scripts/setup.php';

$application = new Application();

$setupCommand = new Setup();

$application->add($setupCommand);
$application->setDefaultCommand('setup');

$application->run();