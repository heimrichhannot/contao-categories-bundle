<?php

if (\Contao\System::getContainer()->get('huh.utils.container')->isBundleActive('HeimrichHannot\FilterBundle\HeimrichHannotContaoFilterBundle'))
{
    $table = 'tl_filter_config_element';
    \Contao\Controller::loadDataContainer($table);
    $dca = &$GLOBALS['TL_DCA'][$table];

    $dca['palettes'][\HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType::TYPE] = str_replace(
        'field,',
        'field,parentCategories,',
        $dca['palettes'][\HeimrichHannot\FilterBundle\Filter\Type\ChoiceType::TYPE]
    );

    \HeimrichHannot\CategoriesBundle\Backend\Category::addMultipleCategoriesFieldToDca(
        $table,
        'parentCategories',
        [
            'addPrimaryCategory' => false,
            'forcePrimaryCategory' => false,
            'parentsUnselectable' => false,
            'mandatory'            => false,
            'tl_class'             => 'autoheight clr',
        ],
        $GLOBALS['TL_LANG']['tl_filter_config_element']['parentCategories']
    );
}