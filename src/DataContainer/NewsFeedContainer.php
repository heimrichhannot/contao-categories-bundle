<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\DataContainer;

class NewsFeedContainer
{
    public function __construct(private readonly NewsContainer $newsContainer)
    {
    }

    public function generateFeed(): void
    {
        $this->newsContainer->generateFeeds();
    }
}
