<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\DataContainer;
use Contao\Environment;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\CategoriesBundle\Manager\CategoryManager;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\CategoriesBundle\Widget\CategoryTree;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class HookListener
{
    /**
     * @var Request
     */
    private $request;
    private Utils           $utils;
    private CategoryManager $categoryManager;

    /**
     * HookListener constructor.
     */
    public function __construct(Request $request, Utils $utils, CategoryManager $categoryManager)
    {
        $this->request = $request;
        $this->utils = $utils;
        $this->categoryManager = $categoryManager;
    }

    public function adjustCategoryTree($buffer, $template)
    {
        if (!$this->request->getGet('picker') || !($field = $this->request->getGet('category_field')) || !($table = $this->request->getGet('category_table'))) {
            return $buffer;
        }

        $dcaEval = $GLOBALS['TL_DCA'][$table]['fields'][$field]['eval'];

        // hide unselectable checkboxes
        if ($dcaEval['parentsUnselectable']) {
            $selectedableCategories = [];

            if (null !== ($categories = System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_category', ['tl_category.selectable=?'], [true]))) {
                $selectedableCategories = $categories->fetchEach('id');
            }

            $buffer = $this->hideUnselectableCheckboxes($buffer, $selectedableCategories);
        }

        if ($dcaEval['rootNodesUnselectable'] && (isset($dcaEval['rootNodes']) && !empty($dcaEval['rootNodes']) && \is_array($dcaEval['rootNodes']))) {
            $rootNodes = $this->utils->model()->findMultipleModelInstancesByIds(CategoryModel::getTable(), $dcaEval['rootNodes']);

            if ($rootNodes) {
                $buffer = $this->hideUnselectableRadioInputs($buffer, $rootNodes->fetchEach('id'));
            }
        }

        return $buffer;
    }

    public function reloadCategoryTree($action, DataContainer $dc)
    {
        switch ($action) {
            case 'reloadCategoryTree':
                $id = $this->request->getGet('id');
                $field = $dc->inputName = $this->request->getPost('name');

                // Handle the keys in "edit multiple" mode
                if ('editAll' === $this->request->getGet('act')) {
                    $id = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', $field);
                    $field = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', $field);
                }

                $dc->field = $field;

                // The field does not exist
                if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field])) {
                    System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, 'Field "'.$field.'" does not exist in DCA "'.$dc->table.'"', ['contao' => new ContaoContext(__METHOD__, TL_ERROR)]);

                    throw new BadRequestHttpException('Bad request');
                }

                $row = null;
                $value = null;

                // Load the value
                if ('overrideAll' !== $this->request->getGet('act')) {
                    if ('File' === $GLOBALS['TL_DCA'][$dc->table]['config']['dataContainer']) {
                        $value = Config::get($field);
                    } elseif ($id > 0 && Database::getInstance()->tableExists($dc->table)) {
                        $row = Database::getInstance()->prepare('SELECT * FROM '.$dc->table.' WHERE id=?')->execute($id);

                        // The record does not exist
                        if ($row->numRows < 1) {
                            System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, 'A record with the ID "'.$id.'" does not exist in table "'.$dc->table.'"', ['contao' => new ContaoContext(__METHOD__, TL_ERROR)]);

                            throw new BadRequestHttpException('Bad request');
                        }

                        $value = $row->$field;
                        $dc->activeRecord = $row;
                    }
                }

                // Call the load_callback
                if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['load_callback'])) {
                    foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['load_callback'] as $callback) {
                        if (\is_array($callback)) {
                            $callbackObj = System::importStatic($callback[0]);
                            $value = $callbackObj->{$callback[1]}($value, $dc);
                        } elseif (\is_callable($callback)) {
                            $value = $callback($value, $dc);
                        }
                    }
                }

                // Set the new value
                $value = $this->request->getPost('value', true);
                $key = 'categoryTree';

                // Convert the selected values
                if ('' !== $value) {
                    $value = StringUtil::trimsplit("\t", $value);

                    $value = serialize($value);
                }

                /** @var CategoryTree $strClass */
                $strClass = $GLOBALS['BE_FFL'][$key];

                /** @var CategoryTree $objWidget */
                $objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$field], $dc->inputName, $value, $field, $dc->table, $dc));

                throw new ResponseException(new Response(static::replaceOldBePaths($objWidget->generate())));
        }
    }

    protected function hideUnselectableCheckboxes(string $buffer, array $selectableCategories = [])
    {
        $objNode = new HtmlPageCrawler($buffer);

        $objNode->filter('.tree_view input[name="picker[]"]')->each(function ($objElement) use ($selectableCategories) {
            $categoryId = $objElement->getAttribute('value');

            if (\System::getContainer()->get('huh.categories.manager')->hasChildren($categoryId) && !\in_array($categoryId, $selectableCategories)) {
                $objElement->replaceWith('<div class="dummy" style="display: inline-block; width: 22px; height: 13px;"></div>');
            }
        });

        $objNode->filter('.tree_view input[name="primaryCategory"]')->each(function ($objElement) {
            /** @var HtmlPageCrawler $objElement */
            $category = $objElement->getAttribute('data-id');

            if ($this->categoryManager->hasChildren($category)) {
                $objElement->removeAttribute('checked');
                $objElement->siblings()->first()->setAttribute('style', 'opacity: 0 !important');
            }
        });

        return $objNode->saveHTML();
    }

    protected function hideUnselectableRadioInputs(string $buffer, array $categoriesToHide = [])
    {
        $objNode = new HtmlPageCrawler($buffer);

        $objNode->filter('.tree_view input.tl_tree_radio[name="picker"]')->each(function ($objElement) use ($categoriesToHide) {
            $categoryId = $objElement->getAttribute('value');

            if (\in_array($categoryId, $categoriesToHide)) {
                $objElement->replaceWith('<div class="dummy" style="display: inline-block; width: 22px; height: 13px;"></div>');
            }
        });

        return $objNode->saveHTML();
    }

    /**
     * Protected function taken from \Contao\Controller.
     */
    protected static function replaceOldBePaths($strContext)
    {
        $router = System::getContainer()->get('router');

        $generate = function ($route) use ($router) {
            return substr($router->generate($route), \strlen(Environment::get('path')) + 1);
        };

        $arrMapper = [
            'contao/confirm.php' => $generate('contao_backend_confirm'),
            'contao/file.php' => $generate('contao_backend_file'),
            'contao/help.php' => $generate('contao_backend_help'),
            'contao/index.php' => $generate('contao_backend_login'),
            'contao/main.php' => $generate('contao_backend'),
            'contao/page.php' => $generate('contao_backend_page'),
            'contao/password.php' => $generate('contao_backend_password'),
            'contao/popup.php' => $generate('contao_backend_popup'),
            'contao/preview.php' => $generate('contao_backend_preview'),
            'contao/switch.php' => $generate('contao_backend_switch'),
        ];

        return str_replace(array_keys($arrMapper), array_values($arrMapper), $strContext);
    }
}
