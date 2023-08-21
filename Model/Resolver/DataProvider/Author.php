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
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Author
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Author
{
    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * Author constructor.
     * @param AuthorRepositoryInterface $authorRepository
     * @param State $state
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        AuthorRepositoryInterface $authorRepository,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->authorRepository = $authorRepository;
        $this->state = $state;
        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->scopeConfig = $scopeConfig;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * @param string $authorId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $authorId): array
    {
        $author = $this->authorRepository->getFactory()->create();
        $author->getResource()->load($author, $authorId);

        if (!$author->isActive()) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($author, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    ScopeInterface::SCOPE_STORE
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
            'featured_image',
            'filtered_content',
            'short_filtered_content'
        ];

        $data['author_id'] = $author->getId();

        foreach ($keys as $key) {
            $method = 'get' . str_replace(
                    '_',
                    '',
                    ucwords($key, '_')
                );
            $data[$key] = $author->$method();
            if ($key === 'author_url') {
                $data[$key] = str_replace(
                    '/' . $this->scopeResolver->getScope()->getCode() . '/',
                    '/',
                    $data[$key]
                );
            }
        }

        return $data;
    }
}
