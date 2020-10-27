<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Author
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Author
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

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
     * Author constructor.
     * @param AuthorRepositoryInterface $authorRepository
     * @param FilterEmulate             $widgetFilter
     * @param State                     $state
     * @param DesignInterface           $design
     * @param ThemeProviderInterface    $themeProvider
     * @param ScopeConfigInterface      $scopeConfig
     */
    public function __construct(
        AuthorRepositoryInterface $authorRepository,
        FilterEmulate $widgetFilter,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->authorRepository = $authorRepository;
        $this->widgetFilter     = $widgetFilter;
        $this->state            = $state;
        $this->design           = $design;
        $this->themeProvider    = $themeProvider;
        $this->scopeConfig      = $scopeConfig;
    }

    /**
     * @param string $authorId
     * @return array
     */
    public function getData(string $authorId): array
    {
        $author = $this->authorRepository->getFactory()->create();
        $author->getResource()->load($author, $authorId);

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($self, $author, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);

                $data = $this->getDynamicData($author);

                return $data;
            }
        );

        return $data;
    }

    /**
     * Prepare all additional data
     * @param $author
     * @param null $fields
     * @return mixed
     */
    public function getDynamicData($author, $fields = null)
    {
        $data = $author->getData();

        $keys = [
            'meta_description',
            'meta_title',
            'author_url',
            'name',
            'title',
            'identifier',
        ];

        $data['author_id'] = $author->getId();

        foreach ($keys as $key) {
            $method = 'get' . str_replace(
                    '_',
                    '',
                    ucwords($key, '_')
                );
            $data[$key] = $author->$method();
        }

        return $data;
    }
}