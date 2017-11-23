<?php

namespace HeimrichHannot\CategoriesBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use HeimrichHannot\CategoriesBundle\CategoriesBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(CategoriesBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, 'blocks'])
        ];
    }
}

