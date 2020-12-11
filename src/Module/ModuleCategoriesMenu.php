<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Module;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\CategoriesBundle\Backend\Category;

class ModuleCategoriesMenu extends \Contao\Module
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_categoriesMenu';

    /**
     * Active category.
     *
     * @var object
     */
    protected $objActiveCategory = null;

    /**
     * Active categories.
     *
     * @var array
     */
    protected $activeCategories = [];

    /**
     * Category trail.
     *
     * @var array
     */
    protected $arrCategoryTrail = [];

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### CATEGORIES MENU ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile()
    {
        $categoriesManager = System::getContainer()->get('huh.categories.manager');

        if ($this->cm_customCategories) {
            $categories = $categoriesManager->findMultipleByIds(StringUtil::deserialize($this->cm_categories, true));
        } else {
            $categories = $categoriesManager->findAll();
        }

        // Return if no categories are found
        if (null === $categories) {
            $this->Template->categories = '';

            return;
        }

        /* @var $objPage PageModel */
        global $objPage;
        $strParam = Category::getUrlParameterName();
        $strUrl = $objPage->getFrontendUrl('/'.$strParam.'/%s');

        // Get the jumpTo page
        if ($this->jumpTo > 0 && $objPage->id != $this->jumpTo) {
            $objJump = PageModel::findByPk($this->jumpTo);

            if (null !== $objJump) {
                $strUrl = $objPage->getFrontendUrl($objPage->row().'/'.$strParam.'/%s');
            }
        }

        $arrIds = [];

        // Get the parent categories IDs
        foreach ($categories as $category) {
            if ($this->cm_customCategories) {
                $arrIds[] = $category->id;
            } else {
                $arrIds = array_merge($arrIds, $categoriesManager->findBy('pid', $category->pid)->fetchEach('id'));
            }
        }

        // Get the active category
        if ('' != System::getContainer()->get('huh.request')->getGet($strParam)) {
            $this->objActiveCategory = $categoriesManager->findByIdOrAlias(System::getContainer()->get('huh.request')->getGet($strParam));

            if (null !== $this->objActiveCategory) {
                $this->arrCategoryTrail = $categoriesManager->findBy('pid', $this->objActiveCategory->pid)->fetchEach('id');

                // Remove the current category from the trail
                unset($this->arrCategoryTrail[array_search($this->objActiveCategory->id, $this->arrCategoryTrail)]);
            }
        }

        $rootId = 0;

        // Set the custom root ID
        if ($this->cm_customRoot) {
            $rootId = $this->cm_categoriesRoot;
        }

        $this->Template->categories = $this->renderCategories($rootId, array_unique($arrIds), $strUrl);
    }

    /**
     * Recursively compile the  categories and return it as HTML string.
     *
     * @param int
     * @param int
     *
     * @return string
     */
    protected function renderCategories($intPid, $arrIds, $strUrl, $intLevel = 1)
    {
        $categories = System::getContainer()->get('huh.categories.manager')->findCategoryAndSubcategoryByPidAndIds($intPid, $arrIds);

        if (null === $categories) {
            return '';
        }

        $strParam = Category::getUrlParameterName();
        $arrCategories = [];

        // Layout template fallback
        if ('' == $this->navigationTpl) {
            $this->navigationTpl = 'nav_default';
        }

        $objTemplate = new FrontendTemplate($this->navigationTpl);
        $objTemplate->type = \get_class($this);
        $objTemplate->cssID = $this->cssID;
        $objTemplate->level = 'level_'.$intLevel;
        $objTemplate->showQuantity = $this->cm_showQuantity;

        $count = 0;
        $total = $categories->count();

        // Add the "reset categories" link
        if ($this->cm_resetCategories && 1 == $intLevel) {
            $blnActive = System::getContainer()->get('huh.request')->getGet($strParam) ? false : true;

            $arrCategories[] = [
                'isActive' => empty($this->activeCategories) && $blnActive,
                'subitems' => '',
                'class' => 'reset first'.((1 == $total) ? ' last' : '').' even'.($blnActive ? ' active' : ''),
                'title' => StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['cm_resetCategories'][1]),
                'linkTitle' => StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['cm_resetCategories'][1]),
                'link' => $GLOBALS['TL_LANG']['MSC']['cm_resetCategories'][0],
                'href' => ampersand(str_replace('/'.$strParam.'/%s', '', $strUrl)),
            ];

            $count = 1;
            ++$total;
        }

        ++$intLevel;

        // Render categories
        foreach ($categories as $category) {
            $strSubcategories = '';

            // Get the subcategories
            if ($category->subcategories) {
                $strSubcategories = $this->renderCategories($category->id, $arrIds, $strUrl, $intLevel);
            }

            $blnActive = (null !== $this->objActiveCategory) && ($this->objActiveCategory->id == $category->id);
            $strClass = ('cm_category_'.$category->id).($category->cssClass ? (' '.$category->cssClass) : '').((1 == ++$count) ? ' first' : '').(($count == $total) ? ' last' : '').((0 == ($count % 2)) ? ' odd' : ' even').($blnActive ? ' active' : '').(('' != $strSubcategories) ? ' submenu' : '').(\in_array($category->id, $this->arrCategoryTrail) ? ' trail' : '').(\in_array(
                    $category->id,
                    $this->activeCategories
                ) ? ' cm_trail' : '');
            $strTitle = $category->frontendTitle ?: $category->title;

            if (\System::getContainer()->get('translator')->getCatalogue()->has($strTitle)) {
                $strTitle = \System::getContainer()->get('translator')->trans($strTitle);
            }

            $arrRow = $category->row();
            $arrRow['isActive'] = $blnActive;
            $arrRow['subitems'] = $strSubcategories;
            $arrRow['class'] = $strClass;
            $arrRow['title'] = StringUtil::specialchars($strTitle, true);
            $arrRow['linkTitle'] = StringUtil::specialchars($strTitle, true);
            $arrRow['link'] = $strTitle;
            $arrRow['href'] = ampersand(str_replace('%s', ($GLOBALS['TL_CONFIG']['disableAlias'] ? $category->id : $category->alias), $strUrl));

            $arrCategories[] = $arrRow;
        }

        $objTemplate->items = $arrCategories;

        return !empty($arrCategories) ? $objTemplate->parse() : '';
    }
}
