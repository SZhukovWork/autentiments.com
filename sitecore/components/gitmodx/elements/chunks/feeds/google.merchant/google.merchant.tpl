<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<name>{$_modx->config.site_name}</name>
<description>{$_modx->resource.description ?: 'Autentiments — это новое прочтение современной женственности'}</description>
<link>{'site_url' | config}</link>
{set $site_url = ('site_url' | option) | preg_replace : '#/$#' : ''}
{$_modx->setPlaceholder('site_url_pls', $site_url)}
{$_modx->setPlaceholder('id_postfix_pls', $_modx->config['cultureKey'] == 'ru' ? '' : $_modx->config['cultureKey'])}
{$_modx->setPlaceholder('currency_id_pls', $_modx->config['cultureKey'] == 'ru' ? 1 : 6)}
{$_modx->setPlaceholder('currency_code_pls', $_modx->config['cultureKey'] == 'ru' ? 'RUB' : 'EUR')}
{'!msProducts' | snippet : [
    'parents' => 7,
    'level' => 10,
    'limit' => 0,
    'showUnpublished' => false,
    'showDeleted' => false,
    'showHidden' => false,
    'includeContent' => true,
    'tpl' => 'google.merchant.tpl',
]}
</channel>
</rss>