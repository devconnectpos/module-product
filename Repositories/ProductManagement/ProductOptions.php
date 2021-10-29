<?php
/**
 * Created by IntelliJ IDEA.
 * User: vjcspy
 * Date: 22/10/2016
 * Time: 15:52
 */

namespace SM\Product\Repositories\ProductManagement;

use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use SM\Product\Helper\ProductHelper;
use Magento\Catalog\Model\Product\Option;

/**
 * Class ProductOptions
 *
 * @package SM\Product\Repositories\ProductManagement
 */
class ProductOptions
{

    /**
     * @var \SM\Product\Helper\ProductHelper
     */
    protected $productHelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var \Magento\Catalog\Helper\Product
     */
    private $catalogProduct;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;
    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductPrice
     */
    private $productPrice;
    /**
     * @var \SM\Integrate\Helper\Data
     */
    private $integrateData;

    private $obBvalue;

    protected $imageHelper;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    private $customOptionRepository;

    /**
     * ProductOptions constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface               $objectManager
     * @param \Magento\Catalog\Helper\Product                         $catalogProduct
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Catalog\Model\ProductFactory                   $productFactory
     * @param \SM\Product\Repositories\ProductManagement\ProductPrice $productPrice
     * @param \SM\Integrate\Helper\Data                               $integrateData
     * @param \Magento\Catalog\Helper\Image                           $imageHelper
     * @param \SM\Product\Helper\ProductHelper                        $productHelper
     * @param ProductCustomOptionRepositoryInterface                  $customOptionRepository
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Product $catalogProduct,
        Registry $registry,
        ProductFactory $productFactory,
        ProductPrice $productPrice,
        \SM\Integrate\Helper\Data $integrateData,
        \Magento\Catalog\Helper\Image $imageHelper,
        ProductHelper $productHelper,
        ProductCustomOptionRepositoryInterface $customOptionRepository
    ) {
        $this->productFactory = $productFactory;
        $this->objectManager = $objectManager;
        $this->catalogProduct = $catalogProduct;
        $this->registry = $registry;
        $this->productPrice = $productPrice;
        $this->integrateData = $integrateData;
        $this->imageHelper = $imageHelper;
        $this->productHelper = $productHelper;
        $this->customOptionRepository = $customOptionRepository;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     * @throws \Exception
     */
    public function getOptions(\Magento\Catalog\Model\Product $product)
    {
        $xOptions = [];
        switch ($product->getTypeId()) {
            case 'configurable':
                $xOptions['configurable'] = $this->getOptionsConfigurableProduct($product);
                break;
            case 'bundle':
                $xOptions['bundle'] = $this->getOptionsBundleProduct($product);
                break;
            case 'grouped':
                $xOptions['grouped'] = $this->getAssociatedProducts($product);
                break;
            case 'aw_giftcard':
                /** @var \SM\Product\Repositories\ProductManagement\ProductOptions\AWGiftCard $awGC */
                $awGC = $this->objectManager->create(
                    'SM\Product\Repositories\ProductManagement\ProductOptions\AWGiftCard'
                );
                $xOptions['gift_card'] = $awGC->getGiftCardOption($product);
                break;
            case 'amgiftcard':
                /** @var \SM\Product\Repositories\ProductManagement\ProductOptions\AmastyGiftCard $amGC */
                $amGC = $this->objectManager->create(
                    'SM\Product\Repositories\ProductManagement\ProductOptions\AmastyGiftCard'
                );
                $xOptions['gift_card'] = $amGC->getGiftCardOption($product);
                break;
            case 'giftcard':
                /** @var \SM\Product\Repositories\ProductManagement\ProductOptions\AWGiftCard $awGC */
                $m2eeGC = $this->objectManager->create(
                    'SM\Product\Repositories\ProductManagement\ProductOptions\M2EEGiftCard'
                );
                $xOptions['gift_card'] = $m2eeGC->getGiftCardOption($product);
                break;
        }

        return $xOptions;
    }

    public function getCustomizableOptions(\Magento\Catalog\Model\Product $product)
    {
        return $this->getCustomOptionsSimpleProduct($product);
    }

    /**
     * @return \Magento\Framework\ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @return \Magento\Catalog\Helper\Product
     */
    public function getCatalogProduct()
    {
        return $this->catalogProduct;
    }

    /**
     * @return \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
     */
    protected function getConfigurableBlock()
    {
        return $this->objectManager->create('\SM\Product\Repositories\ProductManagement\ProductOptions\Configurable');
    }

    /**
     * @return \Magento\Bundle\Block\Adminhtml\Catalog\Product\Composite\Fieldset\Bundle
     */
    protected function getBundleBlock()
    {
        return $this->objectManager->create(
            '\Magento\Bundle\Block\Adminhtml\Catalog\Product\Composite\Fieldset\Bundle'
        );
    }

