<?php

namespace ECInternet\JetTheme\ViewModel;

use Magento\Framework\Exception\LocalizedException;

class ThemeHtmlTitle implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $_registry;

    /**
     * ThemeHtmlTitle constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_registry = $registry;
    }

    /**
     * @return string|null
     */
    public function getCurrentCategoryImage()
    {
        /** @var \Magento\Catalog\Model\Category $currentCategory */
        $currentCategory = $this->_registry->registry('current_category');
        if ($currentCategory) {
            try {
                return $currentCategory->getImageUrl();
            } catch (LocalizedException $e) {
                error_log("ThemeHtmlTitle - Unable to get category image url - {$e->getMessage()}");
            }
        }

        return null;
    }
}