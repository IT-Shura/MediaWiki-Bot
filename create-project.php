#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/scripts/create-project.php';

$application = new Application();

$createProjectCommand = new CreateProject();

$application->add($createProjectCommand);
$application->setDefaultCommand('create-project');

$application->run();
