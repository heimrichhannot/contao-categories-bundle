<?php

namespace HeimrichHannot\CategoriesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Terminal42\DcMultilingualBundle\Driver;
use Terminal42\DcMultilingualBundle\Model\MultilingualTrait;

class DcMultilingualListener
{
    #[AsHook('loadDataContainer')]
    public function onLoadDataContainer(string $table): void
    {
        if ($table !== 'tl_category') {
            return;
        }

        if (!trait_exists(MultilingualTrait::class)) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA'][$table];
        if (empty($dca['config']['languages'])) {
            return;
        }

        $dca['config']['dataContainer'] = Driver::class;
        $dca['config']['langPid'] = 'langPid';
        $dca['config']['langColumnName'] = 'language';
        if (!isset($dca['config']['fallbackLang'])) {
            $dca['config']['fallbackLang'] = 'en';
        }
        $dca['config']['sql']['keys']['alias,language'] = 'index';


        $dca['config']['sql']['keys']['langPid'] = 'index';
        $dca['config']['sql']['keys']['language'] = 'index';
        $dca['fields']['langPid']['sql'] = "int(10) unsigned NOT NULL default '0'";
        $dca['fields']['language']['sql'] = "varchar(2) NOT NULL default ''";

        $dca['fields']['title']['eval']['translatableFor'] = '*';
        $dca['fields']['frontendTitle']['eval']['translatableFor'] = '*';

        $dca['fields']['alias']['eval']['translatableFor']          = '*';
        $dca['fields']['alias']['eval']['isMultilingualAlias']      = true;
        $dca['fields']['alias']['eval']['generateAliasFromField']   = 'title';
        unset($dca['fields']['alias']['save_callback']);
    }
}