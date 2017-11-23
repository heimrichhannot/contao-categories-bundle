<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
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
