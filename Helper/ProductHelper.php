<?php
/**
 * Created by KhoiLe - mr.vjcspy@gmail.com
 * Date: 7/9/17
 * Time: 4:40 PM
 */

namespace SM\Product\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Config\Model\Config\Loader;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Framework\ObjectManagerInterface;

class ProductHelper
{
    protected $productAdditionAttribute;

    protected $_productAdditionAttribute;

    protected $_productAttributeStatus;
    protected $productSearchAttribute;
    /**
     * @var \Magento\Config\Model\Config\Loader
     */
    protected $configLoader;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;
    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $entityType;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $configData;

    public function __construct(
        Loader $loader,
        Type $entityType,
        ObjectManagerInterface $objectManager,
        ProductFactory $productFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->configLoader = $loader;
        $this->entityType     = $entityType;
        $this->objectManager = $objectManager;
    }

    public function getProductAdditionAttribute()
    {
        if (is_null($this->productAdditionAttribute)) {
            $configData       = $this->configLoader->getConfigByPath('xretail/' . 'pos', 'default', 0);
            $productAttribute = array_filter(
                $configData,
                function ($key) {
                    return $key === 'xretail/pos/addition_field_search';
                },
                ARRAY_FILTER_USE_KEY
            );

            if (is_array($productAttribute) && count($productAttribute) > 0 && is_array(json_decode(current($productAttribute)['value'], true))) {
                $this->productAdditionAttribute = json_decode(current($productAttribute)['value'], true);
            } else {
                $this->productAdditionAttribute = [];
            }
        }

        return $this->productAdditionAttribute;
    }

    public function getPWAProductAttributeStatus($storeID)
    {
        return $this->scopeConfig->getValue('pwa/product_category/pwa_show_disable_products', 'stores', $storeID);
    }

    public function getPWAOutOfStockStatus($storeID)
    {
        return $this->scopeConfig->getValue('pwa/product_category/pwa_show_out_of_stock_products', 'stores', $storeID);
    }

    public function getPWAProductVisibility($storeID)
    {
        return $this->scopeConfig->getValue('pwa/product_category/pwa_show_product_visibility', 'stores', $storeID);
    }

    /**
     * @return array|mixed
     */
    public function getDefaultProductAttributeSearch()
    {
        if (is_null($this->productSearchAttribute)) {
            $configData       = $this->configLoader->getConfigByPath('xretail/' . 'pos', 'default', 0);
            $productAttribute = array_filter(
                $configData,
                function ($key) {
                    return $key === 'xretail/pos/search_product_attribute';
                },
                ARRAY_FILTER_USE_KEY
            );

            if (count($productAttribute) > 0 && is_array(json_decode(current($productAttribute)['value'], true))) {
                $this->productSearchAttribute = json_decode(current($productAttribute)['value'], true);
            } else {
                $this->productSearchAttribute = [];
            }
        }

        return $this->productSearchAttribute;
    }

    public function getSearchOnlineAttribute($defaultSearchField = null)
    {
        if (!!$defaultSearchField) {
            $defaultAttributeSearch = $defaultSearchField;
        } else {
            $defaultAttributeSearch = $this->getDefaultProductAttributeSearch();
        }
        $productAttribute       = $this->getProductAdditionAttribute();

        return array_merge($defaultAttributeSearch, $productAttribute);
    }

    /**
     * @return array
     */
    public function getProductAttributes()
    {
        //$attributes     = $this->productFactory->create()->getAttributes();
        $productEntityTypeId       = $this->entityType->loadByCode('catalog_product')->getId();
        $coll = $this->objectManager->create(Collection::class);
        $coll->addFieldToFilter(Set::KEY_ENTITY_TYPE_ID, $productEntityTypeId);
        $attributes = $coll->load()->getItems();
        $attributeArray = [];

        foreach ($attributes as $attribute) {
            $attributeArray[] = [
                'label' => $attribute->getFrontend()->getLabel(),
                'value' => $attribute->getAttributeCode()
            ];
        }

        return $attributeArray;
    }

    /**
     * @return  \Magento\Catalog\Model\Product
     */
    protected function getProductModel()
    {
        return $this->productFactory->create();
    }

    /**
     * @param \Magento\Catalog\Model\Product | string $product
     *
     * @return array
     */
    public function getProductAdditionalData($product)
    {
        $configData                     = $this->getConfigLoaderData();
        $estimatedAvailabilityAttribute = $configData['xretail/pos/attribute_for_estimated_availability']['value'];
        $additionalData                 = [];

        if (!$product instanceof Product) {
            $product = $this->getProductModel()->load($product);
        }

        if (!!$estimatedAvailabilityAttribute && $estimatedAvailabilityAttribute != '') {
            $additionalData[] = ['attribute' => $estimatedAvailabilityAttribute, 'value' => $product->getData($estimatedAvailabilityAttribute)];
        }

        return $additionalData;
    }

    protected function getConfigLoaderData()
    {
        if ($this->configData === null) {
            $this->configData = $this->configLoader->getConfigByPath('xretail/pos', 'default', 0);
        }

        return $this->configData;
    }
}
