<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\DataContainer;

use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Environment;
use Contao\Feed;
use Contao\Date;
use Contao\Config;
use Contao\FeedItem;
use Contao\ContentModel;
use Contao\FilesModel;
use Contao\File;
use Contao\ArticleModel;
use Contao\NewsFeedModel;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\UtilsBundle\Util\Utils;

class NewsContainer
{
    public function __construct(private readonly InsertTagParser $parser, private readonly Utils $utils)
    {
    }

    public function generateFeeds(): void
    {
        if (!class_exists('Contao\NewsFeedModel')) {
            return;
        }

        $objFeed = NewsFeedModel::findAll();

        if (null !== $objFeed) {
            while ($objFeed->next()) {
                $objFeed->feedName = $objFeed->alias ?: 'news'.$objFeed->id;
                $this->generateFiles($objFeed->row());
                System::getContainer()->get('monolog.logger.contao')->log(LogLevel::INFO, 'Generated news feed "'.$objFeed->feedName.'.xml"', ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]);
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
    public function generateFiles($arrFeed): void
    {
        $arrArchives = StringUtil::deserialize($arrFeed['archives']);
        $modelUtil = $this->utils->model();

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

        $arrFields = array_map(fn($val) => '"'.$val.'"', $arrFields);

        $strType = ('atom' == $arrFeed['format']) ? 'generateAtom' : 'generateRss';
        $strLink = $arrFeed['feedBase'] ?: Environment::get('base');
        $strFile = $arrFeed['feedName'];

        $objFeed = new Feed($strFile);
        $objFeed->link = $strLink;
        $objFeed->title = $arrFeed['title'];
        $objFeed->description = $arrFeed['description'];
        $objFeed->language = $arrFeed['language'];
        $objFeed->published = $arrFeed['tstamp'];

        $db = Database::getInstance();

        // Get the items
        $time = Date::floorToMinute();

        $query = 'SELECT n.* FROM tl_news n INNER JOIN tl_category_association a ON n.id = a.entity WHERE a.categoryField IN ('.implode(',', $arrFields).')'.
                    ' AND a.category IN ('.implode(',', $arrCategories).') AND n.pid IN ('.implode(',', $arrArchives).')'.
                    " AND (n.start='' OR n.start<='$time') AND (n.stop='' OR n.stop>'".($time + 60)."') AND n.published='1' GROUP BY n.id ORDER BY date DESC";

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
                if (null === ($archive = $modelUtil->findModelInstanceByPk('tl_news_archive', $objArticle->pid))) {
                    continue;
                }

                $jumpTo = $archive->jumpTo;

                // No jumpTo page set (see #4784)
                if (!$jumpTo) {
                    continue;
                }

                // Get the jumpTo URL
                if (!isset($arrUrls[$jumpTo])) {
                    $objParent = PageModel::findWithDetails($jumpTo);

                    // A jumpTo page is set but does no longer exist (see #5781)
                    if (null === $objParent) {
                        $arrUrls[$jumpTo] = false;
                    } else {
                        $arrUrls[$jumpTo] = $objParent->getAbsoluteUrl((Config::get('useAutoItem') && !Config::get('disableAlias')) ? '/%s' : '/items/%s');
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

                    if (null !== ($objCategories = $modelUtil->findMultipleModelInstancesByIds('tl_category', $ids))) {
                        $arrCategories = $objCategories->fetchEach('title');
                    }
                }

                $strUrl = $arrUrls[$jumpTo];
                $objItem = new FeedItem();

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
                    $objElement = ContentModel::findPublishedByPidAndTable($objArticle->id, 'tl_news');

                    if (null !== $objElement) {
                        // Overwrite the request (see #7756)
                        $strRequest = Environment::get('request');
                        Environment::set('request', $objItem->link);

                        while ($objElement->next()) {
                            $strDescription .= Controller::getContentElement($objElement->current());
                        }

                        Environment::set('request', $strRequest);
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

                $strDescription = $this->parser->replace($strDescription);
                $objItem->description = Controller::convertRelativeUrls($strDescription, $strLink);

                // Add the article image as enclosure
                if ($objArticle->addImage) {
                    $objFile = FilesModel::findByUuid($objArticle->singleSRC);

                    if (null !== $objFile) {
                        $objItem->addEnclosure($objFile->path, $strLink);
                    }
                }

                // Enclosures
                if ($objArticle->addEnclosure) {
                    $arrEnclosure = StringUtil::deserialize($objArticle->enclosure, true);

                    if (\is_array($arrEnclosure)) {
                        $objFile = FilesModel::findMultipleByUuids($arrEnclosure);

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
        if (class_exists(ContaoCoreBundle::class)) {
            File::putContent('web/share/'.$strFile.'.xml', $this->parser->replace($objFeed->$strType()));
        } else {
            File::putContent('share/'.$strFile.'.xml', $this->parser->replace($objFeed->$strType()));
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
                if (null !== ($objArticle = ArticleModel::findByPk($objItem->articleId, ['eager' => true])) && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel) {
                    /* @var PageModel $objPid */
                    return StringUtil::ampersand($objPid->getAbsoluteUrl('/articles/'.($objArticle->alias ?: $objArticle->id)));
                }

                break;
        }

        // Backwards compatibility (see #8329)
        if ('' != $strBase && !preg_match('#^https?://#', (string) $strUrl)) {
            $strUrl = $strBase.$strUrl;
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', (string) $strUrl), ($objItem->alias ?: $objItem->id));
    }
}
