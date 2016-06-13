#!/usr/bin/env php
<?php

use MediaWiki\Bot\CommandManager;
use MediaWiki\Bot\ProjectManager;
use MediaWiki\Storage\FileStore;
use MediaWiki\HttpClient\GuzzleHttpClient;
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
$storage = new FileStore(__DIR__.'/storage/cache');

$projectManager = new ProjectManager($client, $storage, __DIR__.'/projects');

$commandManager = new CommandManager($storage, __DIR__.'/scripts');

$project = $projectManager->loadProject($projectName);

$application = new Application();

$commands = $commandManager->getCommandsList();

foreach ($commands as $command) {
    $instance = $commandManager->getCommand($command, $project);

    $instance->setProjectManager($projectManager);

    $application->add($instance);
}

$application->run();