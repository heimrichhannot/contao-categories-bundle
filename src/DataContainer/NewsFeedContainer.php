<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\DataContainer;

class NewsFeedContainer
{
    /**
     * @var NewsContainer
     */
    private $newsContainer;

    public function __construct(NewsContainer $newsContainer)
    {
        $this->newsContainer = $newsContainer;
    }

    public function generateFeed()
    {
        $this->newsContainer->generateFeeds();
    }
}
