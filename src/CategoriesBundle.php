<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use HeimrichHannot\CategoriesBundle\DependencyInjection\CategoriesExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CategoriesBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): CategoriesExtension|ExtensionInterface|null
    {
        return new CategoriesExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
