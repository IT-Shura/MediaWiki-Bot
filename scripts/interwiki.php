<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MediaWiki\Bot\Command;

class Interwiki extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'interwiki';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interwiki Bot';

    /**
     * Missed pages.
     * 
     * @var array
     */
    protected $missed = [];

    protected $rejected = [];

    protected $tokens = [];

    protected $reportsDirectory;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($this->option('report')) {
            $this->prepareReportsDirectory();
        }
    }

    public function prepareReportsDirectory()
    {
        $this->reportsDirectory = __DIR__.'/../storage/reports/interwiki';

        if (!file_exists($this->reportsDirectory)) {
            mkdir($this->reportsDirectory, 777, true);
        }
    }

    public function getArguments()
    {
        $defaultLanguage = $this->project->getDefaultLanguage();

        return [
            ['language', InputArgument::OPTIONAL, 'Language of the project', $defaultLanguage],
        ];
    }

    public function getOptions()
    {
        $defaultLanguage = $this->project->getDefaultLanguage();

        return [
            ['titles', null, InputOption::VALUE_REQUIRED, '', null],
            ['namespace', null, InputOption::VALUE_REQUIRED, '', null],
            ['all', null, InputOption::VALUE_NONE, '', null],
            ['reset', null, InputOption::VALUE_NONE, '', null],
            ['report', null, InputOption::VALUE_NONE, '', null],
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

        if ($this->option('all')) {
            $this->handleAllPages($language);

            return;
        }

        if ($this->option('namespace')) {
            $this->handlePagesInNamespace($language, $this->option('namespace'));

            return;
        }

        if ($this->option('titles')) {
            $titles = explode('|', $this->option('titles'));

            $this->handleSpecifiedPages($language, $titles);

            return;
        }

        $this->handlePages($language);
    }

    public function handleAllPages($language)
    {
        $reset = $this->storage->get('interwiki.last-action') !== 'all';

        $this->storage->forever('interwiki.last-action', 'all');

        $data = [
            'apnamespace' => 0,
        ];

        $this->handlePages($language, $data, $reset);
    }

    public function handlePagesInNamespace($language, $namespace)
    {
        $reset = $this->storage->get('interwiki.last-action') !== 'namespace';
        $reset = $reset or $this->storage->get('interwiki.last-namespace') !== $namespace;

        $this->storage->forever('interwiki.last-action', 'namespace');
        $this->storage->forever('interwiki.last-namespace', $namespace);

        $namespaces = $this->project->service('namespaces')->getList($language);

        $namespaceId = null;

        foreach ($namespaces as $data) {
            if ($data['name'] === $namespace) {
                $namespaceId = $data['id'];

                break;
            }
        }

        if ($namespaceId === null) {
            $this->error(sprintf('Namespace "%s" does not exists', $namespace));

            exit;
        }

        $data = [
            'apnamespace' => $namespaceId,
        ];

        $this->handlePages($language, $data, $reset);
    }

    public function handleSpecifiedPages($language, $titles)
    {
        foreach ($titles as $title) {
            $this->updateInterwikiLinks($title, $language);
        }
    }

    public function handlePages($language, $parameters = [], $reset = false)
    {
        if ($this->option('reset') or $reset) {
            $this->storage->forget('interwiki.continue');
            $this->storage->forget('interwiki.apcontinue');
            $this->storage->forget('interwiki.parameters');
        }

        $this->storage->forever('interwiki.parameters', $parameters);

        $continue = $this->storage->get('interwiki.continue');
        $apcontinue = $this->storage->get('interwiki.apcontinue');

        while (true) {
            echo PHP_EOL, 'Loading pages list...', str_repeat(PHP_EOL, 2);

            $response = $this->getPagesList($language, $continue, $apcontinue, $parameters);

            $continue = $response['continue'];
            $apcontinue = $response['apcontinue'];

            foreach ($response['list'] as $page) {
                $this->updateInterwikiLinks($page['title'], $language);
            }

            if ($continue === null) {
                $this->storage->forget('interwiki.continue');
                $this->storage->forget('interwiki.apcontinue');
                $this->storage->forget('interwiki.parameters');

                break;
            }

            $this->storage->forever('interwiki.continue', $continue);
            $this->storage->forever('interwiki.apcontinue', $apcontinue);
        }
    }

    public function getPagesList($language, $continue = null, $apcontinue = null, $extParameters = [])
    {
        $parameters = [
            'list' => 'allpages',
            'continue' => $continue,
            'apcontinue' => $apcontinue,
        ];

        $parameters = array_merge($parameters, $extParameters);

        $response = $this->project->api($language)->query($parameters);

        if (array_key_exists('continue', $response)) {
            $continue = $response['continue']['continue'];
            $apcontinue = $response['continue']['apcontinue'];
        } else {
            $continue = null;
            $apcontinue = null;
        }

        return [
            'continue' => $continue,
            'apcontinue' => $apcontinue,
            'list' => $response['query']['allpages'],
        ];
    }

    public function updateInterwikiLinks($title, $language)
    {
        $this->rejected = [];

        $links = $this->getLangLinks($title, $language);

        ksort($links);

        $titles = array_combine(array_keys($links), array_column($links, 'title'));

        foreach ($links as $language => $data) {
            $wikiText = $this->loadWikiText($data['title'], $language);

            foreach ($data['langlinks'] as $linkLanguage => $linkTitle) {
                $pattern = sprintf('/\[\[\s*%s\s*:\s*%s\s*\]\]/', $linkLanguage, preg_quote($linkTitle));
                $pattern = str_replace(' ', '[ _]', $pattern);

                $wikiText = preg_replace($pattern, '', $wikiText);
            }

            $wikiText = trim($wikiText);

            $tags = $this->generateInterwikiTags($titles, $language);

            $wikiText .= PHP_EOL.PHP_EOL.$tags;

            $response = $this->savePage($data['title'], $wikiText, $language);

            if ($response['edit']['result'] !== "Success") {
                $this->error(sprintf('Error on saving "%s" (%s)', $data['title'], $language));

                var_dump($response);

                die;
            }
        }

        if ($this->option('report')) {
            if (count($this->rejected) > 0) {
                $this->logRejected($title, $language, $this->rejected);
            }
        }

        echo sprintf('%s - OK', $title), PHP_EOL;
    }

    public function logRejected($title, $language, $rejected)
    {
        $filename = sprintf('%s/%s.txt', $this->reportsDirectory, $language);

        $data = [];

        foreach ($rejected as $language => $titles) {
            $data[] = $language.' - '.implode(', ', $titles);
        }

        $body = implode(PHP_EOL, $data);

        $delimiter = str_repeat(PHP_EOL, 2).str_repeat('=', 20).str_repeat(PHP_EOL, 2);

        $data = $title.str_repeat(PHP_EOL, 2).$body.$delimiter;

        file_put_contents($filename, $data, FILE_APPEND);
    }

    public function loadWikiText($title, $language)
    {
        $parameters = ['rvprop' => 'timestamp|user|comment|content'];

        $page = $this->loadPage($title, $language, 'revisions', $parameters);

        if (array_key_exists('invalid', $page)) {
            $this->error(sprintf('Page "%s" is invalid', $title));

            var_dump($page);

            exit;
        }

        $revision = array_shift($page['revisions']);

        return $revision['*'];
    }

    public function generateInterwikiTags($links, $exclude)
    {
        $tags = [];

        unset($links[$exclude]);

        foreach ($links as $language => $title) {
            $tags[] = sprintf('[[%s:%s]]', $language, str_replace(' ', '_', $title));            
        }

        return implode(PHP_EOL, $tags);
    }

    public function savePage($title, $content, $language)
    {
        $token = $this->getCsrfToken($language);

        $parameters = [
            'action' => 'edit',
            'title' => $title,
            'text' => $content,
            'bot' => true,
            'nocreate' => true,
            'token' => $token,
        ];

        return $this->project->api($language)->request('POST', $parameters);
    }

    public function getCsrfToken($language)
    {
        if (!array_key_exists($language, $this->tokens)) {
            $parameters = [
                'action' => 'query',
                'meta' => 'tokens',
                'type' => 'csrf',
            ];

            $response = $this->project->api($language)->request('POST', $parameters);

            $this->tokens[$language] = $response['query']['tokens']['csrftoken'];
        }

        return $this->tokens[$language];
    }

    public function getLangLinks($title, $language, $currentLinks = [])
    {
        $this->login($language);

        $page = $this->loadPage($title, $language, 'langlinks');

        if (array_key_exists('invalid', $page)) {
            $this->error(sprintf('Page "%s" is invalid', $title));

            var_dump($page);

            exit;
        }

        if (array_key_exists('missing', $page)) {
            $this->addMissed($title, $language);

            return $currentLinks;
        }

        $langLinks = array_key_exists('langlinks', $page) ? $page['langlinks'] : [];

        $langLinks = $this->normalizeLangLinks($langLinks);

        $currentLinks[$language] = [
            'title' => $title,
            'langlinks' => $langLinks,
        ];   

        foreach ($langLinks as $language => $title) {
            if ($title === '') {
                continue;
            }

            if ($this->isRejected($title, $language)) {
                continue;
            }

            // skip page if their interwiki links already saved
            if (array_key_exists($language, $currentLinks)) {
                if ($currentLinks[$language]['title'] === $title) {
                    continue;
                }

                $message = sprintf('Conflict (%s)', $language);

                $options = [$currentLinks[$language]['title'], $title];

                $answer = $this->choice($message, $options, false);

                if ($answer === $currentLinks[$language]['title']) {
                    $this->addRejected($title, $language);

                    continue;
                }

                $this->addRejected($currentLinks[$language]['title'], $language);
            }

            // skip page if it missed
            if ($this->isMissed($title, $language)) {
                continue;
            }

            $currentLinks = $this->getLangLinks($title, $language, $currentLinks);
        }

        return $currentLinks;
    }

    public function normalizeLangLinks($langLinks)
    {
        $result = [];

        foreach ($langLinks as $link) {
            $lang = $link['lang'];
            $title = $link['*'];

            $result[$lang] = $title;
        }

        return $result;
    }

    public function addRejected($title, $language)
    {
        if (!array_key_exists($language, $this->rejected)) {
            $this->rejected = [];
        }

        $this->rejected[$language][] = $title;
    }

    public function isRejected($title, $language)
    {
        if (!array_key_exists($language, $this->rejected)) {
            return false;
        }

        return in_array($title, $this->rejected[$language]);
    }

    public function addMissed($title, $language)
    {
        if (!array_key_exists($language, $this->missed)) {
            $this->missed[$language] = [];
        }

        $this->missed[$language][] = $title;
    }

    public function isMissed($title, $language)
    {
        if (!array_key_exists($language, $this->missed)) {
            return false;
        }

        return in_array($title, $this->missed[$language]);
    }

    public function loadPage($title, $language, $properties = null, $extParameters = [])
    {
        if ($title === '') {
            throw new InvalidArgumentException(sprintf('Title must not be empty (%s)', $language));
        }

        $parameters = [
            'titles' => $title,
            'prop' => $properties,
        ];

        $parameters = array_merge($parameters, $extParameters);

        $response = $this->project->api($language)->query($parameters);

        $page = array_shift($response['query']['pages']);

        return $page;
    }
}
