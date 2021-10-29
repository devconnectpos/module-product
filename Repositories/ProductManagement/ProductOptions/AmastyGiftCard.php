<?php

namespace SM\Product\Repositories\ProductManagement\ProductOptions;

use Amasty\GiftCard\Model\Config\Source\GiftCardType;
use Amasty\GiftCard\Utils\FileUpload;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GiftCard\Model\Giftcard;
use Magento\Store\Model\StoreManagerInterface;
use SM\Product\Helper\ProductHelper;
use SM\Product\Repositories\ProductManagement\ProductOptions;
use SM\Product\Repositories\ProductManagement\ProductPrice;

class AmastyGiftCard extends ProductOptions
{
    const PRICE_TYPE_PERCENT = 1;
    const PRICE_TYPE_FIXED = 2;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $storeManager;

    /**
     * @var \Amasty\GiftCard\Block\Product\View\Type\GiftCard
     */
    protected $giftCardViewBlock;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var FileUpload
     */
    protected $fileUpload;

    public function __construct(
        ObjectManagerInterface $objectManager,
        \Magento\Catalog\Helper\Product $catalogProduct,
        Registry $registry, ProductFactory $productFactory,
        ProductPrice $productPrice,
        \SM\Integrate\Helper\Data $integrateData,
        \Magento\Catalog\Helper\Image $imageHelper,
        ProductHelper $productHelper,
        ProductCustomOptionRepositoryInterface $customOptionRepository,
        StoreManagerInterface $storeManager,
        FileUpload $fileUpload,
        Json $jsonSerializer
    ) {
        $this->fileUpload = $fileUpload;
        $this->storeManager = $storeManager;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($objectManager, $catalogProduct, $registry, $productFactory, $productPrice, $integrateData, $imageHelper, $productHelper, $customOptionRepository);
    }

    /**
     * @return \Amasty\GiftCard\Block\Product\View\Type\GiftCard
     */
    protected function getGiftCardViewBlock()
    {
        if (is_null($this->giftCardViewBlock)) {
            $this->giftCardViewBlock = $this->getObjectManager()->create('Amasty\GiftCard\Block\Product\View\Type\GiftCard');
        }

        return $this->giftCardViewBlock;
    }

    /**
     * @param Product $product
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGiftCardOption(Product $product)
    {
        $this->getGiftCardViewBlock()->setData('product', $product);

        $gcFeeTypeAttr = $product->getCustomAttribute('am_giftcard_fee_type');
        $amountOptionValue = [];
        $gcFeeType = 'none';

        if ($product->getData('am_open_amount_min')) {
            $amountOptionValue['min'] = $product->getData('am_open_amount_min');
        }

        if ($product->getData('am_open_amount_max')) {
            $amountOptionValue['max'] = $product->getData('am_open_amount_max');
        }

        if ($gcFeeTypeAttr) {
            switch ($gcFeeTypeAttr->getValue()) {
                case self::PRICE_TYPE_PERCENT:
                    $gcFeeType = 'percent';
                    break;
                case self::PRICE_TYPE_FIXED:
                    $gcFeeType = 'fixed';
                    break;
                default:
                    $gcFeeType = 'none';
            }
        }

        $amounts = $this->getAmounts($product);

        return [
            'isAllowPreview'       => true,
            'isAllowDesignSelect'  => (bool)$product->getData('am_giftcard_code_image'),
            'isAllowMessage'       => false,
            'isAllowHeadline'      => false,
            'isAllowEmail'         => true,
            'isAllowDeliveryDate'  => false,
            'isAllowOpenAmount'    => (bool)$product->getData('am_allow_open_amount'),
            'isFixedAmount'        => !((bool)$product->getData('am_allow_open_amount')),
            'isPhysicalValue'      => $product->getData('am_giftcard_type') == GiftCardType::TYPE_PRINTED,
            'isCombinedValue'      => $product->getData('am_giftcard_type') == GiftCardType::TYPE_COMBINED,
            'isVirtualValue'       => $product->getData('am_giftcard_type') == GiftCardType::TYPE_VIRTUAL,
            'isAllowGiftWrapping'  => false,
            'giftWrappingPrice'    => false,
            'getAmountOptions'     => $amounts,
            'getAmountOptionValue' => $amountOptionValue,
            'getMinCustomAmount'   => $product->getData('am_open_amount_min') ?: false,
            'getMaxCustomAmount'   => $product->getData('am_open_amount_max') ?: false,
            'getTimezones'         => $this->jsonSerializer->unserialize($this->getGiftCardViewBlock()->getListTimezones()),
            'getGiftcardTemplates' => $this->getGiftCardImageTemplates($product),
            'codePool'             => $product->getData('am_giftcard_code_set') ?: false,

            // Below are values specific to Amasty gift card
            'giftCardFeeEnabled'   => (bool)$product->getData('am_giftcard_fee_enable'),
            'giftCardFeeType'      => $gcFeeType,
            'giftCardLifeTime'     => (int)$product->getData('am_giftcard_lifetime'),

            // This option shows only when the gift card is a combined gift card
            'giftCardTypeOptions'  => [
                [
                    'label' => 'e-Gift Card',
                    'value' => GiftCardType::TYPE_VIRTUAL,
                ],
                [
                    'label' => 'Physical Gift Card',
                    'value' => GiftCardType::TYPE_PRINTED,
                ],
                [
                    'label' => 'Combined Gift Card',
                    'value' => GiftCardType::TYPE_COMBINED,
                ],
            ],
        ];
    }

    /**
     * @param Product $product
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getAmounts($product)
    {
        $amountOptionAttr = $product->getCustomAttribute('am_giftcard_prices');

        if (is_null($amountOptionAttr)) {
            return [];
        }

        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $amounts = $amountOptionAttr->getValue();
        $result = [];
        foreach ($amounts as $amount) {
            if ($amount['website_id'] == '0' || $amount['website_id'] == $websiteId) {
                $result[] = $amount['value'];
            }
        }

        return $result;
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    protected function getGiftCardType(Product $product)
    {
        $giftCardType = $product->getData('am_giftcard_type');

        switch ($giftCardType) {
            case GiftCardType::TYPE_VIRTUAL:
                return 'virtual';

            case GiftCardType::TYPE_PRINTED:
                return 'physical';

            case GiftCardType::TYPE_COMBINED:
                return 'combined';
        }

        return '';
    }

    /**
     * @return \Amasty\GiftCard\Model\Image\ResourceModel\Collection|mixed
     */
    protected function getGiftCardImageCollection()
    {
        return $this->getObjectManager()->get('Amasty\GiftCard\Model\Image\ResourceModel\Collection');
    }

    /**
     * @param $product
     *
     * @return array
     */
    protected function getGiftCardImageTemplates($product)
    {
        $images = [];

        if ($productImagesId = $product->getAmGiftcardCodeImage()) {
            $productImagesId = explode(',', $productImagesId);
            $collection = $this->getGiftCardImageCollection()
                ->addFieldToFilter('image_id', ['in' => $productImagesId]);

            foreach ($collection->getItems() as $image) {
                try {
                    $images[] = [
                        'name'     => $image->getTitle(),
                        'value'    => $image->getImageId(),
                        'imageUrl' => $this->fileUpload->getImageUrl(
                            $image->getImagePath()
                        ),
                    ];
                } catch (LocalizedException $e) {
                    return $images;
                }
            }
        }

        return $images;
    }
}
