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

    public function getOptions()
    {
        return [
            ['logout', null, InputOption::VALUE_NONE, 'If set, user will log out', null],
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

        if ($this->option('logout')) {
            exit;
        }

        if ($result = $this->login($language)) {
            $url = $this->project->getApiUrls()[$language];
            $url = parse_url($url, PHP_URL_HOST);

            $username = $this->project->getApiUsernames()[$language];

            $this->info(sprintf('Logged in on %s as %s', $url, $username));
        }
    }
}
