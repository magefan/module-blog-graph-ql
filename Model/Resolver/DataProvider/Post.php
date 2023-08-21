<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\PostRepositoryInterface;
use Magefan\Blog\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Post
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Post
{
    /**
     * @var Tag
     */
    private $tagDataProvider;

    /**
     * @var Category
     */
    private $categoryDataProvider;

    /**
     * @var Author
     */
    private $authorDataProvider;

    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

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
     * Post constructor.
     * @param PostRepositoryInterface $postRepository
     * @param Tag $tagDataProvider
     * @param Category $categoryDataProvider
     * @param Author $authorDataProvider
     * @param State $state
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        PostRepositoryInterface $postRepository,
        Tag $tagDataProvider,
        Category $categoryDataProvider,
        Author $authorDataProvider,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->postRepository = $postRepository;
        $this->tagDataProvider = $tagDataProvider;
        $this->categoryDataProvider = $categoryDataProvider;
        $this->authorDataProvider = $authorDataProvider;
        $this->state = $state;
        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->scopeConfig = $scopeConfig;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * @param string $postId
     * @param array|null $fields
     * @param null $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $postId, $fields = null, $storeId = null): array
    {
        $post = $this->postRepository->getFactory()->create();
        $post->getResource()->load($post, $postId);

        if (!$post->isActive()) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($post, $fields, &$data, $storeId) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);
                $post->setStoreId((int)$storeId);
                $data = $this->getDynamicData($post, $fields);

                return $data;
            }
        );

        return $data;
    }

    /**
     * Prepare all additional data
     * @param $post
     * @param null|array $fields
     * @return array
     */
    public function getDynamicData($post, $fields = null)
    {
        $data = $post->getData();

        $keys = [
            'og_image',
            'og_type',
            'og_description',
            'og_title',
            'meta_description',
            'meta_title',
            'short_filtered_content',
            'filtered_content',
            'first_image',
            'featured_image',
            'featured_list_image',
            'post_url',
        ];

        foreach ($keys as $key) {
            if (null === $fields || array_key_exists($key, $fields)) {
                $method = 'get' . str_replace(
                        '_',
                        '',
                        ucwords($key, '_')
                    );
                $data[$key] = $post->$method();
                if ($key === 'post_url') {
                    $data[$key] = str_replace(
                        '/' . $this->scopeResolver->getScope()->getCode() . '/',
                        '/',
                        $data[$key]
                    );
                }
            }
        }

        if (null === $fields || array_key_exists('tags', $fields)) {
            $tags = [];
            foreach ($post->getRelatedTags() as $tag) {
                $tags[] = $this->tagDataProvider->getDynamicData(
                    $tag
                // isset($fields['tags']) ? $fields['tags'] : null
                );
            }
            $data['tags'] = $tags;
        }

        /* Do not use check for null === $fields here
         * this checks is used for REST, and related data was not provided via reset */
        if (is_array($fields) && array_key_exists('related_posts', $fields)) {
            $relatedPosts = [];

            $isEnabled = $this->scopeConfig->getValue(
                Config::XML_RELATED_POSTS_ENABLED,
                ScopeInterface::SCOPE_STORE
            );

            if ($isEnabled) {
                $pageSize = (int) $this->scopeConfig->getValue(
                    Config::XML_RELATED_POSTS_NUMBER,
                    ScopeInterface::SCOPE_STORE
                );

                $postCollection = $post->getRelatedPosts()
                    ->addActiveFilter()
                    ->setPageSize($pageSize ?: 5);
                foreach ($postCollection as $relatedPost) {
                    $relatedPosts[] = $this->getDynamicData(
                        $relatedPost,
                        isset($fields['related_posts']) ? $fields['related_posts'] : null
                    );
                }
            }

            $data['related_posts'] = $relatedPosts;
        }

        /* Do not use check for null === $fields here */
        if (is_array($fields) && array_key_exists('related_products', $fields)) {
            $relatedProducts = [];

            $isEnabled = $this->scopeConfig->getValue(
                Config::XML_RELATED_PRODUCTS_ENABLED,
                ScopeInterface::SCOPE_STORE
            );

            if ($isEnabled) {
                $pageSize = (int) $this->scopeConfig->getValue(
                    Config::XML_RELATED_PRODUCTS_NUMBER,
                    ScopeInterface::SCOPE_STORE
                );

                $productCollection = $post->getRelatedProducts()
                    ->setPageSize($pageSize ?: 5);
                foreach ($productCollection as $relatedProduct) {
                    $relatedProducts[] = $relatedProduct->getSku();
                }
            }
            $data['related_products'] = $relatedProducts;
        }

        if (null === $fields || array_key_exists('categories', $fields)) {
            $categories = [];
            foreach ($post->getParentCategories() as $category) {
                $categories[] = $this->categoryDataProvider->getDynamicData(
                    $category,
                    isset($fields['categories']) ? $fields['categories'] : null
                );
            }
            $data['categories'] = $categories;
        }

        if (null === $fields || array_key_exists('author', $fields)) {
            if ($author = $post->getAuthor()) {
                $data['author'] = $this->authorDataProvider->getDynamicData(
                    $author
                //isset($fields['author']) ? $fields['author'] : null
                );
            }
        }

        if (is_array($fields) && array_key_exists('canonical_url', $fields)) {
            $data['canonical_url'] = $post->getCanonicalUrl();
        }

        return $data;
    }
}
