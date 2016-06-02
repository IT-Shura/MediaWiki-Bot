<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;

class Projects extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows projects list';

    public function handle()
    {
        $headers = ['Code', 'Name'];

        $projects = [];

        foreach ($this->projectManager->getProjectsList() as $project) {
            $projects[] = [
                'code' => $project->getName(),
                'name' => $project->getTitle(),
            ];
        }

        $this->table($headers, $projects);
    }
}
