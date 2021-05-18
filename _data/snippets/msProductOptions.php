id: 50
source: 1
name: msProductOptions
category: miniShop2
properties: 'a:9:{s:7:"product";a:7:{s:4:"name";s:7:"product";s:4:"desc";s:16:"ms2_prop_product";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:3:"tpl";a:7:{s:4:"name";s:3:"tpl";s:4:"desc";s:12:"ms2_prop_tpl";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:20:"tpl.msProductOptions";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:12:"ignoreGroups";a:7:{s:4:"name";s:12:"ignoreGroups";s:4:"desc";s:21:"ms2_prop_ignoreGroups";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:13:"ignoreOptions";a:7:{s:4:"name";s:13:"ignoreOptions";s:4:"desc";s:22:"ms2_prop_ignoreOptions";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:11:"onlyOptions";a:7:{s:4:"name";s:11:"onlyOptions";s:4:"desc";s:20:"ms2_prop_onlyOptions";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:10:"sortGroups";a:7:{s:4:"name";s:10:"sortGroups";s:4:"desc";s:19:"ms2_prop_sortGroups";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:11:"sortOptions";a:7:{s:4:"name";s:11:"sortOptions";s:4:"desc";s:20:"ms2_prop_sortOptions";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:16:"sortOptionValues";a:7:{s:4:"name";s:16:"sortOptionValues";s:4:"desc";s:25:"ms2_prop_sortOptionValues";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}s:6:"groups";a:7:{s:4:"name";s:6:"groups";s:4:"desc";s:15:"ms2_prop_groups";s:4:"type";s:9:"textfield";s:7:"options";a:0:{}s:5:"value";s:0:"";s:7:"lexicon";s:20:"minishop2:properties";s:4:"area";s:0:"";}}'
static_file: core/components/minishop2/elements/snippets/snippet.ms_product_options.php

-----

/** @var modX $modx */
/** @var array $scriptProperties */
/** @var miniShop2 $miniShop2 */
$miniShop2 = $modx->getService('miniShop2');

$tpl = $modx->getOption('tpl', $scriptProperties, 'tpl.msProductOptions');
if (!empty($input) && empty($product)) {
    $product = $input;
}

$product = !empty($product) && $product != $modx->resource->id
    ? $modx->getObject('msProduct', array('id' => $product))
    : $modx->resource;
if (!($product instanceof msProduct)) {
    return "[msProductOptions] The resource with id = {$product->id} is not instance of msProduct.";
}

$ignoreGroups = array_diff(array_map('trim', explode(',', $modx->getOption('ignoreGroups', $scriptProperties, ''))), array(''));
$ignoreOptions = array_diff(array_map('trim', explode(',', $modx->getOption('ignoreOptions', $scriptProperties, ''))), array(''));
$sortGroups = array_diff(array_map('trim', explode(',', $modx->getOption('sortGroups', $scriptProperties, ''))), array(''));
$sortOptions = array_diff(array_map('trim', explode(',', $modx->getOption('sortOptions', $scriptProperties, ''))), array(''));
$onlyOptions = array_diff(array_map('trim', explode(',', $modx->getOption('onlyOptions', $scriptProperties, ''))), array(''));
if (empty($sortOptions) && !empty($onlyOptions)) {
    $sortOptions = $onlyOptions;
}
$groups = !empty($groups)
    ? array_map('trim', explode(',', $groups))
    : array();
/** @var msProductData $data */
if ($data = $product->getOne('Data')) {
    $optionKeys = $data->getOptionKeys();
}
if (empty($optionKeys)) {
    return '';
}
$productData = $product->loadOptions();

$options = array();
foreach ($optionKeys as $key) {
    // Filter by key
    if (!empty($onlyOptions) && $onlyOptions[0] != '' && !in_array($key, $onlyOptions)) {
        continue;
    } elseif (in_array($key, $ignoreOptions)) {
        continue;
    }
    $option = array();
    foreach ($productData as $dataKey => $dataValue) {
        $dataKey = explode('.', $dataKey);
        if ($dataKey[0] == $key && (count($dataKey) > 1)) {
            $option[$dataKey[1]] = $dataValue;
        }
    }

    $skip = (!empty($ignoreGroups) && (in_array($option['category'], $ignoreGroups) || in_array($option['category_name'], $ignoreGroups)))
        || (!empty($groups) && !in_array($option['category'], $groups) && !in_array($option['category_name'], $groups));

    if (!$skip) {
        $option['value'] = $product->get($key);
        if (!empty($option['value'])) {
            $options[$key] = $option;
        }
    }
}

if (!empty($sortGroups) && !empty($options)) {
    $sortGroups = array_map('mb_strtolower', $sortGroups);
    uasort($options, function($a, $b) use ($sortGroups) {
        $ai = array_search(mb_strtolower($a['category'], 'utf-8'), $sortGroups, true);
        $ai = $ai !== false ? $ai : array_search(mb_strtolower($a['category_name'], 'utf-8'), $sortGroups, true);
        $bi = array_search(mb_strtolower($b['category'], 'utf-8'), $sortGroups, true);
        $bi = $bi !== false ? $bi : array_search(mb_strtolower($b['category_name'], 'utf-8'), $sortGroups, true);
        if ($ai === false && $bi === false) {
            return 0;
        } elseif ($ai === false) {
            return 1;
        } elseif ($bi === false) {
            return -1;
        } elseif ($ai < $bi) {
            return -1;
        } elseif ($ai > $bi) {
            return 1;
        }
        return 0;
    });
}

if (!empty($sortOptions) && !empty($options)) {
    $sortOptions = array_map('mb_strtolower', $sortOptions);
    uksort($options, function($a, $b) use ($sortOptions) {
        $ai = array_search(mb_strtolower($a, 'utf-8'), $sortOptions, true);
        $bi = array_search(mb_strtolower($b, 'utf-8'), $sortOptions, true);
        if ($ai === false && $bi === false) {
            return 0;
        } elseif ($ai === false) {
            return 1;
        } elseif ($bi === false) {
            return -1;
        } elseif ($ai < $bi) {
            return -1;
        } elseif ($ai > $bi) {
            return 1;
        }
        return 0;
    });
}

$options = $miniShop2->sortOptionValues($options, $scriptProperties['sortOptionValues']);

if (in_array($scriptProperties['return'], array('data', 'array'), true)) {
    return $options;
}

/** @var pdoTools $pdoTools */
$pdoTools = $modx->getService('pdoTools');

return $pdoTools->getChunk($tpl, array(
    'options' => $options,
));