<?php

class PolylangPolylangContentGetProcessor extends modObjectGetProcessor
{
    public $classKey = 'PolylangContent';
    public $languageTopics = array('polylang:default');
    /** @var Polylang $polylang  */
    public $polylang ;

    public function initialize()
{
    // $this->polylang = $this->modx->getService('polylang', 'Polylang');
    return parent::initialize();
}

}

return 'PolylangPolylangContentGetProcessor';