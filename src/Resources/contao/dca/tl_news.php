<?php

$dca = &$GLOBALS['TL_DCA']['tl_news'];

/**
 * Config
 */
$dca['config']['onload_callback']['generateFeed_huhCategories'] = [\HeimrichHannot\CategoriesBundle\DataContainer\NewsContainer::class, 'generateFeeds'];
