<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;

class DummyCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dummy-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command description';

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
        //
    }
}
