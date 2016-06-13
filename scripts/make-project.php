<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;
use MediaWiki\Helpers;

class MakeProject extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates new project';

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
        $name = $this->ask('Please enter the project name');
        $title = $this->ask('Please enter the project title');
        $defaultLanguage = $this->ask('Please specify default language for the project');
        
        $className = Helpers\pascal_case($name);

        $stub = file_get_contents(__DIR__.'/../stubs/project.php');

        $search = ['DummyProject', 'dummy-project', 'Dummy Project', 'default-language'];
        $replace = [$className, $name, $title, $defaultLanguage];

        $stub = str_replace($search, $replace, $stub);

        $filename = sprintf('%s/../projects/%s.php', __DIR__, $name);

        if (file_exists($filename)) {
            $this->error(sprintf('Project with name "%s" already exists.', $name));

            exit;
        }

        file_put_contents($filename, $stub);

        $this->info('Project created successfully.');
    }
}
