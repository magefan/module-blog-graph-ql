<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\PostRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Post
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Post
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

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
     * Post constructor.
     * @param PostRepositoryInterface $postRepository
     * @param FilterEmulate           $widgetFilter
     * @param State                   $state
     * @param DesignInterface         $design
     * @param ThemeProviderInterface  $themeProvider
     * @param ScopeConfigInterface    $scopeConfig
     */
    public function __construct(
        PostRepositoryInterface $postRepository,
        FilterEmulate $widgetFilter,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->postRepository = $postRepository;
        $this->widgetFilter   = $widgetFilter;
        $this->state          = $state;
        $this->design         = $design;
        $this->themeProvider  = $themeProvider;
        $this->scopeConfig    = $scopeConfig;
    }

    /**
     * @param string $postId
     * @param array|null $fields
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $postId, $fields = null): array
    {
        $post = $this->postRepository->getFactory()->create();
        $post->getResource()->load($post, $postId);

        if (!$post->isActive()) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($post, $fields, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);

                $data = $post->getDynamicData($fields);

                return $data;
            }
        );

        return $data;
    }
}
