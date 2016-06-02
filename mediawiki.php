#!/usr/bin/env php
<?php

use MediaWiki\Bot\CommandManager;
use MediaWiki\Bot\ProjectManager;
use MediaWiki\Storage\FileStore;
use MediaWiki\Api\HttpClient\GuzzleHttpClient;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

if (!file_exists(__DIR__.'/user-config.php')) {
    die('File "user-config.php" doest not exists.');
}

$config = require __DIR__.'/user-config.php';

$projectName = $config['project'];

$client = new GuzzleHttpClient();
$storage = new FileStore(__DIR__.'/storage');

$projectManager = new ProjectManager($client, $storage, __DIR__.'/projects');

$commandManager = new CommandManager($storage, __DIR__.'/scripts');

$project = $projectManager->loadProject($projectName);

$application = new Application();

$commands = scandir(__DIR__.'/scripts');

foreach ($commands as $command) {
    if (in_array($command, ['.', '..'])) {
        continue;
    }

    $instance = $commandManager->getCommand(basename($command, '.php'), $project);

    $instance->setProjectManager($projectManager);

    $application->add($instance);
}

$application->run();