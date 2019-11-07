# Guide: News categories

This is an example how to add an categories field to your news entity: 

```php
<?php
// src/Resources/contao/dca/tl_news.php

$dca = &$GLOBALS['TL_DCA']['tl_news'];
$dca['palettes']['default'] = str_replace('time', 'time;{categories_legend},categories;', $dca['palettes']['default']);

\HeimrichHannot\CategoriesBundle\Backend\Category::addMultipleCategoriesFieldToDca(
    'tl_news',
    'categories',
    [
        'addPrimaryCategory'  => false,
        'mandatory'           => false,
        'parentsUnselectable' => true
    ]
);

```