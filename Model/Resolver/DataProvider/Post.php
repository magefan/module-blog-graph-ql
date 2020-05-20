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
     * @param string $postId
     * @param array $fields
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $postId, array $fields): array
    {
        $post = $this->postRepository->getFactory()->create();
        $post->getResource()->load($post, $postId);

        if (!$post->isActive()) {
            throw new NoSuchEntityException();
        }

        return $post->getDynamicData($fields);
    }
}
