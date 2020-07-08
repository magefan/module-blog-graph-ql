<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\PostRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var Tag
     */
    private $tag;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var Author
     */
    private $author;

    /**
     * Post constructor.
     * @param PostRepositoryInterface $postRepository
     * @param FilterEmulate $widgetFilter
     * @param Tag $tag
     * @param Category $category
     * @param Author $author
     */
    public function __construct(
        PostRepositoryInterface $postRepository,
        FilterEmulate $widgetFilter,
        Tag $tag,
        Category $category,
        Author $author
    ) {
        $this->postRepository = $postRepository;
        $this->widgetFilter = $widgetFilter;
        $this->tag = $tag;
        $this->category = $category;
        $this->author = $author;
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

        return $this->getDynamicData($post, $fields);
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
            }
        }

        if (null === $fields || array_key_exists('tags', $fields)) {
            $tags = [];
            foreach ($post->getRelatedTags() as $tag) {
                $tags[] = $this->tag->getDynamicData(
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
            foreach ($post->getRelatedPosts() as $relatedPost) {
                $relatedPosts[] = $this->getDynamicData(
                    $relatedPost,
                    isset($fields['related_posts']) ? $fields['related_posts'] : null
                );
            }
            $data['related_posts'] = $relatedPosts;
        }

        /* Do not use check for null === $fields here */
        if (is_array($fields) && array_key_exists('related_products', $fields)) {
            $relatedProducts = [];
            foreach ($post->getRelatedProducts() as $relatedProduct) {
                $relatedProducts[] = $relatedProduct->getSku();
            }
            $data['related_products'] = $relatedProducts;
        }

        if (null === $fields || array_key_exists('categories', $fields)) {
            $categories = [];
            foreach ($post->getParentCategories() as $category) {
                $categories[] = $this->category->getDynamicData(
                    $category,
                    isset($fields['categories']) ? $fields['categories'] : null
                );
            }
            $data['categories'] = $categories;
        }

        if (null === $fields || array_key_exists('author', $fields)) {
            if ($author = $post->getAuthor()) {
                $data['author'] = $this->author->getDynamicData(
                    $author
                //isset($fields['author']) ? $fields['author'] : null
                );
            }
        }

        return $data;
    }
}
