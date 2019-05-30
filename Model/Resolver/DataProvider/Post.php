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
     * Post constructor.
     * @param PostRepositoryInterface $postRepository
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        PostRepositoryInterface $postRepository,
        FilterEmulate $widgetFilter
    ) {
        $this->postRepository = $postRepository;
        $this->widgetFilter = $widgetFilter;
    }

    /**
     * @param int $postId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(int $postId): array
    {
        $post = $this->postRepository->getById($postId);

        if (false === $post->isActive()) {
            throw new NoSuchEntityException();
        }

        $renderedContent = $this->widgetFilter->filter($post->getContent());

        $postData = [
            'url_key' => $post->getIdentifier(),
            'identifier' => $post->getIdentifier(),
            'title' => $post->getTitle(),
            'meta_title' => $post->getMetaTitle(),
            'meta_keywords' => $post->getMetaKeywords(),
            'meta_description' => $post->getMetaDescription(),
            'og_title' => $post->getOgTitle(),
            'og_description' => $post->getOgDescription(),
            'og_image' => $post->getOgImage(),
            'og_type' => $post->getOgType(),
            'content_heading' => $post->getData('content_heading'),
            'content' => $renderedContent,
            'creation_time' => $post->getData('creation_time'),
            'update_time' => $post->getUpdatedAt(),
            'publish_time' => $post->getPublishDate(),
            'is_active' => $post->getData('is_active'),
            'include_in_recent' => $post->getData('include_in_recent'),
            'position' => $post->getData('position'),
            'featured_img' => $post->getFeaturedImage(),
            'author' => $post->getAuthor()->getName(), //object Author
            'author_id' => $post->getData('author_id'),
            'page_layout' => $post->getData('page_layout'),
            'layout_update_xml' => $post->getData('layout_update_xml'),
            'custom_theme' => $post->getData('custom_theme'),
            'custom_layout' => $post->getData('custom_layout'),
            'custom_layout_update_xml' => $post->getData('custom_layout_update_xml'),
            'custom_theme_from' => $post->getData('custom_theme_from'),
            'custom_theme_to' => $post->getData('custom_theme_to'),
            //'media_gallery' => $post->getGalleryImages(), //array
            'media_gallery' => $post->getData('media_gallery'),
            'secret' => $post->getSecret(),
            'views_count' => $post->getData('views_count'),
            'is_recent_posts_skip' => $post->getData('is_recent_posts_skip'),
            'short_content' => $post->getData('short_content'),
            //'fb_auto_publish' => $post->getData('fb_auto_publish'), // BlogPlus
            //'fb_post_format' => $post->getData('fb_post_format'), // BlogPlus
            //'fb_published' => $post->getData('fb_published'), // BlogPlus
            //'rp_conditions_serialized' => $post->getData('rp_conditions_serialized'), // BlogPlus
            //'rp_conditions_generation_time' => $post->getData('rp_conditions_generation_time'), // BlogPlus
        ];
        return $postData;
    }
}
