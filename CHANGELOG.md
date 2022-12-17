# Changelog

All notable changes to this project will be documented in this file.

## [1.4.4] - 2022-12-17

- Fixed: php8 issues

## [1.4.3] - 2022-12-17

- Fixed: implicit dependency to news bundle in `tl_news_feed`

## [1.4.2] - 2022-11-04

Same as 1.4.1

## [1.4.1] - 2022-11-04

- Fixed: palette handling for tl_page
- Fixed: missing translations for tl_page fields
- Fixed: warning in CategoryTree widget ([#16])

## [1.4.0] - 2022-02-28

- Added: rootNodesUnselectable eval option
- Changed: minimum php version is now 7.4
- Changed: raised minimum utils bundle version
- Changed: removed unnecessary composer.json content
- Changed: enhanced technical introductions
- Changed: some small refactoring
- Fixed: some old namespaces and deprecations
- Fixed: added some missing translations

## [1.3.2] - 2022-02-15

- Fixed: array index issues in php 8+

## [1.3.1] - 2022-02-15

- Fixed: array index issues in php 8+

## [1.3.0] - 2022-01-13
- Changed: allow wa72/htmlpagedom v2

## [1.2.1] - 2022-01-12
- Fixed: service not public

## [1.2.0] - 2021-10-11

- Added: php8 support

## [1.1.3] - 2021-04-27

- added `doNotCopy` to category fields because else it might not be in sync with the association entity

## [1.1.2] - 2021-04-27

- fixed dependencies
- added loose coupling for NewsFeedModel

## [1.1.1] - 2020-12-11

- Fix categories sorting for categories menu module (#11)
- Fix calling urls with umlauts (#11)
- Fix missing field and palette problem in contao 4.9 (#9)

## [1.1.0] - 2020-12-01

- added parent category filter type
- added CategoryModel::getChildCategories()
- added CategoryModel::hasChildCategories()
- added CategoryModel::getDescendantCategories()
- added CategoryModel annotation
- moved filter config element dca config to loadDataContainer hook
- update some imports in CategoryManager

## [1.0.3] - 2020-08-25

- fixed save issue if category is multilingual

## [1.0.2] - 2020-07-23

- fixed inputType for `categories_default` for user and user group permissions

## [1.0.1] - 2020-06-30

- fixed request token issue for contao 4.9

## [1.0.0] - 2020-06-22

- added ondelete_callback in order to remove associations of a deleted category


[#16]: https://github.com/heimrichhannot/contao-categories-bundle/pull/16
