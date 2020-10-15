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
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    
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
     * @param \Magento\CatalogInventory\Api\StockItemCriteriaInterface $stockItemCriteria
     * @param \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        StockItemCriteriaInterface $stockItemCriteria,
        StockItemRepositoryInterface $stockItemRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->stockItemCriteria   = $stockItemCriteria;
        $this->stockItemRepository = $stockItemRepository;
        $this->productRepository = $productRepository;
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
            if ($product->getTypeId() === 'configurable') {
                $isInStock = $this->checkConfigurableProduct($product, $scope);
                $stock->setData('is_in_stock', $isInStock);
            }
            $listType = ['simple', 'virtual', 'giftcard', 'aw_giftcard', 'aw_giftcard2'];
            if (in_array($product->getType(), $listType)) {
                if ($stock->getData('qty') > 0 && $stock->getData('is_in_stock') == 1) {
                    $stock->setData('is_in_stock', '1');
                } else {
                    $stock->setData('is_in_stock', '0');
                }
            }
            return $stock->getData();
        }
        return [];
    }
    
    private function checkConfigurableProduct($product, $scope)
    {
        $children = $product->getTypeInstance()->getChildrenIds($product->getId());
        $children = $children[0];
        foreach ($children as $child) {
            /** @var Product $p */
            $p = $this->productRepository->getById($child);
            $stock = $this->getStock($p, $scope);
            if ($stock['is_in_stock'] == 1) {
                return 1;
            }
        }
    
        return 0;
    }
}
