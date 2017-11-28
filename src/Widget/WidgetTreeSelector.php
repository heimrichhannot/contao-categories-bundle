<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Widget;

use HeimrichHannot\CategoriesBundle\Backend\Category;
use TreePicker\TreePickerHelper;

/**
 * Class WidgetTreeSelector.
 *
 * Provide methods to handle input field "tree picker".
 */
class WidgetTreeSelector extends \TreePicker\WidgetTreeSelector
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Path nodes.
     *
     * @var array
     */
    protected $arrNodes = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Load the database object.
     *
     * @param array
     */
    public function __construct($arrAttributes = null)
    {
        $this->import('Database');
        parent::__construct($arrAttributes);

        if (!$this->foreignTable) {
            throw new \Exception('The foreign table is not specified');
        }

        $this->loadDataContainer($this->foreignTable);
        \System::loadLanguageFile($this->foreignTable);

        // Add the scripts
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/widget_tree_picker/assets/treepicker.min.js';
    }

    /**
     * Get the search session key.
     *
     * @return string
     */
    public function getSearchSessionKey()
    {
        return 'treepicker_'.substr(md5($this->strTable.$this->strField), 0, 8).'_selector_search';
    }

    /**
     * Get the picker session key.
     *
     * @return string
     */
    public function getPickerSessionKey()
    {
        return 'tl_treepicker_'.substr(md5($this->strTable.$this->strField), 0, 8);
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        $this->import('BackendUser', 'User');

        // Store the keyword
        if ('item_selector' === \Input::post('FORM_SUBMIT')) {
            $this->Session->set($this->getSearchSessionKey(), \Input::post('keyword'));
            $this->reload();
        }

        $tree = '';
        $this->getPathNodes();
        $for = $this->Session->get($this->getSearchSessionKey());
        $arrIds = [];

        // Search for a specific item
        if ('' !== $for && $this->searchField) {
            // The keyword must not start with a wildcard
            if (0 === strncmp($for, '*', 1)) {
                $for = substr($for, 1);
            }

            $objRoot = $this->Database->prepare('SELECT id FROM '.$this->foreignTable.' WHERE CAST('.$this->searchField.' AS CHAR) REGEXP ?')
                                      ->execute($for);

            if ($objRoot->numRows > 0) {
                // Respect existing limitations
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['rootNodes'])) {
                    $arrRoot = [];

                    while ($objRoot->next()) {
                        // Predefined node set
                        if (count(array_intersect($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['rootNodes'], $this->Database->getParentRecords($objRoot->id, $this->foreignTable))) > 0) {
                            $arrRoot[] = $objRoot->id;
                        }
                    }

                    $arrIds = $arrRoot;
                } else {
                    $arrIds = $objRoot->fetchEach('id');
                }
            }

            // Build the tree
            foreach ($arrIds as $id) {
                $tree .= $this->renderItemTree($id, -20, true);
            }
        } else {
            $strNode = $this->Session->get($this->getPickerSessionKey());

            // Unset the node if it is not within the predefined node set
            if ($strNode > 0 && is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['rootNodes'])) {
                if (!in_array($strNode, $this->Database->getChildRecords($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['rootNodes'], $this->foreignTable), true)) {
                    $this->Session->remove($this->getPickerSessionKey());
                }
            }

            // Set a new node
            if (isset($_GET['node'])) {
                $this->Session->set($this->getPickerSessionKey(), \Input::get('node'));
                \Controller::redirect(preg_replace('/&node=[^&]*/', '', \Environment::get('request')));
            }

            $intNode = $this->Session->get($this->getPickerSessionKey());

            // Add breadcrumb menu
            if ($intNode) {
                $arrIds = [];
                $arrLinks = [];

                // Generate breadcrumb trail
                if ($intNode) {
                    $intId = $intNode;

                    do {
                        $objItem = $this->Database->prepare('SELECT * FROM '.$this->foreignTable.' WHERE id=?')
                                                  ->limit(1)
                                                  ->execute($intId);

                        if ($objItem->numRows < 1) {
                            // Currently selected item does not exist
                            if ($intId === $intNode) {
                                $this->Session->set($this->getPickerSessionKey(), 0);

                                return;
                            }

                            break;
                        }

                        $arrIds[] = $intId;

                        // No link for the active item
                        if ($objItem->id === $intNode) {
                            $arrLinks[] = TreePickerHelper::generateItemLabel($objItem, $this->foreignTable, $this->objDca, $this->titleField);
                        } else {
                            $arrLinks[] = '<a href="'.\Controller::addToUrl('node='.$objItem->id).'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.TreePickerHelper::generateItemLabel($objItem, $this->foreignTable, $this->objDca, $this->titleField).'</a>';
                        }

                        $intId = $objItem->pid;
                    } while ($intId > 0);
                }

                // Limit tree
                $GLOBALS['TL_DCA'][$this->foreignTable]['list']['sorting']['root'] = [$intNode];

                // Add root link
                $arrLinks[] = \Image::getHtml($GLOBALS['TL_DCA'][$this->foreignTable]['list']['sorting']['icon'] ?: 'iconPLAIN.gif').' <a href="'.\Controller::addToUrl('node=0').'" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
                $arrLinks = array_reverse($arrLinks);

                // Insert breadcrumb menu
                $GLOBALS['TL_DCA'][$this->foreignTable]['list']['sorting']['breadcrumb'] .= '
<ul id="tl_breadcrumb">
  <li>'.implode(' &gt; </li><li>', $arrLinks).'</li>
</ul>';
            }

            // Root nodes (breadcrumb menu)
            if (!empty($GLOBALS['TL_DCA'][$this->foreignTable]['list']['sorting']['root'])) {
                $tree = $this->renderItemTree($GLOBALS['TL_DCA'][$this->foreignTable]['list']['sorting']['root'][0], -20);
            }

            // Predefined node set
            elseif (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['rootNodes'])) {
                foreach ($this->eliminateNestedPages($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['rootNodes'], $this->foreignTable) as $node) {
                    $tree .= $this->renderItemTree($node, -20);
                }
            }

            // Show all items
            else {
                $objItem = $this->Database->prepare('SELECT id FROM '.$this->foreignTable.' WHERE pid=? ORDER BY sorting')
                                          ->execute(0);

                while ($objItem->next()) {
                    $tree .= $this->renderItemTree($objItem->id, -20);
                }
            }
        }

        // Select all checkboxes
        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['fieldType'] === 'checkbox') {
            $strReset = "\n".'    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="tl_right"><label for="check_all_'.$this->strId.'" class="tl_change_selected">'.$GLOBALS['TL_LANG']['MSC']['selectAll'].'</label> <input type="checkbox" id="check_all_'.$this->strId.'" class="tl_tree_checkbox" value="" onclick="Backend.toggleCheckboxGroup(this,\''.$this->strName.'\')"></div><div style="clear:both"></div></li>';
        }
        // Reset radio button selection
        else {
            $strReset = "\n".'    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="tl_right"><label for="reset_'.$this->strId.'" class="tl_change_selected">'.$GLOBALS['TL_LANG']['MSC']['resetSelected'].'</label> <input type="radio" name="'.$this->strName.'" id="reset_'.$this->strName.'" class="tl_tree_radio" value="" onfocus="Backend.getScrollOffset()"></div><div style="clear:both"></div></li>';
        }

        // Return the tree
        return '<ul class="tl_listing tree_view picker_selector'.(('' !== $this->strClass) ? ' '.$this->strClass : '').'" id="'.$this->strId.'">
    <li class="tl_folder_top"><div class="tl_left">'.\Image::getHtml($GLOBALS['TL_DCA'][$this->foreignTable]['list']['sorting']['icon'] ?: 'iconPLAIN.gif').' '.($GLOBALS['TL_DCA'][$this->foreignTable]['config']['label'] ?: $GLOBALS['TL_CONFIG']['websiteTitle']).'</div> <div class="tl_right" style="padding-right: 40px">'.$GLOBALS['TL_LANG']['MSC']['categoriesBundle']['primaryCategory'].'</div><div style="clear:both"></div></li><li class="parent" id="'.$this->strId.'_parent"><ul>'.$tree.$strReset.'
  </ul></li></ul>';
    }

    /**
     * Generate a particular subpart of the item tree and return it as HTML string.
     *
     * @param int
     * @param string
     * @param int
     *
     * @return string
     */
    public function generateAjax($id, $strField, $level)
    {
        if (!\Environment::get('isAjaxRequest')) {
            return '';
        }

        $this->strField = $strField;
        $this->loadDataContainer($this->strTable);

        // Load current values
        if ($this->Database->fieldExists($this->strField, $this->strTable)) {
            $objField = $this->Database->prepare('SELECT '.$this->strField.' FROM '.$this->strTable.' WHERE id=?')
                                       ->limit(1)
                                       ->execute($this->strId);

            if ($objField->numRows) {
                $this->varValue = deserialize($objField->{$this->strField});
            }
        }

        if ($this->Database->fieldExists($this->strField.Category::PRIMARY_CATEGORY_SUFFIX, $this->strTable)) {
            $objField = $this->Database->prepare('SELECT '.$this->strField.Category::PRIMARY_CATEGORY_SUFFIX.' FROM '.$this->strTable.' WHERE id=?')
                ->limit(1)
                ->execute($this->strId);

            if ($objField->numRows) {
                $this->primaryCategory = deserialize($objField->{$this->strField.Category::PRIMARY_CATEGORY_SUFFIX});
            }
        }

        // Call load_callback
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'])) {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->varValue = \System::importStatic($callback[0])->{$callback[1]}($this->varValue, $this->objDca);
                } elseif (is_callable($callback)) {
                    $this->varValue = $callback($this->varValue, $this->objDca);
                }
            }
        }

        $this->getPathNodes();

        // Load the requested nodes
        $tree = '';
        $level = $level * 20;

        $objItem = $this->Database->prepare('SELECT id FROM '.$this->foreignTable.' WHERE pid=? ORDER BY sorting')
                                  ->execute($id);

        while ($objItem->next()) {
            $tree .= $this->renderItemTree($objItem->id, $level);
        }

        return $tree;
    }

    /**
     * Recursively render the itemtree.
     *
     * @param int
     * @param int
     * @param bool
     * @param bool
     *
     * @return string
     */
    protected function renderItemTree($id, $intMargin, $blnNoRecursion = false)
    {
        static $session;
        $session = $this->Session->getData();

        $flag = substr($this->strField, 0, 2);
        $node = 'tree_'.$this->strTable.'_'.$this->strField;
        $xtnode = 'tree_'.$this->strTable.'_'.$this->strName;

        // Get the session data and toggle the nodes
        if (\Input::get($flag.'tg')) {
            $session[$node][\Input::get($flag.'tg')] = (isset($session[$node][\Input::get($flag.'tg')]) && $session[$node][\Input::get($flag.'tg')] === 1) ? 0 : 1;
            $this->Session->setData($session);
            $this->redirect(preg_replace('/(&(amp;)?|\?)'.$flag.'tg=[^& ]*/i', '', \Environment::get('request')));
        }

        $objItem = $this->Database->prepare('SELECT * FROM '.$this->foreignTable.' WHERE id=?')
                                  ->limit(1)
                                  ->execute($id);

        // Return if there is no result
        if ($objItem->numRows < 1) {
            return '';
        }

        $return = '';
        $intSpacing = 20;
        $childs = [];

        // Check whether there are child records
        if (!$blnNoRecursion) {
            $objNodes = $this->Database->prepare('SELECT id FROM '.$this->foreignTable.' WHERE pid=? ORDER BY sorting')
                                       ->execute($id);

            if ($objNodes->numRows) {
                $childs = $objNodes->fetchEach('id');
            }
        }

        $return .= "\n    ".'<li class="tl_file toggle_select primary-category" onmouseover="Theme.hoverDiv(this, 1)" onmouseout="Theme.hoverDiv(this, 0)"><div class="tl_left" style="padding-left:'.($intMargin + $intSpacing).'px">';

        $session[$node][$id] = is_numeric($session[$node][$id]) ? $session[$node][$id] : 0;
        $level = ($intMargin / $intSpacing + 1);
        $blnIsOpen = ($session[$node][$id] === 1 || in_array($id, $this->arrNodes, true));

        if (!empty($childs)) {
            $img = $blnIsOpen ? 'folMinus.gif' : 'folPlus.gif';
            $alt = $blnIsOpen ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
            $return .= '<a href="'.$this->addToUrl($flag.'tg='.$id).'" title="'.specialchars($alt).'" onclick="return TreePicker.toggle(this,\''.$xtnode.'_'.$id.'\',\''.$this->strField.'\',\''.$this->strName.'\','.$level.')">'.\Image::getHtml($img, '', 'style="margin-right:2px"').'</a>';
        }

        $label = TreePickerHelper::generateItemLabel($objItem, $this->foreignTable, $this->objDca);

        // Add the current item
        if (!empty($childs)) {
            $return .= '<a href="'.$this->addToUrl('node='.$objItem->id).'" title="'.specialchars(strip_tags($label)).'">'.$label.'</a></div> <div class="tl_right">';
        } else {
            $return .= '<span style="margin-left:'.$intSpacing.'px;"></span>'.$label.'</div> <div class="tl_right">';
        }

        $showInput = true;

        // HOOK: toggle input visibility
        if (isset($GLOBALS['TL_HOOKS']['widgetTreePickerToggleInput']) && is_array($GLOBALS['TL_HOOKS']['widgetTreePickerToggleInput'])) {
            foreach ($GLOBALS['TL_HOOKS']['widgetTreePickerToggleInput'] as $callback) {
                $this->import($callback[0]);
                $showInput = $this->{$callback[0]}->{$callback[1]}($objItem, $this);
            }
        }

        if ($showInput && (!$GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['parentsUnselectable'] || $this->isLeaf($objItem))) {
            // Add checkbox or radio button
            switch ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['fieldType']) {
                case 'checkbox':
                    $input = '<input type="checkbox" name="'.$this->strName.'[]" id="'.$this->strName.'_'.$id.'" class="tl_tree_checkbox" value="'.specialchars($id).'" onfocus="Backend.getScrollOffset()"'.($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['selectParents'] ? ' onclick="TreePicker.selectParents(this)"' : '').static::optionChecked($id, $this->varValue).'>';

                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['addPrimaryCategory']) {
                        $input = '<input style="margin-right: 73px" type="radio" name="'.$this->strName.Category::PRIMARY_CATEGORY_SUFFIX.'" id="'.$this->strName.Category::PRIMARY_CATEGORY_SUFFIX.'_'.$id.'" class="tl_tree_radio" value="'.specialchars($id).'" onfocus="Backend.getScrollOffset()"'.($this->primaryCategory === $id ? ' checked' : '').'>'.$input;
                    }
                    break;

                default:
                case 'radio':
                    $input = '<input type="radio" name="'.$this->strName.'" id="'.$this->strName.'_'.$id.'" class="tl_tree_radio" value="'.specialchars($id).'" onfocus="Backend.getScrollOffset()"'.static::optionChecked($id, $this->varValue).'>';
                    break;
            }

            // HOOK: modify input markup
            if (isset($GLOBALS['TL_HOOKS']['widgetTreePickerModifyInput']) && is_array($GLOBALS['TL_HOOKS']['widgetTreePickerModifyInput'])) {
                foreach ($GLOBALS['TL_HOOKS']['widgetTreePickerModifyInput'] as $callback) {
                    $this->import($callback[0]);
                    $input = $this->{$callback[0]}->{$callback[1]}($input, $objItem, $this);
                }
            }

            $return .= $input;
        }

        $return .= '</div><div style="clear:both"></div></li>';

        // Begin a new submenu
        if (!empty($childs) && ($blnIsOpen || '' !== $this->Session->get($this->getSearchSessionKey()))) {
            $return .= '<li class="parent" id="'.$node.'_'.$id.'"><ul class="level_'.$level.'">';

            for ($k = 0, $c = count($childs); $k < $c; ++$k) {
                $return .= $this->renderItemTree($childs[$k], ($intMargin + $intSpacing));
            }

            $return .= '</ul></li>';
        }

        return $return;
    }

    /**
     * Checks if an item is a leaf, i.e. there's no item that j.
     *
     * @param $objItem
     *
     * @return bool
     */
    protected function isLeaf($objItem)
    {
        $objChildren = $this->Database->prepare("SELECT id FROM $this->foreignTable WHERE pid=?")->execute($objItem->id);

        return $objChildren->numRows <= 0;
    }

    /**
     * Get the IDs of all parent items of the selected items, so they are expanded automatically.
     */
    protected function getPathNodes()
    {
        if (!$this->varValue) {
            return;
        }

        if (!is_array($this->varValue)) {
            $this->varValue = [$this->varValue];
        }

        foreach ($this->varValue as $id) {
            $arrPids = $this->Database->getParentRecords($id, $this->foreignTable);
            array_shift($arrPids); // the first element is the ID of the item itself
            $this->arrNodes = array_merge($this->arrNodes, $arrPids);
        }
    }
}
