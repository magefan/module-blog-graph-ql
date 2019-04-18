<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\CommentRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Comment
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Comment
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * Comment constructor.
     * @param CommentRepositoryInterface $commentRepository
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        CommentRepositoryInterface $commentRepository,
        FilterEmulate $widgetFilter
    ) {
        $this->commentRepository = $commentRepository;
        $this->widgetFilter = $widgetFilter;
    }

    /**
     * @param int $commentId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(int $commentId): array
    {
        $comment = $this->commentRepository->getById($commentId);

        if (false === $comment->isActive()) {
            throw new NoSuchEntityException();
        }

        $commentData = [
            'comment_id' => $comment->getData('comment_id'),
            'creation_time' => $comment->getPublishDate(),
            'is_active' => $comment->isActive(),
        ];
        return $commentData;
    }
}
