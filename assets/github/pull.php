<?php
define('MODX_API_MODE', true);
require_once dirname(dirname(__DIR__)) . '/index.php';
/** @var $modx gitModx */
$input = json_decode(file_get_contents('php://input'),1);
if(strpos($input['ref'],$modx->getOption('github_branch_on_push',[],'main'))){
    `git pull`;
    $modx->cacheManager->refresh();
}

