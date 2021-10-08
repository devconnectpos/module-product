<?php

namespace SM\Product\Repositories\ProductManagement;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;

/**
 * Class ProductMediaGalleryImages
 *
 * @package SM\Product\Repositories\ProductManagement
 */
class ProductMediaGalleryImages
{
    /**
     * @var ReadHandler
     */
    protected $galleryReadHandler;

    /**
     * @var ProductAttributeMediaGalleryManagementInterface
     */
    private $productAttributeMediaGallery;

    /**
     * @var array
     */
    private $cacheImage = [];

    /**
     * Catalog Image Helper
     *
     * @var Image
     */
    protected $imageHelper;

    public function __construct(
        ReadHandler $galleryReadHandler,
        ProductAttributeMediaGalleryManagementInterface $productAttributeMediaGallery,
        Image $imageHelper
    ) {
        $this->galleryReadHandler = $galleryReadHandler;
        $this->productAttributeMediaGallery = $productAttributeMediaGallery;
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param Product $product
     */
    public function addGallery(Product $product)
    {
        $this->galleryReadHandler->execute($product);
    }

    /**
     * @param Product $product
     *
     * @return mixed
     * @throws \Exception
     */
    public function getMediaGalleryImages(Product $product)
    {
        if (!isset($this->cacheImage[$product->getId()])) {
            $this->addGallery($product);
            $media = [];
            $mediaGalleryImages = $product->getMediaGalleryImages();

            if ($mediaGalleryImages && $mediaGalleryImages->getSize() > 0) {
                foreach ($mediaGalleryImages as $mediaGalleryImage) {
                    $media[] = $mediaGalleryImage['url'];
                }
            } else {
                $mediaGalleryImages = $this->getMediaGallery($product->getSku());

                foreach ($mediaGalleryImages as $mediaGalleryImage) {
                    $media[] = $this->imageHelper->init($product, 'product_page_image_small')
                        ->setImageFile($mediaGalleryImage->getFile())
                        ->getUrl();
                }
            }

            $this->cacheImage[$product->getId()] = $media;
        }

        return $this->cacheImage[$product->getId()];
    }

    /**
     * @param string $sku
     * @return ProductAttributeMediaGalleryEntryInterface[]
     */
    public function getMediaGallery($sku)
    {
        $gallery = [];
        try {
            $gallery = $this->productAttributeMediaGallery->getList($sku);
        } catch (\Exception $exception) {
        }

        return $gallery;
    }
}
