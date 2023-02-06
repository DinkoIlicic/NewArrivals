<?php

declare(strict_types=1);

namespace Dinko\NewArrivals\Model\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class Tree
{
    /**
     * @var null|Collection
     */
    protected ?Collection $collection = null;

    /**
     * Tree constructor.
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(protected CollectionFactory $categoryCollectionFactory)
    {
    }

    /**
     * Get Categories Tree
     *
     * @param int $rootId
     * @param bool $onlyActive
     * @param int[] $excludedIds
     * @return array
     */
    public function getCategoriesTree( //@codingStandardsIgnoreLine cyclomatic complexity
        int $rootId = Category::TREE_ROOT_ID,
        bool $onlyActive = false,
        array $excludedIds = []
    ): array {
        try {
            $collection = $this->getCategoryCollection();
        } catch (LocalizedException $e) {
            return [];
        }

        // root category
        $categoryTreeById = [
            Category::TREE_ROOT_ID => [
                'entity_id' => Category::TREE_ROOT_ID,
                'children'  => []
            ]
        ];

        $categories = []; // filtered categories
        foreach ($collection as $item) {
            $categoryId = (int)$item->getId();

            if ($onlyActive && !$item->getIsActive()) {
                continue; // exclude disabled categories
            }
            if ($excludedIds && in_array($categoryId, $excludedIds, true)) {
                continue;
            }

            $categories[$categoryId] = $item;
        }

        foreach ($categories as $category) {
            $categoryId = (int)$category->getId();
            $parentId   = (int)$category->getParentId();

            // don't include categories which parents arent available
            if (!isset($categoryTreeById[$parentId]) && !isset($categories[$parentId])) {
                continue;
            }

            // assign category data to tree
            foreach ($category->getData() as $key => $value) {
                $categoryTreeById[$categoryId][$key] = $value;
            }

            // request_path is available only for categories from current store
            $categoryTreeById[$categoryId]['url'] = $category->getRequestPath() ? $category->getUrl() : '';

            // for cases when collection isn't ordered by level
            if (!isset($categoryTreeById[$parentId])) {
                $categoryTreeById[$parentId] = ['entity_id' => $parentId];
            }

            // children are assigned by reference
            $categoryTreeById[$parentId]['children'][] = &$categoryTreeById[$categoryId];
        }

        return $categoryTreeById[$rootId]['children'] ?? [];
    }

    /**
     * Get collection
     *
     * @return Collection
     * @throws LocalizedException
     */
    protected function getCategoryCollection(): Collection
    {
        if (!$this->collection) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'is_active']);
            $collection->addUrlRewriteToResult();
            $collection->addAttributeToFilter('entity_id', ['neq' => Category::TREE_ROOT_ID]);
            $collection->setOrder(['level', 'position']);

            $this->collection = $collection;
        }

        return $this->collection;
    }
}
