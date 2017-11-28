<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\Haste\Dca\General;
use HeimrichHannot\Haste\Util\Container;

class Category extends Backend
{
    const PRIMARY_CATEGORY_SUFFIX = '_primary';

    /**
     * Shorthand function for adding a single category field to your dca.
     *
     * @param array  $evalOverride
     * @param string $label
     *
     * @return array
     */
    public static function getCategoryFieldDca($evalOverride = null, $label = null)
    {
        \System::loadLanguageFile('tl_category');

        $label = $label ?: $GLOBALS['TL_LANG']['tl_category']['category'];
        $eval = [
            'tl_class' => 'w50 autoheight',
            'mandatory' => true,
            'fieldType' => 'radio',
            'foreignTable' => 'tl_category',
            'titleField' => 'title',
            'searchField' => 'title',
            'managerHref' => 'do=categories',
            'isCategoryField' => true,
        ];

        if (is_array($evalOverride)) {
            $eval = array_merge($eval, $evalOverride);
        }

        return [
            'label' => &$label,
            'exclude' => true,
            'filter' => true,
            'inputType' => 'categoryTreePicker',
            'foreignKey' => 'tl_category.title',
            'load_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'loadCategoriesFromAssociations']],
            'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'storeToCategoryAssociations']],
            'eval' => $eval,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];
    }

    /**
     * Shorthand function for adding a multiple categories field to your dca.
     *
     * @param string $table
     * @param string $name
     * @param array  $evalOverride
     * @param string $label
     */
    public static function addMultipleCategoriesFieldToDca($table, $name, $evalOverride = null, $label = null)
    {
        \System::loadLanguageFile('tl_category');

        $label = $label ?: $GLOBALS['TL_LANG']['tl_category']['categories'];
        $eval = [
            'tl_class' => 'w50 autoheight',
            'mandatory' => true,
            'multiple' => true,
            'fieldType' => 'checkbox',
            'foreignTable' => 'tl_category',
            'titleField' => 'title',
            'searchField' => 'title',
            'addPrimaryCategory' => true,
            'managerHref' => 'do=categories',
            'isCategoryField' => true,
        ];

        if (is_array($evalOverride)) {
            $eval = array_merge($eval, $evalOverride);
        }

        \Controller::loadDataContainer($table);

        $GLOBALS['TL_DCA'][$table]['fields'][$name] = [
            'label' => &$label,
            'exclude' => true,
            'filter' => true,
            'inputType' => 'categoryTreePicker',
            'foreignKey' => 'tl_category.title',
            'load_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'loadCategoriesFromAssociations']],
            'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'storeToCategoryAssociations']],
            'eval' => $eval,
            'sql' => 'blob NULL',
        ];

        $GLOBALS['TL_DCA'][$table]['fields'][$name.static::PRIMARY_CATEGORY_SUFFIX] = [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];
    }

    /**
     * Automatically add overridable fields to the dca (including palettes, ...).
     */
    public static function addOverridableFieldSelectors()
    {
        $dca = &$GLOBALS['TL_DCA']['tl_category'];

        // add overridable fields
        foreach ($dca['fields'] as $field => $data) {
            if ($data['eval']['overridable']) {
                $overrideFieldName = 'override'.ucfirst($field);

                // boolean field
                $dca['fields'][$overrideFieldName] = [
                    'label' => &$GLOBALS['TL_LANG']['tl_category'][$overrideFieldName],
                    'exclude' => true,
                    'inputType' => 'checkbox',
                    'eval' => ['tl_class' => 'w50', 'submitOnChange' => true],
                    'sql' => "char(1) NOT NULL default ''",
                ];

                // selector
                $dca['palettes']['__selector__'][] = $overrideFieldName;

                // subpalette
                $dca['subpalettes'][$overrideFieldName] = $field;
            }
        }
    }

    /**
     * @param DataContainer $dc
     */
    public function modifyPalette(DataContainer $dc)
    {
        $category = CategoryModel::findByPk($dc->id);
        $dca = &$GLOBALS['TL_DCA']['tl_category'];

        if ($category) {
            if ($category->pid) {
                $dca['palettes']['default'] = str_replace('jumpTo', 'overrideJumpTo', $dca['palettes']['default']);
            }
        }
    }

