<?php

namespace SM\Product\Repositories;

use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Config\Model\Config\Loader;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use SM\Core\Api\Data\PWAProduct;
use SM\Core\Api\Data\XProduct;
use SM\Core\Model\DataObject;
use SM\CustomSale\Helper\Data;
use SM\Integrate\Model\WarehouseIntegrateManagement;
use SM\Performance\Helper\CacheKeeper;
use SM\Product\Helper\ProductHelper;
use SM\Product\Helper\ProductImageHelper;
use SM\Product\Repositories\ProductManagement\ProductAttribute;
use SM\Product\Repositories\ProductManagement\ProductMediaGalleryImages;
use SM\Product\Repositories\ProductManagement\ProductOptions;
use SM\Product\Repositories\ProductManagement\ProductPrice;
use SM\Product\Repositories\ProductManagement\ProductStock;
use SM\XRetail\Helper\DataConfig;
use SM\XRetail\Repositories\Contract\ServiceAbstract;

class ProductManagement extends ServiceAbstract
{

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_categoryFactory;

    protected $productFactory;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var CustomSalesHelper
     */
    protected $customSalesHelper;
    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductMediaGalleryImages
     */
    protected $productMediaGalleryImages;
    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $catalogProduct;
    /**
     * @var \Magento\Config\Model\Config\Loader
     */
    protected $configLoader;
    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductOptions
     */
    private $productOptions;
    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    private $productMediaConfig;
    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductAttribute
     */
    private $productAttribute;
    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductStock
     */
    private $productStock;
    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductPrice
     */
    private $productPrice;
    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    private $cache;

    /**
     * @var string
     */
    public static $CACHE_TAG = "xProduct";
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManagement;
    /**
     * @var \SM\Integrate\Model\WarehouseIntegrateManagement
     */
    private $warehouseIntegrateManagement;
    /**
     * @var \SM\Integrate\Helper\Data
     */
    private $integrateData;
    /**
     * @var \SM\Product\Helper\ProductHelper
     */
    private $productHelper;
    /**
     * @var \SM\Product\Helper\ProductImageHelper
     */
    private $productImageHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     */
    protected $attributeSet;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;
    /**
     * Notifier Pool
     *
     * @var NotifierPool
     */
    protected $notifierPool;
    protected $configData;

