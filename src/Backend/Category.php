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
use Contao\Input;
use Contao\Versions;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\Haste\Dca\General;

class Category extends Backend
{
    const TYPE_DEFAULT = 'default';
    const TYPE_ROOT = 'root';

    const TYPES = [
        self::TYPE_DEFAULT,
        self::TYPE_ROOT,
    ];

    public static function getCategoryFieldDca()
    {
        return [
            'label' => &$GLOBALS['TL_LANG']['tl_category']['category'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'treePicker',
            'foreignKey' => 'tl_news_category.title',
            'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'foreignTable' => 'tl_news_category', 'titleField' => 'title', 'searchField' => 'title', 'managerHref' => 'do=news&table=tl_news_category'],
            'sql' => 'blob NULL',
        ];
    }

    public static function getCategoriesFieldDca($label = null)
    {
        $label = $label ?: $GLOBALS['TL_LANG']['tl_category']['categories'];

        return [
            'label' => &$label,
            'exclude' => true,
            'filter' => true,
            'inputType' => 'treePicker',
            'foreignKey' => 'tl_category.title',
            'eval' => [
                'multiple' => true,
                'fieldType' => 'checkbox',
                'foreignTable' => 'tl_category',
                'titleField' => 'title',
                'searchField' => 'title',
                'managerHref' => 'do=categories',
            ],
            'sql' => 'blob NULL',
        ];
    }

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
            $return = $disablePA ? Image::getHtml('pasteafter_.gif').' ' : '<a href="'.\Controller::addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1],
                    $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.gif').' ' : '<a href="'.\Controller::addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1],
                    $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }

    /**
     * Add the correct indentation.
     *
     * @param array
     * @param string
     * @param object
     * @param string
     *
     * @return string
     */
    public function generateLabel($arrRow, $strLabel, $objDca, $strAttributes)
    {
        return \Image::getHtml('/files/bundles/categories/icon_root_category.svg', '', $strAttributes).' '.$strLabel;
    }

    /**
     * Return the "toggle visibility" button.
     *
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (1 === Input::get('state')));
            \Controller::redirect(\Controller::getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.gif';
        }

        return '<a href="'.\Controller::addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Publish/unpublish a category.
     *
     * @param int
     * @param bool
     */
    public function toggleVisibility($intId, $blnVisible)
    {
        $objVersions = new Versions('tl_category', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_category']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_category']['fields']['published']['save_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
                } elseif (is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $this);
                }
            }
        }

        // Update the database
        $this->Database->prepare('UPDATE tl_category SET tstamp='.time().", published='".($blnVisible ? 1 : '')."' WHERE id=?")
            ->execute($intId);

        $objVersions->create();
        \HeimrichHannot\Haste\Util\Container::log('A new version of record "tl_category.id='.$intId.'" has been created'.$this->getParentEntries('tl_category', $intId), __METHOD__, TL_GENERAL);
    }
}