    /**
     * @param mixed         $value
     * @param DataContainer $dc
     */
    public function storeToCategoryAssociations($value, DataContainer $dc)
    {
        switch ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['fieldType']) {
            case 'radio':
                \System::getContainer()->get('huh.categories.manager')->createAssociations($dc->id, $dc->field, [$value]);
                break;
            case 'checkbox':
                \System::getContainer()->get('huh.categories.manager')->createAssociations($dc->id, $dc->field, StringUtil::deserialize($value, true));
                break;
        }
    }

    /**
     * @param mixed         $value
     * @param DataContainer $dc
     *
     * @return array|null
     */
    public function loadCategoriesFromAssociations($value, DataContainer $dc)
    {
        $categories = \System::getContainer()->get('huh.categories.manager')->findByEntityAndField($dc->id, $dc->field);

        if (null === $categories) {
            return null;
        }

        $categoryIds = $categories->fetchEach('id');

        if (!empty($categoryIds)) {
            switch ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['fieldType']) {
                case 'radio':
                    return $categoryIds[0];
                case 'checkbox':
                    return $categoryIds;
            }
        }

        return null;
    }

    /**
     * @param string        $varValue
     * @param DataContainer $dc
     *
     * @return string
     */
    public static function generateAlias($varValue, DataContainer $dc)
    {
        if (null === ($category = CategoryModel::findByPk($dc->id))) {
            return '';
        }

        return General::generateAlias($varValue, $dc->id, 'tl_category', $category->title);
    }

    public function checkPermission()
    {
        $user = \BackendUser::getInstance();

        if (!$user->isAdmin && !$user->hasAccess('manage', 'categories')) {
            \Controller::redirect('contao/main.php?act=error');
        }
    }

    /**
     * Return the paste category button.
     *
     * @param \DataContainer
     * @param array
     * @param string
     * @param bool
     * @param array
     *
     * @return string
     */
    public function pasteCategory(DataContainer $dc, $row, $table, $cr, $arrClipboard = null)
    {
        $disablePA = false;
        $disablePI = false;

        // Disable all buttons if there is a circular reference
        if (false !== $arrClipboard && ('cut' === $arrClipboard['mode'] && (1 === $cr || $arrClipboard['id'] === $row['id']) || 'cutAll' === $arrClipboard['mode'] && (1 === $cr || in_array($row['id'], $arrClipboard['id'], true)))) {
            $disablePA = true;
            $disablePI = true;
        }

        $return = '';

        // Return the buttons
        $imagePasteAfter = Image::getHtml('pasteafter.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.gif', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

        if ($row['id'] > 0) {
            $return = $disablePA ? Image::getHtml('pasteafter_.gif').' ' : '<a href="'.\Controller::addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp=rt='.\RequestToken::get().'&amp;pid='.$row['id'].(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1],
                    $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.gif').' ' : '<a href="'.\Controller::addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;rt='.\RequestToken::get().'&amp;pid='.$row['id'].(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1],
                    $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }

    /**
     * @param array
     * @param string
     * @param object
     * @param string
     *
     * @return string
     */
    public function generateLabel($row, $label, $dca, $attributes)
    {
        if (isset($row['frontendTitle']) && $row['frontendTitle']) {
            $label .= '<span style="padding-left:3px;color:#b3b3b3;">['.$row['frontendTitle'].']</span>';
        }

        if ('edit' !== Container::getGet('act') &&
            null !== (\System::getContainer())->get('huh.categories.config_manager')->findBy(['tl_category_config.pid=?'], [$row['id']])
        ) {
            $label .= '<span style="padding-left:3px;color:#b3b3b3;">– '.$GLOBALS['TL_LANG']['MSC']['categoriesBundle']['configsAvailable'].'</span>';
        }

        return \Image::getHtml('iconPLAIN.gif', '', $attributes).' '.$label;
    }
}
