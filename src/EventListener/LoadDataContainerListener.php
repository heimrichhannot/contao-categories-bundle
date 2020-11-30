<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\EventListener;

use HeimrichHannot\CategoriesBundle\Backend\Category;

/**
 * @Hook("loadDataContainer")
 */
class LoadDataContainerListener
{
    public function __invoke(string $table)
    {
        switch ($table) {
            case 'tl_filter_config_element':
                $this->prepareTlFilterConfigElementTable();
        }
    }

    protected function prepareTlFilterConfigElementTable()
    {
        if (!class_exists('HeimrichHannot\FilterBundle\HeimrichHannotContaoFilterBundle')) {
            return;
        }
        $dca = &$GLOBALS['TL_DCA']['tl_filter_config_element'];

        $dca['palettes'][\HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType::TYPE] = str_replace(
            'field,',
            'field,parentCategories,',
            $dca['palettes'][\HeimrichHannot\FilterBundle\Filter\Type\ChoiceType::TYPE]
        );
        $dca['palettes'][\HeimrichHannot\CategoriesBundle\Filter\Type\ParentCategoryChoiceType::TYPE] =
            '{general_legend},title,type;
            {config_legend},field,parentCategories;
            {publish_legend},published;';

        Category::addMultipleCategoriesFieldToDca(
            'tl_filter_config_element',
            'parentCategories',
            [
                'addPrimaryCategory' => false,
                'forcePrimaryCategory' => false,
                'parentsUnselectable' => false,
                'mandatory' => false,
                'tl_class' => 'autoheight clr',
            ],
            $GLOBALS['TL_LANG']['tl_filter_config_element']['parentCategories']
        );
    }
}
