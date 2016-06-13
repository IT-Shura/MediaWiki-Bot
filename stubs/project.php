<?php

use MediaWiki\Bot\Project;

class DummyProject extends Project
{
    /**
     * @var string
     */
    protected $name = 'dummy-project';

    /**
     * @var string
     */
    protected $title = 'Dummy Project';

    /**
     * @var string
     */
    protected $defaultLanguage = 'default-language';

    /**
     * @return array
     */
    public function getApiUrls()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getApiUsernames()
    {
        return [];
    }
}
