<?php

$lang = &$GLOBALS['TL_LANG']['tl_filter_config_element'];

$lang['parentCategories'] = ["Parent categories", "Choose parent categories of the categories, which should be available in the filter."];

if (\Contao\System::getContainer()->get('huh.utils.container')->isBundleActive('HeimrichHannot\FilterBundle\HeimrichHannotContaoFilterBundle')) {
    $lang['reference']['type'][\HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType::TYPE] = "Categories";
}