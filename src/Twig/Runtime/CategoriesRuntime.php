<?php

namespace HeimrichHannot\CategoriesBundle\Twig\Runtime;

use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Manager\CategoryManager;
use Twig\Extension\RuntimeExtensionInterface;

class CategoriesRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly CategoryManager $categoryManager,
    )
    {
    }

    /**
     * Get the category for a given category id.
     */
    public function getCategory(int $id): ?array
    {
        $category = $this->categoryManager->findByIdOrAlias($id);

        if (null === $category) {
            return null;
        }

        return $category->row();
    }

    /**
     * Get the category for a given category id.
     */
    public function getCategories(array|string $ids): ?array
    {
        $ids = StringUtil::deserialize($ids, true);

        if (empty($ids)) {
            return [];
        }

        $categories = $this->categoryManager->findMultipleByIds($ids);

        if (null === $categories) {
            return null;
        }

        return $categories->fetchAll();
    }

    /**
     * Get the category for a given category id taking into account the contextual (overridable) properties -> see README.md for more detail.
     */
    public function getContextualCategory(int|string $id, $contextObj, string $categoryField, int $primaryCategory, bool $skipCache = false): ?array
    {
        $category = $this->categoryManager->findByIdOrAlias($id);

        if (null === $category) {
            return null;
        }

        $this->categoryManager->addOverridablePropertiesToCategory($category, $contextObj, $categoryField, $primaryCategory, $skipCache);

        return $category->row();
    }
}