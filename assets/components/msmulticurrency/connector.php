<?php
/**
 * msMultiCurrency Connector
 * @package msmulticurrency
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH.'index.php';

$corePath = $modx->getOption('msmulticurrency.core_path',null,$modx->getOption('core_path').'components/msmulticurrency/');
require_once $corePath.'model/msmulticurrency/msmc.class.php';
$modx->msmc = new MsMC($modx);

$modx->lexicon->load('msmulticurrency:default');

/* handle request */
$path = $modx->getOption('processorsPath',$modx->msmc->config,$corePath.'processors/');
$modx->request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));
