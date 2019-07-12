<?php

$lang = &$GLOBALS['TL_LANG']['tl_filter_config_element'];

$lang['parentCategories'] = ['Elternkategorien', 'Wählen Sie hier die Elternkategorien der Kategorien aus, welche im Filter gewählt werden können.'];

if (\Contao\System::getContainer()->get('huh.utils.container')->isBundleActive('HeimrichHannot\FilterBundle\HeimrichHannotContaoFilterBundle')) {
    $lang['reference']['type'][\HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType::TYPE] = 'Kategorien';
}