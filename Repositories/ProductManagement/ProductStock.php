<?php
/**
 * Created by IntelliJ IDEA.
 * User: vjcspy
 * Date: 22/10/2016
 * Time: 22:42
 */

namespace SM\Product\Repositories\ProductManagement;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;

/**
 * Class ProductStock
 *
 * @package SM\Product\Repositories\ProductManagement
 */
class ProductStock
{

    /**
     * @var \Magento\CatalogInventory\Api\StockItemCriteriaInterface
     */
    private $stockItemCriteria;
    /**
     * @var \Magento\CatalogInventory\Api\StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * ProductStock constructor.
     *
     * @param \Magento\CatalogInventory\Api\StockItemCriteriaInterface   $stockItemCriteria
     * @param \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository
     */
    public function __construct(
        StockItemCriteriaInterface $stockItemCriteria,
        StockItemRepositoryInterface $stockItemRepository
    ) {
        $this->stockItemCriteria   = $stockItemCriteria;
        $this->stockItemRepository = $stockItemRepository;
    }

    public function getStock(Product $product, $scope)
    {
        /*
         * FIXME: Hiện tại do thằng magento nó không chia stock theo website được mà luôn fix = 0 nên mình cũng phải làm theo nó
         * see: \Magento\CatalogInventory\Model\StockState::getStockQty()
         */
        $this->stockItemCriteria->setProductsFilter([$product->getId()]);
        $this->stockItemCriteria->setScopeFilter($scope);
        $stocks = $this->stockItemRepository->getList($this->stockItemCriteria)->getItems();
        if (is_array($stocks) && count($stocks) == 1) {
            $stock = array_values($stocks)[0];
            return $stock->getData();
        }
        return [];
    }
}
