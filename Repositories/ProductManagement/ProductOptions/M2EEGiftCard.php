<?php
/**
 * Created by Hung Nguyen - hungnh@smartosc.com
 * Date: 16/08/2018
 * Time: 09:56
 */

namespace SM\Product\Repositories\ProductManagement\ProductOptions;

use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\GiftCard\Model\Giftcard;
use Magento\Store\Model\StoreManagerInterface;
use SM\Product\Repositories\ProductManagement\ProductOptions;
use SM\Product\Repositories\ProductManagement\ProductPrice;

class M2EEGiftCard extends ProductOptions
{

    /**
     * @var \Magento\GiftCard\Block\Catalog\Product\View\Type\Giftcard
     */
    protected $giftCardViewBlock;
    /**
     * @var \Magento\Store\Model\Store
     */
    protected $storeManager;

    public function __construct(
        ObjectManagerInterface $objectManager,
        Product $catalogProduct,
        Registry $registry,
        ProductFactory $productFactory,
        ProductPrice $productPrice,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager  = $storeManager;
        parent::__construct($objectManager, $catalogProduct, $registry, $productFactory, $productPrice);
    }

    /**
     * @return \Magento\GiftCard\Block\Catalog\Product\View\Type\Giftcard
     */
    protected function getGiftCardViewBlock()
    {
        if (is_null($this->giftCardViewBlock)) {
            $this->giftCardViewBlock = $this->getObjectManager()
                                            ->create('\Magento\GiftCard\Block\Catalog\Product\View\Type\Giftcard');
        }

        return $this->giftCardViewBlock;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getGiftCardOption(\Magento\Catalog\Model\Product $product)
    {
        $this->resetProductInBlock($product);

        return [
            'isAllowPreview'       => false,
            'isAllowDesignSelect'  => false,
            'isAllowMessage'       => $this->getGiftCardViewBlock()->isMessageAvailable($product),
            'isAllowHeadline'      => false,
            'isAllowEmail'         => $this->getGiftCardViewBlock()->isEmailAvailable($product),
            'isAllowDeliveryDate'  => false,
            'isAllowOpenAmount'    => $this->getGiftCardViewBlock()->isOpenAmountAvailable($product),
            'isFixedAmount'        => $this->getGiftCardViewBlock()->isAmountAvailable($product),
            'isPhysicalValue'      => $product->getData('giftcard_type') == Giftcard::TYPE_PHYSICAL,
            'isCombinedValue'      => $product->getData('giftcard_type') == Giftcard::TYPE_COMBINED,
            'isVirtualValue'       => $product->getData('giftcard_type') == Giftcard::TYPE_VIRTUAL,
            'isAllowGiftWrapping'  => $product->getGiftWrappingAvailable(),
            'giftWrappingPrice'    => $product->getGiftWrappingPrice(),
            'getAmountOptions'     => $this->getAmounts($product),
            'getAmountOptionValue' => $this->getGiftCardViewBlock()->getAmountSettingsJson($product),
            'getMinCustomAmount'   => $product->getOpenAmountMin(),
            'getMaxCustomAmount'   => $product->getOpenAmountMax(),
            'getTimezones'         => false,
            'getGiftcardTemplates' => false,
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getAmounts($product)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        $result = [];
        foreach ($product->getGiftcardAmounts() as $amount) {
            if ($amount['website_id'] == '0' || $amount['website_id'] == $websiteId) {
                $result[] = $amount['website_value'];
            }
        }
        sort($result);
        return $result;
    }
}
