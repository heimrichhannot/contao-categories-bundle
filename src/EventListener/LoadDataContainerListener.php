<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\System;
use HeimrichHannot\CategoriesBundle\Backend\Category;
use HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType;
use HeimrichHannot\CategoriesBundle\Filter\Type\ParentCategoryChoiceType;
use HeimrichHannot\FilterBundle\Filter\Type\ChoiceType;

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

        System::loadLanguageFile('tl_filter_config_element');

        $dca = &$GLOBALS['TL_DCA']['tl_filter_config_element'];

        $dca['palettes'][CategoryChoiceType::TYPE] = str_replace(
            'field,',
            'field,parentCategories,',
            $dca['palettes'][ChoiceType::TYPE]
        );
        $dca['palettes'][ParentCategoryChoiceType::TYPE] =
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
