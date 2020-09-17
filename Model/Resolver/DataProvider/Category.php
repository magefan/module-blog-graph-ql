<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Category
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Category
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    private $design;

    /**
     * @var \Magento\Framework\View\Design\Theme\ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Category constructor.
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FilterEmulate               $widgetFilter
     * @param State                       $state
     * @param DesignInterface             $design
     * @param ThemeProviderInterface      $themeProvider
     * @param ScopeConfigInterface        $scopeConfig
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        FilterEmulate $widgetFilter,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->widgetFilter       = $widgetFilter;
        $this->state              = $state;
        $this->design             = $design;
        $this->themeProvider      = $themeProvider;
        $this->scopeConfig        = $scopeConfig;
    }

    /**
     * @param string $categoryId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $categoryId): array
    {
        $category = $this->categoryRepository->getFactory()->create();
        $category->getResource()->load($category, $categoryId);

        if (!$category->isActive()) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($category, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);

                $data = $category->getDynamicData();

                return $data;
            }
        );

        return $data;
    }
}
