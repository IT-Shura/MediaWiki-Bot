<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;
use MediaWiki\Helpers;

class Namespaces extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'namespaces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Namespace manipulation bot';

    public function getArguments()
    {
        $defaultLanguage = $this->project->getDefaultLanguage();

        return [
            ['language', InputArgument::OPTIONAL, 'Language of the project', $defaultLanguage],
        ];
    }

    public function getOptions()
    {
        return [
            ['list', null, InputOption::VALUE_NONE, 'Show namespace list', null],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $language = $this->argument('language');

        if ($this->option('list')) {
            $this->showNamespaceList($language);

            return;
        }
    }

    public function showNamespaceList($language)
    {
        $header = ['ID', 'Name', 'Canonical'];

        $namespaces = $this->project->service('namespaces')->getList($language);

        $data = [];

        foreach ($namespaces as $namespace) {
            $data[] = [
                'id' => $namespace['id'],
                'name' => $namespace['name'],
                'canonical' => Helpers\array_get($namespace, 'canonical'),
            ];
        }

        $this->table($header, $data);
    }

    public function getNamespaceList($language)
    {
        $parameters = [
            'meta' => 'siteinfo',
            'siprop'=> 'namespaces',
            'formatversion' => 2,
        ];

        $response = $this->project->api($language)->query($parameters);

        return $response['query']['namespaces'];
    }
}
