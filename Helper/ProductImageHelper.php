<?php
/**
 * Created by KhoiLe - mr.vjcspy@gmail.com
 * Date: 7/22/17
 * Time: 10:52 AM
 */

namespace SM\Product\Helper;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use SM\Product\Repositories\ProductManagement\ProductMediaGalleryImages;

class ProductImageHelper extends AbstractHelper
{

    /**
     * Custom directory relative to the "media" folder
     */
    const DIRECTORY = 'retail/pos';
    const CATALOG_DIRECTORY = '/catalog/product';

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\Framework\Image\Factory
     */
    protected $imageFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \SM\Product\Repositories\ProductManagement\ProductMediaGalleryImages
     */
    protected $productMediaGalleryImages;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectoryRead;

    /**
     * Catalog Image Helper
     *
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Framework\Filesystem              $filesystem
     * @param \Magento\Framework\Image\AdapterFactory    $imageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ProductMediaGalleryImages                  $productMediaGalleryImages
     * @param \Magento\Catalog\Helper\Image              $imageHelper
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        AdapterFactory $imageFactory,
        StoreManagerInterface $storeManager,
        ProductMediaGalleryImages $productMediaGalleryImages,
        \Magento\Catalog\Helper\Image $imageHelper
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->mediaDirectoryRead = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->imageFactory = $imageFactory;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->productMediaGalleryImages = $productMediaGalleryImages;
        parent::__construct($context);
    }

    /**
     * First check this file on FS
     *
     * @param string $filename
     *
     * @return bool
     */
    protected function fileExists($filename)
    {
        if ($this->mediaDirectory->isFile($filename)) {
            return true;
        }

        return false;
    }

    /**
     * Resize image
     *
     * @param          $image
     * @param int      $width
     * @param null     $height
     *
     * @return string
     * @throws \Exception
     */
    public function resize($image, $width = 200, $height = null)
    {
        $mediaFolder = self::DIRECTORY;

        $path = $mediaFolder.'/cache';
        if ($width !== null) {
            $path .= '/'.$width.'x';
            if ($height !== null) {
                $path .= $height;
            }
        }

        $absolutePath = $this->mediaDirectoryRead->getAbsolutePath(self::CATALOG_DIRECTORY.$image);
        $imageResized = $this->mediaDirectory->getAbsolutePath($path).$image;

        if (!$this->fileExists($path.$image)) {
            if ($this->fileExists(self::CATALOG_DIRECTORY.$image)) {
                $imageFactory = $this->imageFactory->create();
                $imageFactory->open($absolutePath);
                $imageFactory->constrainOnly(true);
                $imageFactory->keepTransparency(true);
                $imageFactory->keepFrame(false);
                $imageFactory->keepAspectRatio(true);
                $imageFactory->resize($width, $height);
                $imageFactory->save($imageResized);
            } else {
                return "";
            }
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).$path.$image;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string|null
     * @throws \Exception
     */
    public function getImageUrl(Product $product)
    {
        if (is_null($product->getImage()) || $product->getImage() === 'no_selection' || !$product->getImage()) {
            $mediaGalleryImages = $this->productMediaGalleryImages->getMediaGalleryImages($product);

            if (count($mediaGalleryImages) > 0) {
                $firstImage = $mediaGalleryImages[0] ?? null;
                if (is_object($firstImage)) {
                    return $this->imageHelper->init($product, 'product_page_image_small')
                        ->setImageFile($firstImage->getFile())
                        ->getUrl();
                }
                return $firstImage;
            }

            return null;
        }

        return $this->resize($product->getImage());
    }

}
