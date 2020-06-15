<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\DataContainer;

use Contao\Controller;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

class NewsContainer
{
    /**
     * @var \HeimrichHannot\UtilsBundle\String\StringUtil
     */
    private $stringUtil;
    /**
     * @var ModelUtil
     */
    private $modelUtil;

    public function __construct(\HeimrichHannot\UtilsBundle\String\StringUtil $stringUtil, ModelUtil $modelUtil)
    {
        $this->stringUtil = $stringUtil;
        $this->modelUtil = $modelUtil;
    }

    public function generateFeeds()
    {
        $objFeed = \NewsFeedModel::findAll();

        if (null !== $objFeed) {
            while ($objFeed->next()) {
                $objFeed->feedName = $objFeed->alias ?: 'news'.$objFeed->id;
                $this->generateFiles($objFeed->row());
                System::log('Generated news feed "'.$objFeed->feedName.'.xml"', __METHOD__, TL_CRON);
            }
        }
    }

    /**
     * Adapted from \NewsCategories\News.
     *
     * @param $arrFeed
     *
     * @throws \Exception
     */
    public function generateFiles($arrFeed)
    {
        $arrArchives = StringUtil::deserialize($arrFeed['archives']);

        if (!\is_array($arrArchives) || empty($arrArchives)) {
            return;
        }

        $arrCategories = StringUtil::deserialize($arrFeed['huhCategories']);

        if (!\is_array($arrCategories) || empty($arrCategories)) {
            return;
        }

        $arrFields = StringUtil::deserialize($arrFeed['huhCategoriesFields']);

        if (!\is_array($arrFields) || empty($arrFields)) {
            return;
        }

        $arrFields = array_map(function ($val) {
            return '"'.$val.'"';
        }, $arrFields);

        $strType = ('atom' == $arrFeed['format']) ? 'generateAtom' : 'generateRss';
        $strLink = $arrFeed['feedBase'] ?: \Environment::get('base');
        $strFile = $arrFeed['feedName'];

        $objFeed = new \Feed($strFile);
        $objFeed->link = $strLink;
        $objFeed->title = $arrFeed['title'];
        $objFeed->description = $arrFeed['description'];
        $objFeed->language = $arrFeed['language'];
        $objFeed->published = $arrFeed['tstamp'];

        $db = Database::getInstance();

        // Get the items
        $time = \Date::floorToMinute();

        $query = 'SELECT * FROM tl_news n INNER JOIN tl_category_association a ON n.id = a.entity WHERE a.categoryField IN ('.implode(',', $arrFields).')'.
                    ' AND a.category IN ('.implode(',', $arrCategories).') AND n.pid IN ('.implode(',', $arrArchives).')'.
                    " AND (n.start='' OR n.start<='$time') AND (n.stop='' OR n.stop>'".($time + 60)."') AND n.published='1'";

        if ($arrFeed['maxItems'] > 0) {
            $query .= ' LIMIT '.$arrFeed['maxItems'];

            $objArticle = $db->execute($query);
        } else {
            $objArticle = $db->execute($query);
        }

        // Parse the items
        if ($objArticle->numRows > 0) {
            $arrUrls = [];

            while ($objArticle->next()) {
                if (null === ($archive = $this->modelUtil->findModelInstanceByPk('tl_news_archive', $objArticle->pid))) {
                    continue;
                }

                $jumpTo = $archive->jumpTo;

                // No jumpTo page set (see #4784)
                if (!$jumpTo) {
                    continue;
                }

                // Get the jumpTo URL
                if (!isset($arrUrls[$jumpTo])) {
                    $objParent = \PageModel::findWithDetails($jumpTo);

                    // A jumpTo page is set but does no longer exist (see #5781)
                    if (null === $objParent) {
                        $arrUrls[$jumpTo] = false;
                    } else {
                        $arrUrls[$jumpTo] = $objParent->getAbsoluteUrl((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s');
                    }
                }

                // Skip the event if it requires a jumpTo URL but there is none
                if (false === $arrUrls[$jumpTo] && 'default' == $objArticle->source) {
                    continue;
                }

                // Get the categories
                if ($arrFeed['categories_show']) {
                    $arrCategories = [];
                    $ids = StringUtil::deserialize($objArticle->categories, true);

                    if (null !== ($objCategories = $this->modelUtil->findMultipleModelInstancesByIds('tl_category', $ids))) {
                        $arrCategories = $objCategories->fetchEach('title');
                    }
                }

                $strUrl = $arrUrls[$jumpTo];
                $objItem = new \FeedItem();

                // Add the categories to the title
                if ('title' == $arrFeed['categories_show']) {
                    $objItem->title = sprintf('[%s] %s', implode(', ', $arrCategories), $objArticle->headline);
                } else {
                    $objItem->title = $objArticle->headline;
                }

                $objItem->link = $this->getLink($objArticle, $strUrl);
                $objItem->published = $objArticle->date;
                $objItem->author = $objArticle->authorName;

                // Prepare the description
                if ('source_text' == $arrFeed['source']) {
                    $strDescription = '';
                    $objElement = \ContentModel::findPublishedByPidAndTable($objArticle->id, 'tl_news');

                    if (null !== $objElement) {
                        // Overwrite the request (see #7756)
                        $strRequest = \Environment::get('request');
                        \Environment::set('request', $objItem->link);

                        while ($objElement->next()) {
                            $strDescription .= Controller::getContentElement($objElement->current());
                        }

                        \Environment::set('request', $strRequest);
                    }
                } else {
                    $strDescription = $objArticle->teaser;
                }

                // Add the categories to the description
                if ('text_before' == $arrFeed['categories_show'] || 'text_after' == $arrFeed['categories_show']) {
                    $strCategories = '<p>'.$GLOBALS['TL_LANG']['MSC']['newsCategories'].' '.implode(', ', $arrCategories).'</p>';

                    if ('text_before' == $arrFeed['categories_show']) {
                        $strDescription = $strCategories.$strDescription;
                    } else {
                        $strDescription .= $strCategories;
                    }
                }

                $strDescription = $this->stringUtil->replaceInsertTags($strDescription, false);
                $objItem->description = Controller::convertRelativeUrls($strDescription, $strLink);

                // Add the article image as enclosure
                if ($objArticle->addImage) {
                    $objFile = \FilesModel::findByUuid($objArticle->singleSRC);

                    if (null !== $objFile) {
                        $objItem->addEnclosure($objFile->path, $strLink);
                    }
                }

                // Enclosures
                if ($objArticle->addEnclosure) {
                    $arrEnclosure = StringUtil::deserialize($objArticle->enclosure, true);

                    if (\is_array($arrEnclosure)) {
                        $objFile = \FilesModel::findMultipleByUuids($arrEnclosure);

                        if (null !== $objFile) {
                            while ($objFile->next()) {
                                $objItem->addEnclosure($objFile->path, $strLink);
                            }
                        }
                    }
                }

                $objFeed->addItem($objItem);
            }
        }

        // Create the file
        if (class_exists('Contao\CoreBundle\ContaoCoreBundle')) {
            \File::putContent('web/share/'.$strFile.'.xml', $this->stringUtil->replaceInsertTags($objFeed->$strType(), false));
        } else {
            \File::putContent('share/'.$strFile.'.xml', $this->stringUtil->replaceInsertTags($objFeed->$strType(), false));
        }
    }

    /**
     * Taken from \Contao\News.
     *
     * @param $objItem
     * @param $strUrl
     * @param string $strBase
     *
     * @throws \Exception
     *
     * @return string|string[]|null
     */
    protected function getLink($objItem, $strUrl, $strBase = '')
    {
        switch ($objItem->source) {
            // Link to an external page
            case 'external':
                return $objItem->url;

                break;

            // Link to an internal page
            case 'internal':
                if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel) {
                    /* @var PageModel $objTarget */
                    return $objTarget->getAbsoluteUrl();
                }

                break;

            // Link to an article
            case 'article':
                if (null !== ($objArticle = \ArticleModel::findByPk($objItem->articleId, ['eager' => true])) && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel) {
                    /* @var PageModel $objPid */
                    return ampersand($objPid->getAbsoluteUrl('/articles/'.($objArticle->alias ?: $objArticle->id)));
                }

                break;
        }

        // Backwards compatibility (see #8329)
        if ('' != $strBase && !preg_match('#^https?://#', $strUrl)) {
            $strUrl = $strBase.$strUrl;
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
    }
}
