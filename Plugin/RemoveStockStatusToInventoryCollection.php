<?php

namespace SM\Product\Plugin;


class RemoveStockStatusToInventoryCollection
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * SaveOutletIdToOrderAndQuote constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function aroundAddInStockFilterToCollection($subject, $proceed, $result)
    {
        $isConnectPOS = $this->registry->registry('is_connectpos');

        if (!$isConnectPOS) {
            $proceed($result);
            return;
        }

        $this->registry->unregister('is_connectpos');
        $this->registry->register('is_connectpos', true);
    }

    public function aroundAddIsInStockFilterToCollection($subject, $proceed, $result)
    {
        $isConnectPOS = $this->registry->registry('is_connectpos');

        if (!$isConnectPOS) {
            $proceed($result);
            return;
        }

        $this->registry->unregister('is_connectpos');
        $this->registry->register('is_connectpos', true);
    }
}
