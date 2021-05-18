<?php

class MultiCurrencyProviderUpdateProcessor extends modObjectUpdateProcessor
{
    public $classKey = 'MultiCurrencyProvider';
    public $languageTopics = array('msmulticurrency:multicurrency');
    /** @var MsMC $msmc */
    public $msmc;
    private $oldEnable;

    public function initialize()
    {
        $this->msmc = $this->modx->getService('msmulticurrency', 'MsMC');
        return parent::initialize();
    }

    public function beforeSet()
    {
        $this->setCheckbox('enable');
        $properties = $this->getProperty('properties');
        $this->oldEnable = $this->object->get('enable');
        $className = $this->getProperty('class_name');

        if ($this->modx->getCount($this->classKey, array('class_name' => $className, 'id:!=' => $this->object->get('id')))) {
            $this->modx->error->addField('class_name', $this->modx->lexicon('msmulticurrency.err.provider_class_name_ae'));
        }

        if (!$this->modx->loadClass($className, $this->msmc->config['providerPath'], true, true)) {
            $this->modx->error->addField('class_name', $this->modx->lexicon('msmulticurrency.err.provider_file_nf'));
        }

        $properties = empty($properties) ? array() : $this->modx->fromJSON($properties);
        $this->setProperty('properties', $properties);

        return !$this->hasErrors();
    }

    public function afterSave()
    {
        $enable = $this->getProperty('enable');

        if ($canSave = parent::afterSave() && empty($this->oldEnable) && $enable) {
            $table = $this->modx->getTableName($this->classKey);
            $sql = "UPDATE {$table} SET enable = 0 WHERE id !=" . $this->object->get('id');
            $this->modx->exec($sql);
        }

        return $canSave;
    }

}

return 'MultiCurrencyProviderUpdateProcessor';