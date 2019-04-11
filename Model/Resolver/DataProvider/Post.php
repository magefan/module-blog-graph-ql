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
     * @param int $pageId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(int $postId): array
    {
        $post= $this->postRepository->getById($postId);

        if (false === $post->isActive()) {
            throw new NoSuchEntityException();
        }

        $renderedContent = $this->widgetFilter->filter($post->getContent());

        $pageData = [
            'url_key' => $post->getIdentifier(),
            /*PageInterface::TITLE => $page->getTitle(),
            PageInterface::CONTENT => $renderedContent,
            PageInterface::CONTENT_HEADING => $page->getContentHeading(),
            PageInterface::PAGE_LAYOUT => $page->getPageLayout(),
            PageInterface::META_TITLE => $page->getMetaTitle(),
            PageInterface::META_DESCRIPTION => $page->getMetaDescription(),
            PageInterface::META_KEYWORDS => $page->getMetaKeywords(),*/
        ];
        return $pageData;
    }
}
