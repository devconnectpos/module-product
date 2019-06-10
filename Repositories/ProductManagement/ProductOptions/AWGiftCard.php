<?php
/**
 * Created by KhoiLe - mr.vjcspy@gmail.com
 * Date: 10/30/17
 * Time: 14:20
 */

namespace SM\Product\Repositories\ProductManagement\ProductOptions;

use Aheadworks\Giftcard\Model\Source\Entity\Attribute\GiftcardType;
use Magento\Catalog\Model\Product;
use SM\Product\Repositories\ProductManagement\ProductOptions;

class AWGiftCard extends ProductOptions
{

    /**
     * @var \Aheadworks\Giftcard\Block\Product\View
     */
    protected $giftCardViewBlock;

    /**
     * @return \Aheadworks\Giftcard\Block\Product\View
     */
    protected function getGiftCardViewBlock()
    {
        if (is_null($this->giftCardViewBlock)) {
            $this->giftCardViewBlock = $this->getObjectManager()->create('Aheadworks\Giftcard\Block\Product\View');
        }

        return $this->giftCardViewBlock;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    public function getGiftCardOption(Product $product)
    {
        $this->resetProductInBlock($product);
        $isPhysicalValue = false;
        if ($product->getData('aw_gc_type') == GiftcardType::VALUE_PHYSICAL) {
            $isPhysicalValue = true;
        }
        return [
            'isAllowPreview'      => $this->getGiftCardViewBlock()->isAllowPreview(),
            'isAllowDesignSelect' => $this->getGiftCardViewBlock()->isAllowDesignSelect(),
            'isAllowMessage'      => $this->getGiftCardViewBlock()->isAllowMessage(),
            'isAllowHeadline'     => $this->getGiftCardViewBlock()->isAllowHeadline(),
            'isAllowEmail'        => $this->getGiftCardViewBlock()->isAllowEmail(),
            'isAllowDeliveryDate' => $this->getGiftCardViewBlock()->isAllowDeliveryDate(),
            'isAllowOpenAmount'   => $this->getGiftCardViewBlock()->isAllowOpenAmount(),
            'isFixedAmount'       => $this->getGiftCardViewBlock()->isFixedAmount(),
            'isPhysicalValue'     => $isPhysicalValue,

            'getAmountOptions'     => $this->getGiftCardViewBlock()->getAmountOptions(),
            'getAmountOptionValue' => $this->getGiftCardViewBlock()->getAmountOptionValue(),
            'getMinCustomAmount'   => $this->getGiftCardViewBlock()->getMinCustomAmount(),
            'getMaxCustomAmount'   => $this->getGiftCardViewBlock()->getMaxCustomAmount(),
            'getFixedAmount'       => $this->getGiftCardViewBlock()->getFixedAmount(),
            'getTimezones'         => $this->getGiftCardViewBlock()->getTimezones(),
            'getGiftcardTemplates' => $this->getGiftCardViewBlock()->getGiftcardTemplates(),
        ];
    }
}