    /**
     * @return \Magento\GroupedProduct\Block\Adminhtml\Product\Composite\Fieldset\Grouped
     */
    protected function getGroupedBlock()
    {
        return $this->objectManager->create(
            '\Magento\GroupedProduct\Block\Adminhtml\Product\Composite\Fieldset\Grouped'
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    protected function getOptionsConfigurableProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->resetProductInBlock($product);
        $this->catalogProduct->setSkipSaleableCheck(true);

        return json_decode($this->getConfigurableBlock()->getConfigurableJsonConfig(), true);
    }

    /**
     * @param $product
     *
     * @return mixed
     */
    protected function getOptionsBundleProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->resetProductInBlock($product);
        $this->catalogProduct->setSkipSaleableCheck(true);
        $outputOptions = [];
        $options = $this->getBundleBlock()->getOptions();
        $obValues = [];
        if ($this->integrateData->isExistPektsekyeOptionBundle()) {
            $this->obBvalue = $this->objectManager->get('Pektsekye\OptionBundle\Model\ResourceModel\Bvalue');
            $obValues = $this->obBvalue->getValues($product->getId(), (int)$product->getStoreId());
        }
        foreach ($options as $option) {
            $selections = [];
            if (is_array($option->getSelections())) {
                foreach ($option->getSelections() as $selection) {
                    $selectionData = $selection->getData();
                    $selectionData['id'] = $selectionData['entity_id'];
                    if (!empty($selectionData['tier_price'])) {
                        $selectionData['tier_prices'] = $selectionData['tier_price'];
                    } else {
                        $selectionData['tier_prices'] = $this->getProductPrice()
                            ->getExistingPrices($selection, 'tier_price', true);
                    }
                    $image = isset($selectionData['image']) ? $selectionData['image'] : '';

                    if ($this->integrateData->isExistPektsekyeOptionBundle()) {
                        $image = !empty($obValues[$selectionData['selection_id']]['image']) ? $obValues[$selectionData['selection_id']]['image'] : $image;
                    }
                    $imageUrl = '';
                    if (!empty($image)) {
                        $imageUrl = $this->imageHelper->init($product, 'product_page_image_small', ['type' => 'thumbnail'])
                            ->resize(200)
                            ->setImageFile($image)
                            ->getUrl();
                    }
                    $selectionData['image'] = $imageUrl;
                    $selectionData['small_image'] = $imageUrl;
                    $selectionData['thumbnail'] = $imageUrl;
                    if (isset($selectionData['product_id'])) {
                        $selectionData['additional_data'] = $this->productHelper->getProductAdditionalData($selectionData['product_id']);
                    }
                    $selections[] = $selectionData;
                }
            }
            $optionData = $option->getData();
            $optionData['selections'] = $selections;
            $outputOptions[] = $optionData;
        }

        return [
            'options'    => $outputOptions,
            'type_price' => $product->getPriceType(),
        ];
    }

    protected function getBundleOptionSelections(\Magento\Catalog\Model\Product $product)
    {
        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $typeInstance->setStoreFilter($product->getStoreId(), $product);
        $optionCollection = $typeInstance->getOptionsCollection($product);
        $selectionCollection = $typeInstance->getSelectionsCollection(
            $typeInstance->getOptionsIds($product),
            $product
        );
        $catalogRuleProcessor = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\CatalogRule\Model\ResourceModel\Product\CollectionProcessor::class);
        $catalogRuleProcessor->addPriceData($selectionCollection);
        $selectionCollection->addTierPriceData();

        return $optionCollection->appendSelections(
            $selectionCollection,
            false,
            $this->catalogProduct->getSkipSaleableCheck()
        );
    }

    /**
     * @param $product
     *
     * @return array
     */
    protected function getAssociatedProducts(\Magento\Catalog\Model\Product $product)
    {
        $outputOptions = [];
        $this->resetProductInBlock($product);
        $this->catalogProduct->setSkipSaleableCheck(true);
        //$this->getGroupedBlock()->setPreconfiguredValue();
        $_associatedProducts = $this->getGroupedBlock()->getAssociatedProducts();
        $_hasAssociatedProducts = count($_associatedProducts) > 0;
        if ($_hasAssociatedProducts) {
            foreach ($_associatedProducts as $_item) {
                $_itemData = $_item->getData();
                if (!empty($_itemData['tier_price'])) {
                    $_itemData['tier_prices'] = $_itemData['tier_price'];
                } else {
                    $_itemData['tier_prices'] = $this->getProductPrice()
                        ->getExistingPrices($_item, 'tier_price', true);
                }
                $outputOptions[] = $_itemData;
            }
        }

        return $outputOptions;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    protected function getCustomOptionsSimpleProduct(\Magento\Catalog\Model\Product $product)
    {
        $options = [];
        $customOptions = $this->customOptionRepository->getList($product->getSku());

        /** @var \Magento\Catalog\Model\Product\Option $option */
        foreach ($customOptions as $option) {
            $customOption = $option->getData();
            $customOption['data'] = $option->getValuesCollection()->getData();
            $options[] = $customOption;
        }

        return $options;
    }

    /**
     * @return \Magento\Framework\Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @param $product
     *
     * @return $this
     */
    protected function resetProductInBlock($product)
    {
        $this->getRegistry()->unregister('current_product');
        $this->getRegistry()->unregister('product');
        $this->getRegistry()->register('current_product', $product);
        $this->getRegistry()->register('product', $product);

        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->productFactory->create();
    }

    /**
     * @return \SM\Product\Repositories\ProductManagement\ProductPrice
     */
    public function getProductPrice()
    {
        return $this->productPrice;
    }
}
