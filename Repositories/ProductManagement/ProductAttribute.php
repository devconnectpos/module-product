<?php
namespace SM\Product\Repositories\ProductManagement;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog \Model\Product;
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
     * @var \SM\XRetail\Helper\DataConfig
     */
    private $dataConfig;

    /**
     * ProductAttribute constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $productAttributeCollection
     * @param \SM\XRetail\Helper\DataConfig                                     $dataConfig
     */
    public function __construct(
        Collection $productAttributeCollection,
        DataConfig $dataConfig
    ) {
        $this->dataConfig                 = $dataConfig;
        $this->productAttributeCollection = $productAttributeCollection;
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
                    $customAtt[$attribute['value']] = $val;
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
}
