<?php
namespace SM\Product\Repositories\ProductManagement;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use SM\XRetail\Helper\DataConfig;

/**
 * Class ProductAttribute
 *
 * @package SM\Product\Repositories\ProductManagement
 */
class ProductAttribute
{

    /**
     * @var
     */
    protected $data;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $productAttributeCollection;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;
    /**
     * @var \SM\XRetail\Helper\DataConfig
     */
    private $dataConfig;

    /**
     * ProductAttribute constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $productAttributeCollection
     * @param \SM\XRetail\Helper\DataConfig $dataConfig
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        Collection $productAttributeCollection,
        DataConfig $dataConfig,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->dataConfig = $dataConfig;
        $this->productAttributeCollection = $productAttributeCollection;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getCustomAttributes(Product $product)
    {
        if ($this->dataConfig->getApiGetCustomAttributes()) {
            $customAtt  = [];
            $attributes = $this->getAllCustomAttributes();
            foreach ($attributes as $attribute) {
                $val = $product->getData($attribute['value']);
                if (!is_null($val)) {
                    $customAtt[$attribute['value']] = [
                        'label' => $attribute['key'] ?? '',
                        'value' => $val,
                    ];
                }
            }

            return $customAtt;
        } else {
            return [];
        }
    }

    /**
     * @return mixed
     */
    public function getAllCustomAttributes()
    {
        $result     = [];
        // Exclude some unneeded attributes, either because we do not use them or they are duplicated with those in top level
        $excludeAttributes = [
            'name',
            'sku',
            'image',
            'thumbnail',
            'small_image',
            'options_container',
            'msrp_display_actual_price_type',
            'url_key',
            'required_options',
            'has_options',
            'tax_class_id',
            'category_ids',
            'media_gallery',
            'price',
            'tier_price',
            'visibility',
            'status',
            'quantity_and_stock_status',
        ];
        $attributes = $this->productAttributeCollection
            ->addFieldToFilter('attribute_code', ['nin' => $excludeAttributes])
            ->addVisibleFilter();

        if ($attributes != null && $attributes->count() > 0) {
            foreach ($attributes as $attribute) {
                $result[] = ['value' => $attribute->getAttributeCode(), 'key' => $attribute->getFrontendLabel()];
            }
        }
        return $result;
    }

    public function getStoreFrontAttributes($product, array $excludeAttr = [])
    {
        $data = [];
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            if ($this->isVisibleOnFrontend($attribute, $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($product);

                if ($value instanceof \Magento\Framework\Phrase) {
                    $value = (string)$value;
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = $this->priceCurrency->convertAndFormat($value);
                }

                if (is_string($value) && strlen(trim($value))) {
                    $data[$attribute->getAttributeCode()] = [
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attribute->getAttributeCode(),
                    ];
                }
            }
        }
        return $data;
    }

    protected function isVisibleOnFrontend(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute,
        array $excludeAttr
    ) {
        return ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr));
    }
}
