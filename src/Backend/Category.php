<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\PageModel;
use Contao\RequestToken;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class Category extends Backend
{
    const PRIMARY_CATEGORY_SUFFIX = '_primary';

    protected static $defaultPrimaryCategorySet = false;

    /**
     * Add a breadcrumb menu to the page tree.
     *
     * @throws AccessDeniedException
     * @throws \RuntimeException
     */
    public function addBreadcrumb()
    {
        $strKey = 'tl_category_node';

        /** @var AttributeBagInterface $objSession */
        $objSession = \System::getContainer()->get('session')->getBag('contao_backend');

        // Set a new node
        if (System::getContainer()->get('huh.request')->hasGet('cn')) {
            // Check the path
            if (\Validator::isInsecurePath(System::getContainer()->get('huh.request')->getGet('cn', true))) {
                throw new \RuntimeException('Insecure path '.System::getContainer()->get('huh.request')->getGet('cn', true));
            }

            $objSession->set($strKey, System::getContainer()->get('huh.request')->getGet('cn', true));
            \Controller::redirect(preg_replace('/&cn=[^&]*/', '', Environment::get('request')));
        }

        $intNode = $objSession->get($strKey);

        if ($intNode < 1) {
            return;
        }

        // Check the path (thanks to Arnaud Buchoux)
        if (\Validator::isInsecurePath($intNode)) {
            throw new \RuntimeException('Insecure path '.$intNode);
        }

        $arrIds = [];
        $arrLinks = [];

        // Generate breadcrumb trail
        if ($intNode) {
            $intId = $intNode;
            $objDatabase = \Database::getInstance();

            do {
                $objCategory = $objDatabase->prepare('SELECT * FROM tl_category WHERE id=?')->limit(1)->execute($intId);

                if ($objCategory->numRows < 1) {
                    // Currently selected page does not exist
                    if ($intId == $intNode) {
                        $objSession->set($strKey, 0);

                        return;
                    }

                    break;
                }

                $arrIds[] = $intId;

                // No link for the active page
                if ($objCategory->id == $intNode) {
                    $arrLinks[] = \Backend::addPageIcon($objCategory->row(), '', null, '', true).' '.$objCategory->title;
                } else {
                    $arrLinks[] = \Backend::addPageIcon($objCategory->row(), '', null, '', true).' <a href="'.\Backend::addToUrl('cn='.$objCategory->id).'" title="'.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.$objCategory->title.'</a>';
                }

                // FIXME: Implement permission check
//                if (!$objUser->isAdmin && $objUser->hasAccess($objCategory->id, 'categories'))
//                {
//                    break;
//                }

                $intId = $objCategory->pid;
            } while ($intId > 0);
        }

        // FIXME: implement permission check
//        if (!$objUser->hasAccess($arrIds, 'categories'))
//        {
//            $objSession->set($strKey, 0);
//            throw new AccessDeniedException('Categories ID ' . $intNode . ' is not available.');
//        }

        // Limit tree
        $GLOBALS['TL_DCA']['tl_category']['list']['sorting']['root'] = [$intNode];

        // Add root link
        $arrLinks[] = \Image::getHtml('pagemounts.svg').' <a href="'.\Backend::addToUrl('cn=0').'" title="'.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $arrLinks = array_reverse($arrLinks);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_category']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>'.implode(' › </li><li>', $arrLinks).'</li>
</ul>';
    }

    /**
     * Shorthand function for adding a single category field to your dca.
     *
     * @param array  $evalOverride
     * @param string $label
     *
     * @return array
     *
     * @deprecated Use Category::addSingleCategoryFieldToDca() instead
     */
    public static function getCategoryFieldDca($evalOverride = null, $label = null)
    {
        \System::loadLanguageFile('tl_category');

        $eval = [
            'tl_class' => 'w50 autoheight',
            'mandatory' => true,
            'fieldType' => 'radio',
            'isCategoryField' => true,
        ];

        if (\is_array($evalOverride)) {
            $eval = array_merge($eval, $evalOverride);
        }

        // add the deletion callback on record level
        $dca['config']['ondelete_callback'] = isset($dca['config']['ondelete_callback']) && \is_array($dca['config']['ondelete_callback']) ? $dca['config']['ondelete_callback'] : [];

        $dca['config']['ondelete_callback']['deleteEntityCategoryAssociations'] = [static::class, 'deleteEntityCategoryAssociations'];

        $data = [
            'label' => &$GLOBALS['TL_LANG']['tl_category']['category'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'categoryTree',
            'foreignKey' => 'tl_category.title',
            'load_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'loadCategoriesFromAssociations']],
            'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'storeToCategoryAssociations']],
            'eval' => $eval,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        if ($label) {
            unset($data['label']);
            $data['label'] = $label;
        }

        return $data;
    }

    /**
     * Shorthand function for adding a single category field to your dca.
     *
     * @param string $table
     * @param string $name
     * @param array  $evalOverride
     * @param string $label
     */
    public static function addSingleCategoryFieldToDca($table, $name, $evalOverride = null, $label = null)
    {
        \System::loadLanguageFile('tl_category');

        $eval = [
            'tl_class' => 'w50 autoheight clr',
            'mandatory' => true,
            'fieldType' => 'radio',
            'doNotCopy' => true,
            'isCategoryField' => true,
        ];

        if (\is_array($evalOverride)) {
            $eval = array_merge($eval, $evalOverride);
        }

        Controller::loadDataContainer($table);

        $dca = &$GLOBALS['TL_DCA'][$table];

        $dca['fields'][$name] = [
            'label' => &$GLOBALS['TL_LANG']['tl_category']['category'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'categoryTree',
            'foreignKey' => 'tl_category.title',
            'load_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'loadCategoriesFromAssociations']],
            'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'storeToCategoryAssociations']],
            'eval' => $eval,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        if ($label) {
            unset($dca['fields'][$name]['label']);
            $dca['fields'][$name]['label'] = $label;
        }

        // add the deletion callback on record level
        $dca['config']['ondelete_callback'] = isset($dca['config']['ondelete_callback']) && \is_array($dca['config']['ondelete_callback']) ? $dca['config']['ondelete_callback'] : [];

        $dca['config']['ondelete_callback']['deleteEntityCategoryAssociations'] = [static::class, 'deleteEntityCategoryAssociations'];
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
        System::loadLanguageFile('tl_category');

        $eval = [
            'tl_class' => 'w50 autoheight clr',
            'mandatory' => true,
            'doNotCopy' => true,
            'multiple' => true,
            'fieldType' => 'checkbox',
            'addPrimaryCategory' => true,
            'forcePrimaryCategory' => true,
            'isCategoryField' => true,
        ];

        if (\is_array($evalOverride)) {
            $eval = array_merge($eval, $evalOverride);
        }

        Controller::loadDataContainer($table);

        $dca = &$GLOBALS['TL_DCA'][$table];

        $dca['fields'][$name] = [
            'label' => &$GLOBALS['TL_LANG']['tl_category']['categories'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'categoryTree',
            'foreignKey' => 'tl_category.title',
            'load_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'loadCategoriesFromAssociations']],
            'save_callback' => [
                ['HeimrichHannot\CategoriesBundle\Backend\Category', 'storePrimaryCategory'],
                ['HeimrichHannot\CategoriesBundle\Backend\Category', 'storeToCategoryAssociations'],
            ],
            'eval' => $eval,
            'sql' => 'blob NULL',
        ];

        if ($label) {
            unset($dca['fields'][$name]['label']);
            $dca['fields'][$name]['label'] = $label;
        }

        if ($eval['addPrimaryCategory']) {
            $dca['fields'][$name.static::PRIMARY_CATEGORY_SUFFIX] = [
                'sql' => "int(10) unsigned NOT NULL default '0'",
            ];
        }

        // add the deletion callback on record level
        $dca['config']['ondelete_callback'] = isset($dca['config']['ondelete_callback']) && \is_array($dca['config']['ondelete_callback']) ? $dca['config']['ondelete_callback'] : [];

        $dca['config']['ondelete_callback']['deleteEntityCategoryAssociations'] = [static::class, 'deleteEntityCategoryAssociations'];
    }

    public static function deleteCachedPropertyValuesByCategoryAndProperty($value, DataContainer $dc)
    {
        if (null !== ($instance = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk($dc->table, $dc->id))) {
            $valueOld = $instance->{$dc->field};

            if ($value != $valueOld) {
                \System::getContainer()->get('huh.categories.property_cache_manager')->delete(
                    [
                        'category=?',
                        'property=?',
                    ],
                    [
                        'tl_category' === $dc->table ? $instance->id : $instance->pid,
                        $dc->field,
                    ]
                );
            }
        }

        return $value;
    }

    public static function deleteCachedPropertyValuesByCategoryAndPropertyBool($value, DataContainer $dc)
    {
        if (null !== ($instance = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk($dc->table, $dc->id))) {
            // compute name of the field being overridden
            $overrideField = lcfirst(str_replace('override', '', $dc->field));

            \System::getContainer()->get('huh.categories.property_cache_manager')->delete(
                [
                    'category=?',
                    'property=?',
                ],
                [
                    'tl_category' === $dc->table ? $instance->id : $instance->pid,
                    $overrideField,
                ]
            );
        }

        return $value;
    }

    public function getPrimarizeOperation($row, $href, $label, $title, $icon)
    {
        $checked = '';

        if (!($field = \Input::get('category_field')) || !($table = \Input::get('category_table'))) {
            return '';
        }

        if (null === ($category = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_category', $row['id']))) {
            return '';
        }

        \Controller::loadDataContainer($table);

        $dcaEval = $GLOBALS['TL_DCA'][$table]['fields'][$field]['eval'];

        if (!$dcaEval['addPrimaryCategory']) {
            return '';
        }

        $primaryCategory = System::getContainer()->get('huh.request')->getGet('primaryCategory');

        $isParentCategory = System::getContainer()->get('huh.categories.manager')->hasChildren($row['id']);
        $checkAsDefaultPrimaryCategory = (!$isParentCategory || !$dcaEval['parentsUnselectable'] || $category->selectable) && !$primaryCategory && $dcaEval['forcePrimaryCategory'] && !static::$defaultPrimaryCategorySet;

        if ($checkAsDefaultPrimaryCategory || (string) $row['id'] === \Input::get('primaryCategory')) {
            static::$defaultPrimaryCategorySet = true;
            $checked = ' checked';
        }

        return '<input type="radio" name="primaryCategory" data-id="'.$row['id'].'" id="primaryCategory_'.$row['id'].'" value="primary_'.$row['id'].'"'.$checked.'>'.'<label style="margin-right: 6px" for="primaryCategory_'.$row['id'].'" title="'.$title.'" class="primarize">'.'<span class="icon primarized">'.\Image::getHtml('bundles/categories/img/icon_primarized.png').'</span>'.'<span class="icon unprimarized">'.\Image::getHtml('bundles/categories/img/icon_unprimarized.png').'</span>'.'</label>';
    }

    /**
     * Automatically add overridable fields to the dca (including palettes, ...).
     */
    public static function addOverridableFieldSelectors()
    {
        $dca = &$GLOBALS['TL_DCA']['tl_category'];

        // add overridable fields
        foreach ($dca['fields'] as $field => $data) {
            if ($data['eval']['overridable'] ?? false) {
                $overrideFieldName = 'override'.ucfirst($field);

                // boolean field
                $dca['fields'][$overrideFieldName] = [
                    'label' => &$GLOBALS['TL_LANG']['tl_category'][$overrideFieldName],
                    'exclude' => true,
                    'inputType' => 'checkbox',
                    'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\Category', 'deleteCachedPropertyValuesByCategoryAndPropertyBool']],
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

    public function modifyDca(DataContainer $dc)
    {
        $modelUtil = System::getContainer()->get('huh.utils.model');

        $category = $modelUtil->findModelInstanceByPk('tl_category', $dc->id);
        $dca = &$GLOBALS['TL_DCA']['tl_category'];

        if ($category) {
            if ($category->pid) {
                foreach ($dca['fields'] as $field => $data) {
                    if (isset($data['eval']['overridable']) && $data['eval']['overridable']) {
                        $dca['palettes']['default'] = str_replace($field, 'override'.ucfirst($field), $dca['palettes']['default']);
                    }
                }
            }

            if (!System::getContainer()->get('huh.categories.manager')->hasChildren($category->id)) {
                unset($dca['fields']['selectable']);
            }
        }

        // hide primarize operation if not in picker context
        // show only in picker
        if (!\Input::get('picker')) {
            unset($dca['list']['operations']['primarize']);
        }
    }

    public function deleteCategoryAssociations(DataContainer $dc, $undoId)
    {
        if (!$dc->id) {
            return;
        }

        System::getContainer()->get('huh.utils.database')->delete(
            'tl_category_association', 'tl_category_association.category=?', [$dc->id]
        );
    }

    public function deleteEntityCategoryAssociations(DataContainer $dc, $undoId)
    {
        $table = $dc->table;

        if (!$table) {
            return;
        }

        Controller::loadDataContainer($table);

        // get category fields for this dca
        $categoryFields = [];
        $dca = &$GLOBALS['TL_DCA'][$table];

        foreach ($dca['fields'] as $field => $data) {
            if ('categoryTree' === ($data['inputType'] ?? '')) {
                $categoryFields[] = $field;
            }
        }

        foreach ($categoryFields as $field) {
            System::getContainer()->get('huh.categories.manager')->removeAllAssociations(
                $dc->id, $field, $table
            );
        }
    }

    /**
     * @param mixed $value
     */
    public function storePrimaryCategory($value, DataContainer $dc)
    {
        if ($primaryCategory = \Input::post($dc->field.static::PRIMARY_CATEGORY_SUFFIX)) {
            System::getContainer()->get('huh.utils.database')->update($dc->table, [
                $dc->field.static::PRIMARY_CATEGORY_SUFFIX => $primaryCategory,
            ], "$dc->table.id=?", [$dc->id]);
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    public function storeToCategoryAssociations($value, DataContainer $dc)
    {
        $manager = \System::getContainer()->get('huh.categories.manager');

        if ($value) {
            switch ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['fieldType']) {
                case 'radio':
                    $manager->createAssociations($dc->id, $dc->field, $dc->table, [$value], true);

                    break;

                case 'checkbox':
                    $manager->createAssociations($dc->id, $dc->field, $dc->table, StringUtil::deserialize($value, true), true);

                    // transform from int to string so that contao backend list filtering works
                    $value = serialize(array_map('strval', StringUtil::deserialize($value, true)));

                    break;
            }
        } else {
            $manager->removeAllAssociations($dc->id, $dc->field, $dc->table);
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return array|null
     */
    public function loadCategoriesFromAssociations($value, DataContainer $dc)
    {
        if (!$dc->id || !$dc->field) {
            return $value;
        }

        $categories = System::getContainer()->get('huh.categories.manager')->findByEntityAndCategoryFieldAndTable($dc->id, $dc->field, $dc->table);

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
     * @param string $varValue
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function generateAlias($varValue, DataContainer $dc)
    {
        if (null === ($category = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_category', $dc->id))) {
            return '';
        }

        $title = $dc->activeRecord->title ?: $category->title;

        return System::getContainer()->get('huh.utils.dca')->generateAlias($varValue, $dc->id, 'tl_category', $title);
    }

    public function checkPermission()
    {
        $user = \BackendUser::getInstance();

        if (!$user->isAdmin && !$user->hasAccess('manage', 'categories')) {
            Controller::redirect('contao/main.php?act=error');
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
        if (false !== $arrClipboard && ('cut' === $arrClipboard['mode'] && (1 === $cr || $arrClipboard['id'] === $row['id']) || 'cutAll' === $arrClipboard['mode'] && (1 === $cr || \in_array($row['id'], $arrClipboard['id'], true)))) {
            $disablePA = true;
            $disablePI = true;
        }

        $return = '';

        // Return the buttons
        $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

        if ($row['id'] > 0) {
            $return = $disablePA ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.Controller::addToUrl('act='.$arrClipboard['mode'].'&mode=1&rt='.RequestToken::get().'&pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1],
                    $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.Controller::addToUrl('act='.$arrClipboard['mode'].'&mode=2&rt='.RequestToken::get().'&pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1],
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

        // add category breadcrumb link
        $label = ' <a href="'.\Backend::addToUrl('cn='.$this->urlEncode($row['id'])).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.$label.'</a>';

        if ('edit' !== System::getContainer()->get('huh.request')->getGet('act') && null !== (System::getContainer())->get('huh.categories.config_manager')->findBy(['tl_category_config.pid=?'], [$row['id']])) {
            $label .= '<span style="padding-left:3px;color:#b3b3b3;">– '.$GLOBALS['TL_LANG']['MSC']['categoriesBundle']['configsAvailable'].'</span>';
        }

        return Image::getHtml('iconPLAIN.svg', '', $attributes).' '.$label;
    }

    /**
     * Shorthand function for adding a category filter list field to your dca.
     *
     * @param array  $evalOverride
     * @param string $label
     *
     * @return array
     */
    public static function getCategoryFilterListFieldDca($evalOverride = null, $label = null)
    {
        System::loadLanguageFile('tl_category');

        $eval = [
            'tl_class' => 'w50 autoheight',
        ];

        if (\is_array($evalOverride)) {
            $eval = array_merge($eval, $evalOverride);
        }

        $data = [
            'label' => &$GLOBALS['TL_LANG']['tl_category']['categoryFilterList'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => $eval,
            'sql' => "char(1) NOT NULL default ''",
        ];

        if ($label) {
            unset($data['label']);
            $data['label'] = $label;
        }

        return $data;
    }

    /**
     * Get the parameter name.
     *
     * @param int $rootId
     *
     * @return string
     */
    public static function getUrlParameterName($rootId = null)
    {
        if (!$rootId) {
            global $objPage;
            $rootId = $objPage->rootId;
        }

        if (!$rootId) {
            return '';
        }
        $rootPage = PageModel::findByPk($rootId);

        if (null === $rootPage) {
            return '';
        }

        return $rootPage->categoriesParam ?: 'category';
    }
}
