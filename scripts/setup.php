<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;

class Setup extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MediaWiki Bot setup script';

    public function getArguments()
    {
        return [];
    }

    public function getOptions()
    {
        return [];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->ask('Please enter the project name (example: my-project)');

        $stub = file_get_contents(__DIR__.'/../stubs/user-config.php');

        $search = ['name-of-the-project'];
        $replace = [$name];

        $stub = str_replace($search, $replace, $stub);

        file_put_contents(__DIR__.'/../user-config.php', $stub);

        $this->info('Setup completed successfully.');
    }
}
