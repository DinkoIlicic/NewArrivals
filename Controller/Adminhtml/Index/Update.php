<?php

declare(strict_types=1);

namespace Dinko\NewArrivals\Controller\Adminhtml\Index;

use Dinko\NewArrivals\Model\ResourceModel\NewArrivals;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Update extends Action
{
    public const ADMIN_RESOURCE = "Dinko_NewArrivals::new_arrivals";

    /**
     * Update constructor.
     * @param Action\Context $context
     * @param NewArrivals $newArrivals
     */
    public function __construct(
        Action\Context $context,
        private NewArrivals $newArrivals
    ) {
        parent::__construct($context);
    }

    /**
     * Update new arrivals manually
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        if (!$this->newArrivals->updateNewArrivals()) {
            $this->messageManager->addErrorMessage(__('Something went wrong, check logs'));
            return $this->_redirect('catalog/category/index');
        }

        $this->messageManager->addSuccessMessage(__('New Arrivals successfully updated'));
        return $this->_redirect('catalog/category/index');
    }
}