    /**
     * ProductManagement constructor.
     *
     * @param \Magento\Framework\Cache\FrontendInterface                           $cache
     * @param \Magento\Catalog\Model\CategoryFactory                               $categoryFactory
     * @param \Magento\Framework\App\RequestInterface                              $requestInterface
     * @param \SM\XRetail\Helper\DataConfig                                        $dataConfig
     * @param \Magento\Store\Model\StoreManagerInterface                           $storeManager
     * @param \Magento\Catalog\Model\ProductFactory                                $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory       $collectionFactory
     * @param \SM\Product\Repositories\ProductManagement\ProductOptions            $productOptions
     * @param \Magento\Catalog\Model\Product\Media\Config                          $productMediaConfig
     * @param \SM\Product\Repositories\ProductManagement\ProductAttribute          $productAttribute
     * @param \SM\Product\Repositories\ProductManagement\ProductStock              $productStock
     * @param \SM\Product\Repositories\ProductManagement\ProductPrice              $productPrice
     * @param \SM\Product\Repositories\ProductManagement\ProductMediaGalleryImages $productMediaGalleryImages
     * @param \Magento\Catalog\Helper\Product                                      $catalogProduct
     * @param \SM\CustomSale\Helper\Data                                           $customSaleHelper
     * @param \Magento\Framework\Event\ManagerInterface                            $eventManagement
     * @param \SM\Integrate\Helper\Data                                            $integrateData
     * @param \SM\Integrate\Model\WarehouseIntegrateManagement                     $warehouseIntegrateManagement
     * @param \SM\Product\Helper\ProductHelper                                     $productHelper
     * @param \SM\Product\Helper\ProductImageHelper                                $productImageHelper
     * @param \Magento\Framework\Registry                                          $registry
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface                     $attributeSet
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute                    $eavAttribute
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                   $scopeConfig
     * @param \Magento\Catalog\Model\CategoryRepository                            $categoryRepository
     * @param \Magento\Framework\Notification\NotifierInterface                    $notifierPool
     * @param \Magento\Config\Model\Config\Loader                                  $configLoader
     */
    public function __construct(
        FrontendInterface $cache,
        CategoryFactory $categoryFactory,
        RequestInterface $requestInterface,
        DataConfig $dataConfig,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        CollectionFactory $collectionFactory,
        ProductOptions $productOptions,
        Config $productMediaConfig,
        ProductAttribute $productAttribute,
        ProductStock $productStock,
        ProductPrice $productPrice,
        ProductMediaGalleryImages $productMediaGalleryImages,
        Product $catalogProduct,
        Data $customSaleHelper,
        ManagerInterface $eventManagement,
        \SM\Integrate\Helper\Data $integrateData,
        WarehouseIntegrateManagement $warehouseIntegrateManagement,
        ProductHelper $productHelper,
        ProductImageHelper $productImageHelper,
        Registry $registry,
        AttributeSetRepositoryInterface $attributeSet,
        Attribute $eavAttribute,
        ScopeConfigInterface $scopeConfig,
        CategoryRepository $categoryRepository,
        NotifierPool $notifierPool,
        Loader $configLoader
    ) {
        $this->cache                        = $cache;
        $this->catalogProduct               = $catalogProduct;
        $this->productPrice                 = $productPrice;
        $this->productAttribute             = $productAttribute;
        $this->productFactory               = $productFactory;
        $this->collectionFactory            = $collectionFactory;
        $this->customSalesHelper            = $customSaleHelper;
        $this->productOptions               = $productOptions;
        $this->productMediaConfig           = $productMediaConfig;
        $this->productStock                 = $productStock;
        $this->productMediaGalleryImages    = $productMediaGalleryImages;
        $this->eventManagement              = $eventManagement;
        $this->warehouseIntegrateManagement = $warehouseIntegrateManagement;
        $this->integrateData                = $integrateData;
        $this->productHelper                = $productHelper;
        $this->productImageHelper           = $productImageHelper;
        $this->_eavAttribute                = $eavAttribute;
        $this->registry                     = $registry;
        $this->attributeSet                 = $attributeSet;
        $this->_categoryFactory             = $categoryFactory;
        $this->categoryRepository           = $categoryRepository;
        $this->scopeConfig                  = $scopeConfig;
        $this->notifierPool                 = $notifierPool;
        parent::__construct($requestInterface, $dataConfig, $storeManager);
        $this->configLoader = $configLoader;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getProductData()
    {
        return $this->loadXProducts($this->getSearchCriteria())->getOutput();
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getPWAProductData()
    {
        return $this->loadPWAProducts($this->getSearchCriteria())->getOutput();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getRelatedProduct()
    {
        return $this->loadRelatedProducts($this->getSearchCriteria())->getOutput();
    }

    /**
     * @param null $searchCriteria
     *
     * @return \SM\Core\Api\SearchResult
     * @throws \Exception
     */
    public function loadRelatedProducts($searchCriteria = null)
    {
        if (is_null($searchCriteria) || !$searchCriteria) {
            $searchCriteria = $this->getSearchCriteria();
        }
        WarehouseIntegrateManagement::setWarehouseId(
            is_null($searchCriteria->getData('warehouse_id'))
                ? $searchCriteria->getData('warehouseId')
                : $searchCriteria->getData(
                'warehouse_id'
            )
        );
        $items   = [];
        $storeId = $this->getStoreManager()->getStore()->getId();

        $productId = $searchCriteria->getData('productId');

        $product = $this->getProductModel()->load($productId);

        $relatedProducts = $product->getRelatedProducts();

        if (!empty($relatedProducts)) {
            foreach ($relatedProducts as $relatedProduct) {
                try {
                    $related = $this->getProductModel()->load($relatedProduct->getId());

                    $items[] = $this->processRelatedProduct($related, $storeId, WarehouseIntegrateManagement::getWarehouseId());
                } catch (\Exception $e) {
                    $this->addNotificationError($e->getMessage(), $relatedProduct->getId());
                }
            }
        }

        return $this->getSearchResult()
                    ->setSearchCriteria($searchCriteria)
                    ->setItems($items);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $storeId
     * @param                                $warehouseId
     *
     * @return \SM\Core\Api\Data\XProduct
     * @throws \Exception
     */
    protected function processRelatedProduct(\Magento\Catalog\Model\Product $product, $storeId, $warehouseId)
    {
        /** @var \SM\Core\Api\Data\XProduct $xProduct */
        $xProduct = new XProduct();
        $xProduct->addData($product->getData());

        $xProduct->setData('store_id', $storeId);

        $xProduct->setData('origin_image', $this->productImageHelper->getImageUrl($product));

        $xProduct->setData('media_gallery', $this->productMediaGalleryImages->getMediaGalleryImages($product));

        // get stock_items
        if (!$this->integrateData->isIntegrateWH() || !$warehouseId) {
            $xProduct->setData(
                'stock_items',
                $this->getProductStock()->getStock($product, 0)
            );
        } else {
            $xProduct->setData(
                'stock_items',
                $this->warehouseIntegrateManagement->getStockItem($product, $warehouseId, $product)
            );
        }

        return $xProduct;
    }

    /**
     * @param null $searchCriteria
     *
     * @return \SM\Core\Api\SearchResult
     * @throws \Exception
     */
    public function loadXProducts($searchCriteria = null)
    {
        if (is_null($searchCriteria) || !$searchCriteria) {
            $searchCriteria = $this->getSearchCriteria();
        }

        if (is_nan($searchCriteria->getData('currentPage'))) {
            $searchCriteria->setData('currentPage', 1);
        } else {
            $searchCriteria->setData('currentPage', $searchCriteria->getData('currentPage'));
        }
        if (is_nan($searchCriteria->getData('pageSize'))) {
            $searchCriteria->setData(
                'pageSize',
                DataConfig::PAGE_SIZE_LOAD_PRODUCT
            );
        } else {
            $searchCriteria->setData(
                'pageSize',
                $searchCriteria->getData('pageSize')
            );
        }

        $collection = $this->getProductCollection($searchCriteria);
        $storeId    = $this->getStoreManager()->getStore()->getId();

        if (is_null($searchCriteria->getData('warehouse_id'))) {
            WarehouseIntegrateManagement::setWarehouseId(
                $searchCriteria->getData('warehouseId')
            );
        } else {
            WarehouseIntegrateManagement::setWarehouseId(
                $searchCriteria->getData('warehouse_id')
            );
        }

        $items = [];

        $loadingData = new DataObject(
            [
                'collection'      => $collection,
                'search_criteria' => $searchCriteria,
                'items'           => $items
            ]
        );
        $this->eventManagement->dispatch(
            'before_load_x_product',
            ['loading_data' => $loadingData]
        );

        if ($loadingData->getData(CacheKeeper::$IS_PULL_FROM_CACHE) === true
            && $searchCriteria->getData('searchOnline') != 1) {
            $items = $loadingData->getData('items');
            $this->getSearchResult()->setCacheTime($loadingData->getData('cache_time'));
        } else {
            if ($collection->getLastPageNumber() < $searchCriteria->getData('currentPage')) {
                $loadingData->setData('is_full_loading', true);
            } else {
                // Skip salesable check when collect child product
                $this->catalogProduct->setSkipSaleableCheck(true);

                // Fix can't get product because magento add plugin before load to filter status product.
                // Unknown why table "cataloginventory_stock_status" hasn't status of product
                //(maybe bms_warehouse cause this)
                $collection->setFlag("has_stock_status_filter", true);

                foreach ($collection as $item) {
                    try {
                        $product = $this->getProductModel()->load($item->getId());

                        $items[] = $this->processXProduct(
                            $product,
                            $storeId,
                            WarehouseIntegrateManagement::getWarehouseId(),
                            $item
                        );
                    } catch (\Exception $e) {
                        $this->addNotificationError($e->getMessage(), $item->getId());
                    }
                }
            }
            $loadingData->setData('items', $items);

            $this->eventManagement->dispatch(
                'after_load_x_product',
                [
                    'loading_data' => $loadingData
                ]
            );
        }

        return $this->getSearchResult()
                    ->setSearchCriteria($searchCriteria)
                    ->setIsLoadFromCache($loadingData->getData(CacheKeeper::$IS_PULL_FROM_CACHE))
                    ->setItems($items)
                    ->setTotalCount($collection->getSize())
                    ->setLastPageNumber($collection->getLastPageNumber());
    }

    /**
     * @param null $searchCriteria
     *
     * @return \SM\Core\Api\SearchResult
     * @throws \Exception
     */
    public function loadPWAProducts($searchCriteria = null)
    {
        if (is_null($searchCriteria) || !$searchCriteria) {
            $searchCriteria = $this->getSearchCriteria();
        }

        $searchCriteria->setData('currentPage', is_nan($searchCriteria->getData('currentPage')) ? 1 : $searchCriteria->getData('currentPage'));
        $searchCriteria->setData(
            'pageSize',
            is_nan($searchCriteria->getData('pageSize')) ? DataConfig::PAGE_SIZE_LOAD_PRODUCT : $searchCriteria->getData('pageSize')
        );
        $items = [];
        if ($searchCriteria->getData('categoryId')) {
            $id_category = $searchCriteria->getData('categoryId');
            $storeId     = $searchCriteria->getData('storeId');
            $category    = $this->categoryRepository->get($id_category, $storeId);
            if ($category->getData('is_active') === '0') {
                return $this->getSearchResult()
                            ->setSearchCriteria($searchCriteria)
                            ->setItems($items)
                            ->setTotalCount(0)
                            ->setLastPageNumber(1);
            }
        }

        $collection = $this->getProductCollection($searchCriteria);

        $storeId = $this->getStoreManager()->getStore()->getId();

        // Skip salesable check when collect child product
        $this->catalogProduct->setSkipSaleableCheck(true);

        // Fix can't get product because magento add plugin before load to filter status product. Unknown why table "cataloginventory_stock_status" hasn't status of product(maybe bms_warehouse cause this)
        $collection->setFlag("has_stock_status_filter", true);

        foreach ($collection as $item) {
            try {
                $product = $this->getProductModel()->load($item->getId());

                $pwaProduct = new PWAProduct();

                $pwaProduct->addData($product->getData());
                $pwaProduct->setData('origin_image', $this->productImageHelper->getImageUrl($product));
                $pwaProduct->setData('customizable_options', $this->getProductOptions()->getCustomizableOptions($product));
                $pwaProduct->setData('x_options', $this->getProductOptions()->getOptions($product));
                $pwaProduct->setData('media_gallery', $this->productMediaGalleryImages->getMediaGalleryImages($product));
                $pwaProduct->setData('check_related_product', $this->checkRelatedProduct($product));
                if ($searchCriteria->getData('isViewDetail') === 'true') {
                    $pwaProduct->setData('related_product_ids', $this->getRelatedProductForPWA($product, $storeId));
                }
                $pwaProduct->setData('stock_items', $this->getProductStock()->getStock($product, 0));
                $pwaProduct->setData('visibility', $product->getVisibility());
                $items[] = $pwaProduct;
            } catch (\Exception $e) {
                $this->addNotificationError($e->getMessage(), $item->getId());
            }
        }

        return $this->getSearchResult()
                    ->setSearchCriteria($searchCriteria)
                    ->setItems($items)
                    ->setTotalCount($collection->getSize())
                    ->setLastPageNumber($collection->getLastPageNumber());
    }

    public function checkRelatedProduct($product)
    {
        $relatedProducts = $product->getRelatedProducts();
        if (!empty($relatedProducts)) {
            return true;
        } else {
            return false;
        }
    }

    public function getRelatedProductForPWA($product, $storeId)
    {
        $relatedProductCollection = $product->getRelatedProductCollection()->addStoreFilter($storeId)->getItems();
        $items                    = [];
        foreach ($relatedProductCollection as $relatedProduct) {
            try {
                $related = $this->getProductModel()->load($relatedProduct->getId());
                if ($related->getData('status') == '1') {
                    $pwa = new PWAProduct();
                    $pwa->setData("id", $related->getData('entity_id'));
                    $pwa->addData($related->getData());
                    $pwa->setData('origin_image', $this->productImageHelper->getImageUrl($related));
                    $pwa->setData('customizable_options', $this->getProductOptions()->getCustomizableOptions($related));
                    $pwa->setData('x_options', $this->getProductOptions()->getOptions($related));
                    $pwa->setData('media_gallery', $this->productMediaGalleryImages->getMediaGalleryImages($related));
                    $pwa->setData('check_related_product', $this->checkRelatedProduct($related));
                    $pwa->setData('price', $related->getPrice());
                    $items[] = $pwa->getData();
                }
            } catch (\Exception $e) {
                $this->addNotificationError($e->getMessage(), $relatedProduct->getId());
            }
        }

        return $items;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $storeId
     * @param                                $warehouseId
     * @param DataObject                     $item
     *
     * @return \SM\Core\Api\Data\XProduct
     * @throws \Exception
     */
    protected function processXProduct(\Magento\Catalog\Model\Product $product, $storeId, $warehouseId, $item = null)
    {
        /** @var \SM\Core\Api\Data\XProduct $xProduct */
        $xProduct = new XProduct();
        $xProduct->addData($product->getData());

        $xProduct->setData('tier_prices', $this->getProductPrice()->getExistingPrices($product, 'tier_price', true));

        $xProduct->setData('store_id', $storeId);

        $xProduct->setData('attribute_set_name', $this->getAttributeSetName($product->getAttributeSetId()));

        $xProduct->setData('origin_image', $this->productImageHelper->getImageUrl($product));

        $xProduct->setData('media_gallery', $this->productMediaGalleryImages->getMediaGalleryImages($product));

        $xProduct->setData('custom_attributes', $this->getProductAttribute()->getCustomAttributes($product));
        // get options
        $xProduct->setData('x_options', $this->getProductOptions()->getOptions($product));

        $xProduct->setData('customizable_options', $this->getProductOptions()->getCustomizableOptions($product));
        // get description
        $xProduct->setData('description', $product->getDescription());

        $xProduct->setData('short_description', $product->getShortDescription());

        // get stock_items
        if ((!$this->integrateData->isIntegrateWH() && !$this->integrateData->isMagentoInventory()) || !$warehouseId) {
            $xProduct->setData(
                'stock_items',
                $this->getProductStock()->getStock($product, 0)
            );
        } else {
            $xProduct->setData(
                'stock_items',
                $this->warehouseIntegrateManagement->getStockItem($product, $warehouseId, $item)
            );
        }

        $xProduct->setData(
            'addition_search_fields',
            array_reduce(
                $this->productHelper->getProductAdditionAttribute(),
                function ($result, $field) use ($product) {
                    if (!!$field && is_string($field)) {
                        return $result . ' ' . json_encode($product->getData($field));
                    } else {
                        return $result;
                    }
                },
                ''
            )
        );

        // additional data
        $additionalData = $this->productHelper->getProductAdditionalData($product);
        $xProduct->setData('additional_data', $additionalData);

        return $xProduct;
    }

    /**
     * @param $storeId
     * @param $warehouseId
     *
     * @return \SM\Core\Api\Data\XProduct
     * @throws \Exception
     */
    public function getCustomSaleData($storeId, $warehouseId)
    {
        $product = $this->customSalesHelper->getCustomSaleProduct();

        return $this->processXProduct($product, $storeId, $warehouseId);
    }

    /**
     * @param \Magento\Framework\DataObject $searchCriteria
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Exception
     */
    public function getProductCollection(\Magento\Framework\DataObject $searchCriteria)
    {
        $this->registry->register('disableFlatProduct', true);
        $storeId = $searchCriteria->getData('storeId');
        if (is_null($storeId)) {
            throw new \Exception(__('Must have param storeId'));
        } else {
            $this->getStoreManager()->setCurrentStore($storeId);
        }
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->setFlag("has_stock_status_filter", true);
        if (!$collection->isEnabledFlat()) {
            $collection->addAttributeToSelect('*');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
            $collection->getSelect()->join(
                ['cataloginventory_stock_item' => $collection->getTable('cataloginventory_stock_item')],
                'cataloginventory_stock_item.product_id=e.entity_id',
                ['stock_status' => 'cataloginventory_stock_item.is_in_stock']
            )->where("cataloginventory_stock_item.website_id=0");

            $collection->addStoreFilter($storeId);
        }
        $collection->setCurPage($searchCriteria->getData('currentPage'));
        $collection->setPageSize($searchCriteria->getData('pageSize'));

        //if ($searchCriteria->getData('status')) {
        //    // 1: Enable/ 2: Disable
        //    $collection->addAttributeToFilter('status', ['in' => $searchCriteria->getData('status')]);
        //}
        //
        //if ($searchCriteria->getData('visibility')) {
        //    // 1: Not Visible Individually / 2: Catalog / 3: Search / 4: Catalog, Search
        //    $collection->addAttributeToFilter('visibility', ['in' => $searchCriteria->getData('visibility')]);
        //}
        if (!!$searchCriteria->getData('categoryId') && $searchCriteria->getData('categoryId') !== 'null') {
            //$category = $this->_categoryFactory->create()->load($searchCriteria->getData('categoryId'));
            //$collection->addCategoryFilter($category);
            $collection->getSelect()->join(
                ['category_product' => $collection->getTable('catalog_category_product')],
                'category_product.product_id = e.entity_id',
                ['category_id' => 'category_product.category_id']
            )->where('category_product.category_id =' . $searchCriteria->getData('categoryId'));
        }

        if ($searchCriteria->getData('typeId')) {
            $collection->addAttributeToFilter('type_id', ['in' => $searchCriteria->getData('typeId')]);
        }

        if ($searchCriteria->getData('productIds')) {
            $collection->addFieldToFilter('entity_id', ['in' => $searchCriteria->getData('productIds')]);
        }
        if ($searchCriteria->getData('entity_id') || $searchCriteria->getData('entityId')) {
            if (is_null($searchCriteria->getData('entity_id'))) {
                $ids = $searchCriteria->getData('entityId');
            } else {
                $ids = $searchCriteria->getData('entity_id');
            }
            $collection->addFieldToFilter('entity_id', ['in' => explode(",", $ids)]);
        }
        if (($this->integrateData->isIntegrateWH()
             || $this->integrateData->isMagentoInventory())
            && ($searchCriteria->getData('warehouse_id')
                || $searchCriteria->getData('warehouseId'))) {
            if (is_null($searchCriteria->getData('warehouse_id'))) {
                $id = $searchCriteria->getData('warehouseId');
            } else {
                $id = $searchCriteria->getData('warehouse_id');
            }
            $this->eventManagement->dispatch(
                "pos_integrate_warehouse_filter_product",
                ['collection' => $collection, 'warehouse_id' => $id]
            );
        }

        if ($searchCriteria->getData('searchOnline') == 1) {
            if ($searchCriteria->getData('visibility')) {
                // 1: Not Visible Individually / 2: Catalog / 3: Search / 4: Catalog, Search
                $collection->addAttributeToFilter('visibility', ['in' => $searchCriteria->getData('visibility')]);
            }
            $collection = $this->searchProductOnlineCollection($searchCriteria, $collection);
        }

        if ($searchCriteria->getData('isPWA') && !!$searchCriteria->getData('storeId')) {
            if ($searchCriteria->getData('isSearch') == 1
                || ($searchCriteria->getData('isViewDetail')
                    && $searchCriteria->getData('isViewDetail')
                       == true)) {
                $collection = $this->searchProductPWACollection($searchCriteria, $collection);
            }
            if ($this->productHelper->getPWAProductAttributeStatus($searchCriteria->getData('storeId')) === 'no') {
                $collection->addAttributeToFilter('status', 1);
            }
            if (!!$searchCriteria->getData('categoryId') && $searchCriteria->getData('categoryId') != 'null') {
                $collection->addCategoriesFilter(['in' => [$searchCriteria->getData('categoryId')]]);
            }

            // filter online

            if (($searchCriteria->getData('minPriceFields') == null || $searchCriteria->getData('minPriceFields') == '')
                && ($searchCriteria->getData('maxPriceFields') !== null && $searchCriteria->getData('maxPriceFields') !== '')) {
                $collection->addPriceDataFieldFilter('%s <= %s', ['final_price', $searchCriteria->getData('maxPriceFields')])
                           ->addFinalPrice();
            } elseif (($searchCriteria->getData('maxPriceFields') == null || $searchCriteria->getData('maxPriceFields') == '')
                      && ($searchCriteria->getData('minPriceFields') !== null && $searchCriteria->getData('minPriceFields') !== '')) {
                $collection->addPriceDataFieldFilter('%s >= %s', ['final_price', $searchCriteria->getData('minPriceFields')])
                           ->addFinalPrice();
            } elseif (($searchCriteria->getData('maxPriceFields') !== null && $searchCriteria->getData('maxPriceFields') !== '')
                      && ($searchCriteria->getData('minPriceFields') !== null && $searchCriteria->getData('minPriceFields') !== '')) {
                $collection->addPriceDataFieldFilter('%s >= %s', ['final_price', $searchCriteria->getData('minPriceFields')])
                           ->addPriceDataFieldFilter('%s <= %s', ['final_price', $searchCriteria->getData('maxPriceFields')])
                           ->addFinalPrice();
            } else {
                $collection->addFinalPrice();
            }

            if ($searchCriteria->getData('isViewDetail') && $searchCriteria->getData('isViewDetail') == true) {
            } else {
                if ($this->productHelper->getPWAProductVisibility($searchCriteria->getData('storeId'))) {
                    $collection->addAttributeToFilter(
                        'visibility',
                        ['in' => $this->productHelper->getPWAProductVisibility($searchCriteria->getData('storeId'))]
                    );
                }
            }

            if ($this->productHelper->getPWAOutOfStockStatus($searchCriteria->getData('storeId')) === 'no') {
                $collection->getSelect()->where('cataloginventory_stock_item.is_in_stock = 1');
            } else {
                $this->registry->unregister('is_connectpos');
                $this->registry->register('is_connectpos', true);
            }
            if (!!$searchCriteria->getData('filterField')) {
                if ($searchCriteria->getData('filterField') === 'high_price') {
                    $collection->getSelect()->order('final_price desc');
                } elseif ($searchCriteria->getData('filterField') === 'low_price') {
                    $collection->getSelect()->order('final_price asc');
                } else {
                    $collection->setOrder('entity_id', 'desc');
                }
            } else {
                $collection->setOrder('entity_id', 'desc');
            }
//            $collection = $this->refactorCollectionForPWA($searchCriteria, $collection);
        }
        $this->registry->unregister('disableFlatProduct');

        return $collection;
    }

//
    public function searchProductPWACollection($searchCriteria, $collection)
    {
        if ($searchCriteria->getData('isViewDetail') && $searchCriteria->getData('isViewDetail') == true) {
            $product      = $this->getProductModel()->load($searchCriteria->getData('searchValue'));
            $_configChild = [$product->getData('entity_id')];
            if ($product->getTypeId() != 'simple') {
                $listChild = $product->getTypeInstance()->getChildrenIds($product->getId());

                foreach ($listChild as $child) {
                    $_configChild = array_merge($_configChild, $child);
                }
            }
            $collection->addFieldToFilter('entity_id', ['in' => $_configChild]);
        } else {
            $searchValue = $searchCriteria->getData('searchValue');
            $searchValue = str_replace(',', ' ', $searchValue);
//            $searchField = $this->productHelper->getSearchOnlineAttribute(explode(",", $searchCriteria->getData('searchFields')));
            $searchField = explode(
                ',',
                $this->scopeConfig->getValue(
                    "pwa/search_product/pwa_search_product",
                    'stores',
                    $searchCriteria->getData('storeId')
                )
            );
            if ($searchValue == 'null' || $searchValue == ' ' || $searchValue == '' || (is_array($searchField) && $searchField[0] === '')) {
                $collection->addFieldToFilter('entity_id', null);
            }

            foreach (explode(" ", $searchValue) as $value) {
                $_fieldFilters = [];
                if (is_array($searchField) && $searchField[0] !== '') {
                    foreach ($searchField as $field) {
                        if ($field === 'id') {
                            $_fieldFilters[] = ['attribute' => 'entity_id', 'like' => '%' . $value . '%'];
                        } else {
                            $_fieldFilters[] = ['attribute' => $field, 'like' => '%' . $value . '%'];
                        }
                    }
                    $collection->addAttributeToFilter($_fieldFilters, null, 'left');
                }
            }
        }

        return $collection;
    }

    public function searchProductOnlineCollection($searchCriteria, $collection)
    {
        if ($searchCriteria->getData('isFindProduct') == 1) {
            if ($searchCriteria->getData('isViewDetail') && $searchCriteria->getData('isViewDetail') == true) {
                $product      = $this->getProductModel()->load($searchCriteria->getData('searchValue'));
                $_configChild = [$product->getData('entity_id')];
                if ($product->getTypeId() != 'simple') {
                    $listChild = $product->getTypeInstance()->getChildrenIds($product->getId());

                    foreach ($listChild as $child) {
                        $_configChild = array_merge($_configChild, $child);
                    }
                }
                $collection->addFieldToFilter('entity_id', ['in' => $_configChild]);
            } else {
                $collection->addFieldToFilter('entity_id', ['in' => $searchCriteria->getData('searchValue')]);
            }
        } else {
            if ($searchCriteria->getData('showOutStock') != 1) {
                $collection->getSelect()->where('cataloginventory_stock_item.is_in_stock = 1');
            }
            $searchValue = $searchCriteria->getData('searchValue');
            $searchValue = str_replace(',', ' ', $searchValue);
            //$searchField = $searchCriteria->getData('searchFields');
            $searchField = $this->productHelper->getSearchOnlineAttribute(
                explode(",", $searchCriteria->getData('searchFields'))
            );
            if ($searchValue == 'null' || $searchValue == ' ' || $searchValue == '') {
                $collection->addFieldToFilter('entity_id', null);
            }
            foreach (explode(" ", $searchValue) as $value) {
                $_fieldFilters = [];
                foreach ($searchField as $field) {
                    if ($field === 'id') {
                        $_fieldFilters[] = ['attribute' => 'entity_id', 'like' => '%' . $value . '%'];
                    } else {
                        $_fieldFilters[] = ['attribute' => $field, 'like' => '%' . $value . '%'];
                    }
                }
                $collection->addAttributeToFilter($_fieldFilters, null, 'left');
            }
            if ($searchCriteria->getData('sortValue') && $searchCriteria->getData('sortType')) {
                $collection->addAttributeToSort(
                    $searchCriteria->getData('sortValue'),
                    $searchCriteria->getData('sortType')
                );
            }
        }

        return $collection;
    }

    /**
     * @return \SM\Product\Repositories\ProductManagement\ProductOptions
     */
    public function getProductOptions()
    {
        return $this->productOptions;
    }

    /**
     * @return \Magento\Catalog\Model\Product\Media\Config
     */
    public function getProductMediaConfig()
    {
        return $this->productMediaConfig;
    }

    /**
     * @return \SM\Product\Repositories\ProductManagement\ProductAttribute
     */
    public function getProductAttribute()
    {
        return $this->productAttribute;
    }

    /**
     * @return  \SM\CustomSale\Helper\Data
     */
    public function getCustomSalesHelper()
    {
        return $this->customSalesHelper;
    }

    /**
     * @return \SM\Product\Repositories\ProductManagement\ProductStock
     */
    public function getProductStock()
    {
        return $this->productStock;
    }

    /**
     * @return \SM\Product\Repositories\ProductManagement\ProductPrice
     */
    public function getProductPrice()
    {
        return $this->productPrice;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProductModel()
    {
        return $this->productFactory->create();
    }

    /**
     * @param $attributeSetId
     *
     * @return mixed
     */
    public function getAttributeSetName($attributeSetId)
    {
        try {
            $attributeSetRepository = $this->attributeSet->get($attributeSetId);

            return $attributeSetRepository->getAttributeSetName();
        } catch (\Exception $e) {
            return $attributeSetId;
        }
    }

    /**
     * @param      $message
     * @param null $item
     *
     * @return mixed
     */
    private function addNotificationError($message, $item = null)
    {
        return $this->notifierPool->addCritical('Error During Load Product ID ' . $item, $message);
    }
}
