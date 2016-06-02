<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;

class Login extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log in';

    public function getArguments()
    {
        $defaultLanguage = $this->project->getDefaultLanguage();

        return [
            ['language', InputArgument::OPTIONAL, 'Language of the project', $defaultLanguage],
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

        $this->logout($language);

        if ($result = $this->login($language)) {
            $this->info('Logged in');
        }
    }
}
