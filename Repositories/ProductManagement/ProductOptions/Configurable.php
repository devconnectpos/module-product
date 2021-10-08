<?php

namespace SM\Product\Repositories\ProductManagement\ProductOptions;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Format;

class Configurable extends \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
{
    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices|null
     */
    protected $variationPrices;
    /**
     * @var \Magento\Framework\Locale\Format|null
     */
    protected $localeFormat;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        array $data = [],
        \Magento\Framework\Locale\Format $localeFormat = null,
        \Magento\Customer\Model\Session $customerSession = null
    ) {
        $variationPrices = null;

        if (class_exists('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices')) {
            $variationPrices = ObjectManager::getInstance()->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices');
        }

        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $data,
            $localeFormat,
            $customerSession,
            $variationPrices
        );
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(Format::class);
        $this->variationPrices = $variationPrices;
    }

    public function getConfigurableJsonConfig()
    {
        $store = $this->getCurrentStore();
        $currentProduct = $this->getProduct();

        $options = $this->helper->getOptions($currentProduct, $this->getAllowProducts());
        $attributesData = $this->configurableAttributeData->getAttributesData($currentProduct, $options);

        $config = [
            'attributes' => $attributesData['attributes'],
            'template' => str_replace('%s', '<%- data.price %>', $store->getCurrentCurrency()->getOutputFormat()),
            'currencyFormat' => $store->getCurrentCurrency()->getOutputFormat(),
            'optionPrices' => $this->getOptionPrices(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'productId' => $currentProduct->getId(),
            'chooseText' => __('Choose an Option...'),
            'images' => $this->getOptionImages(),
            'index' => isset($options['index']) ? $options['index'] : [],
        ];

        if (!is_null($this->variationPrices)) {
            $config['prices'] = $this->variationPrices->getFormattedPrices($this->getProduct()->getPriceInfo());
        } else {
            $regularPrice = $currentProduct->getPriceInfo()->getPrice('regular_price');
            $finalPrice = $currentProduct->getPriceInfo()->getPrice('final_price');
            $config['prices'] = [
                'oldPrice' => [
                    'amount' => $this->localeFormat->getNumber($regularPrice->getAmount()->getValue()),
                ],
                'basePrice' => [
                    'amount' => $this->localeFormat->getNumber($finalPrice->getAmount()->getBaseAmount()),
                ],
                'finalPrice' => [
                    'amount' => $this->localeFormat->getNumber($finalPrice->getAmount()->getValue()),
                ],
            ];
        }

        if ($currentProduct->hasPreconfiguredValues() && !empty($attributesData['defaultValues'])) {
            $config['defaultValues'] = $attributesData['defaultValues'];
        }

        $config = array_merge($config, $this->_getAdditionalConfig());

        return $this->jsonEncoder->encode($config);
    }
}
