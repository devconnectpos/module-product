<?php

namespace SM\Product\Repositories\ProductManagement;

use Magento\Catalog\Model\Product;

/**
 * Class ProductMediaGalleryImages
 *
 * @package SM\Product\Repositories\ProductManagement
 */
class ProductMediaGalleryImages
{
    /**
     * @var \SM\Product\Helper\ProductImageHelper
     */
    protected $productImageHelper;
    /**
     * @var Product\Gallery\ReadHandler
     */
    protected $galleryReadHandler;
    
    /**
     * @var array
     */
    private $cacheImage = [];
    
    public function __construct(
        \SM\Product\Helper\ProductImageHelper $productImageHelper,
        \Magento\Catalog\Model\Product\Gallery\ReadHandler $galleryReadHandler
    ) {
        $this->productImageHelper = $productImageHelper;
        $this->galleryReadHandler = $galleryReadHandler;
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
            }
            
            if (empty($media)) {
                $media[] = $this->productImageHelper->getImageUrl($product);
            }
            
            $this->cacheImage[$product->getId()] = $media;
        }

        return $this->cacheImage[$product->getId()];
    }
}
