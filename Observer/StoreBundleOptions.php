<?php


namespace SM\Product\Observer;


class StoreBundleOptions implements \Magento\Framework\Event\ObserverInterface
{
	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	private $storeManager;
	
	/**
	 * @var \Magento\Bundle\Model\OptionFactory
	 */
	private $bundleOption;
	/**
	 * @var \Magento\Framework\Registry
	 */
	private $registry;
	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	private $objectManager;
	/**
	 * @var \Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory
	 */
	private $selectionCollectionFactory;
	/**
	 * @var \Magento\Catalog\Model\Config
	 */
	private $config;
	
	public function __construct(
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Bundle\Model\OptionFactory $bundleOption,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory $selectionCollectionFactory,
		\Magento\Catalog\Model\Config $config
	) {
		$this->storeManager = $storeManager;
		$this->bundleOption = $bundleOption;
		$this->registry = $registry;
		$this->objectManager = $objectManager;
		$this->selectionCollectionFactory = $selectionCollectionFactory;
		$this->config = $config;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$productIds = $observer->getData('products');
		$bundleOptions = $this->getBundleOptionsCollections($productIds);
		
		$this->registry->unregister('sm_product_bundle_options');
		$this->registry->register('sm_product_bundle_options', $bundleOptions->getItems());
		
		$optionIds = $bundleOptions->getAllIds();
		
		$selectionCollections = $this->getBundleProductSelections($optionIds);
		$this->registry->unregister('sm_product_bundle_selections');
		$this->registry->register('sm_product_bundle_selections', $selectionCollections->getItems());
	}
	
	/**
	 * @param array $productIds
	 * @return \Magento\Bundle\Model\ResourceModel\Option\Collection
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	private function getBundleOptionsCollections(array $productIds)
	{
		$storeId = $this->getStoreManager()->getStore()->getId();
		/** @var \Magento\Bundle\Model\ResourceModel\Option\Collection $optionsCollection */
		$optionsCollection = $this->bundleOption->create()
			->getResourceCollection();
		$this->setProductIdFilter($optionsCollection, $productIds);
		
		$optionsCollection->setPositionOrder();
		
		if ($storeId instanceof \Magento\Store\Model\Store) {
			$storeId = $storeId->getId();
		}
		
		$optionsCollection->joinValues($storeId);
		
		return $optionsCollection;
	}
	
	/**
	 * Retrieve bundle selections collection based on used options
	 *
	 * @param array $optionIds
	 * @return \Magento\Bundle\Model\ResourceModel\Selection\Collection
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	protected function getBundleProductSelections($optionIds)
	{
		$storeId = $this->getStoreManager()->getStore()->getId();
		
		$selectionsCollection = $this->selectionCollectionFactory->create();
		$selectionsCollection
			->addAttributeToSelect($this->config->getProductAttributes())
			->addAttributeToSelect('tax_class_id') //used for calculation item taxes in Bundle with Dynamic Price
			->setFlag('product_children', true)
			->setPositionOrder()
			->addStoreFilter($storeId)
			->setStoreId($storeId)
			->addFilterByRequiredOptions()
			->setOptionIdsFilter($optionIds);
		
		
		return $selectionsCollection;
	}

    /**
	 * Sets product id filter
	 * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
	 * @param array $productIds
	 * @return $this
	 */
	private function setProductIdFilter($collection, $productIds)
	{
		$productTable = 'catalog_product_entity';
		$linkField = $this->getConnection()->getAutoIncrementField($productTable);
		$collection->getSelect()->join(
			['cpe' => $productTable],
			'cpe.'.$linkField.' = main_table.parent_id',
			[]
		)->where(
			"cpe.entity_id in (?)",
			$productIds
		);
		
		return $this;
	}
	
	private function getStoreManager()
	{
		return $this->storeManager;
	}
	
	private function getConnection()
	{
		return $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
	}
}
