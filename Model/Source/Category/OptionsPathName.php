<?php

declare(strict_types=1);

namespace Dinko\NewArrivals\Model\Source\Category;

use Dinko\NewArrivals\Model\Category\Tree;
use Magento\Framework\Data\OptionSourceInterface;

class OptionsPathName implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected array $options = [];

    /**
     * OptionsPathName constructor.
     * @param Tree $categoryTree
     */
    public function __construct(protected Tree $categoryTree)
    {
    }

    /**
     * Options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!$this->options) {
            $this->options = $this->generateOptions($this->categoryTree->getCategoriesTree());
        }

        return $this->options;
    }

    /**
     * Prepend parent category name.
     *
     * @param array $tree
     * @return array
     */
    public function generateOptions(array $tree): array
    {
        $options = [];

        foreach ($tree as $data) {
            $options[] = [
                'value' => $data['entity_id'],
                'label' => $data['name']
            ];

            if (isset($data['children'])) {
                // append parent name to children
                array_walk($data['children'], static function (&$child, $idx, $parentName) {
                    $child['name'] = "{$parentName} / {$child['name']}";
                }, $data['name']);

                foreach ($this->generateOptions($data['children']) as $child) {
                    $options[] = $child;
                }
            }
        }

        return $options;
    }
}
