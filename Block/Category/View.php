<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\JetTheme\Block\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Helper\Category as CatalogCategoryHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Template\Context;
use ECInternet\JetTheme\Logger\Logger;

class View extends \Magento\Catalog\Block\Category\View
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $_categoryRepository;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $_imageHelper;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    private $_currentCategory;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $_assetRepository;

    /**
     * View constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver            $layerResolver
     * @param \Magento\Framework\Registry                      $registry
     * @param \Magento\Catalog\Helper\Category                 $categoryHelper
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Helper\ImageFactory             $imageHelper
     * @param \Magento\Framework\View\Asset\Repository         $assetRepository
     * @param \ECInternet\JetTheme\Logger\Logger               $logger
     * @param array                                            $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Registry $registry,
        CatalogCategoryHelper $categoryHelper,
        CategoryRepositoryInterface $categoryRepository,
        ImageFactory $imageHelper,
        AssetRepository $assetRepository,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $layerResolver, $registry, $categoryHelper, $data);

        $this->_categoryRepository = $categoryRepository;
        $this->_imageHelper        = $imageHelper;
        $this->_assetRepository    = $assetRepository;
        $this->_logger             = $logger;
    }

    /**
     * Is the current category top-level?
     *
     * @return bool
     */
    public function isTopLevel()
    {
        $this->log('isTopLevel()');

        // Don't use triple equals even though it's supposed to return int
        return $this->getCurrentCategory()->getLevel() == 2;
    }

    /**
     * @return \Magento\Catalog\Api\Data\CategoryInterface[]
     */
    public function getAllChildren()
    {
        $this->log('getAllChildren()');

        $children = [];

        // Cache current category id
        $currentCategoryId = $this->getCategory()->getId();
        $this->log('getAllChildren() - Current CategoryId', [$currentCategoryId]);

        $childIds = $this->getCategory()->getAllChildren(true);
        foreach ($childIds as $childId) {
            if ($childId !== $currentCategoryId) {
                try {
                    $childCategory = $this->_categoryRepository->get($childId);

                    // Add name to array as key so we can sort
                    $children[$childCategory->getName()] = $childCategory;
                } catch (NoSuchEntityException $e) {
                    $this->log("Unable to lookup category [$childId] - {$e->getMessage()}");
                }
            }
        }

        ksort($children);

        return $children;
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     *
     * @return string|null
     */
    public function getCategoryUrl(
        CategoryInterface $category
    ) {
        $this->log('getCategoryUrl()', [$category->getId(), $category->getName()]);

        if ($category instanceof Category) {
            $this->log('getCategoryUrl()', [$category->getData()]);

            return $category->getUrl();
        }
        
        return null;
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     *
     * @return bool|string|null
     */
    public function getCategoryThumbnailUrl(
        CategoryInterface $category
    ) {
        $this->log('getCategoryThumbnailUrl()', [$category->getId(), $category->getName()]);

        if ($category instanceof Category) {
            $this->log('getCategoryThumbnailUrl()', [$category->getData()]);

            try {
                if ($imageUrl = $category->getImageUrl('thumbnail')) {
                    return $imageUrl;
                } else {
                    return $this->getThumbnailPlaceholderImage();
                }
            } catch (LocalizedException $e) {
                $this->log("Unable to get category thumbnail image - {$e->getMessage()}");
            }
        }
        
        return null;
    }

    public function getThumbnailPlaceholderImage()
    {
        // TODO: Use a Magento class to get the prefix
        return 'pub/media/catalog/product/placeholder/' . $this->_scopeConfig->getValue('catalog/placeholder/thumbnail_placeholder');
    }

    /**
     * Current category singleton
     *
     * @return \Magento\Catalog\Model\Category
     */
    private function getCategory()
    {
        $this->log('getCategory()');

        // TODO: We may not need a local variable for _currentCategory since it's only used here
        if ($this->_currentCategory === null) {
            $this->_currentCategory = $this->getCurrentCategory();
        }

        return $this->_currentCategory;
    }

    /**
     * Write to extension log
     *
     * @param mixed $message
     * @param array $extra
     */
    private function log($message, $extra = [])
    {
        $this->_logger->info('Block/Category/View - ' . $message, $extra);
    }
}
