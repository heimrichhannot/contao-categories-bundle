<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle;

use HeimrichHannot\CategoriesBundle\DependencyInjection\CategoriesExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CategoriesBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new CategoriesExtension();
    }
}
