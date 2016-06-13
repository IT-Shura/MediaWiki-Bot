<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;
use MediaWiki\Helpers;

class MakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates new command for the bot';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->ask('Please enter the command name');
        $description = $this->ask('Please enter the command description');
        
        $className = Helpers\pascal_case($name);

        $stub = file_get_contents(__DIR__.'/../stubs/command.php');

        $search = ['DummyCommand', 'dummy-command', 'The command description'];
        $replace = [$className, $name, $description];

        $stub = str_replace($search, $replace, $stub);

        $filename = sprintf('%s/%s.php', __DIR__, $name);

        if (file_exists($filename)) {
            $this->error(sprintf('Command with name "%s" already exists.', $name));

            exit;
        }

        file_put_contents($filename, $stub);

        $this->info('Command created successfully.');
    }
}
