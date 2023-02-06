<?php

declare(strict_types=1);

namespace Dinko\NewArrivals\Model\ResourceModel;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class NewArrivals
{
    public const NEW_ARRIVALS_CATEGORY_ID = 'catalog/new_arrivals/category_id';

    /**
     * NewArrivals constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param Category $categoryResource
     */
    public function __construct(
        protected ScopeConfigInterface                $scopeConfig,
        protected CategoryRepositoryInterface         $categoryRepository,
        protected ResourceConnection                  $resource,
        protected LoggerInterface                     $logger,
        protected ProductAttributeRepositoryInterface $productAttributeRepository,
        protected Category                            $categoryResource
    ) {
    }

    /**
     * Get new Arrival Category ID
     *
     * @return int
     */
    private function getNewArrivalCategoryId(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::NEW_ARRIVALS_CATEGORY_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Update New Arrivals
     *
     * @return bool
     */
    public function updateNewArrivals(): bool
    {
        try {
            if (!$newArrivalsCategoryId = $this->getNewArrivalCategoryId()) {
                $this->logger->critical('No new arrivals category ID.');
                return false;
            }

            if (!$category = $this->categoryRepository->get($newArrivalsCategoryId)) {
                $this->logger->critical('No new arrivals category.');
                return false;
            }

            $products = $this->getNewArrivalProducts();
            $category->setPostedProducts(array_flip($products));
            $this->categoryResource->save($category); //@codingStandardsIgnoreLine
            return true;
        } catch (Exception $exception) {
            $this->logger->critical('Could not update new arrivals.', [
                'message' => $exception->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get Products fitting requirements for new arrivals
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getNewArrivalProducts(): array
    {
        $date = date('Y-m-d');

        $newFromDate = (int)$this->productAttributeRepository->get('news_from_date')->getAttributeId();
        $newToDate = (int)$this->productAttributeRepository->get('news_to_date')->getAttributeId();
        if (!$newFromDate || !$newToDate) {
            $this->logger->critical('No New From Date or New To Date Attribute IDs');
            return [];
        }

        $connection = $this->resource->getConnection();

        $collection = $connection
            ->select()
            ->from(
                ['cpe' => $connection->getTableName('catalog_product_entity')],
                'cpe.entity_id'
            )
            ->joinLeft(
                ['cpednf' => $connection->getTableName('catalog_product_entity_datetime')],
                'cpednf.entity_id = cpe.entity_id and cpednf.store_id = 0 and cpednf.attribute_id = ' . $newFromDate,
                []
            )->joinLeft(
                ['cpednt' => $connection->getTableName('catalog_product_entity_datetime')],
                'cpednt.entity_id = cpe.entity_id and cpednt.store_id = 0 and cpednt.attribute_id = ' . $newToDate,
                []
            )
            ->where('cpednf.value is NOT NULL')
            ->where('cpednf.value <= ?', $date)
            ->where('(cpednt.value is NULL OR cpednt.value >= ?)', $date)
            ->distinct(); //@codingStandardsIgnoreLine - multiple same ids returned
        return $connection->fetchCol($collection);
    }
}
