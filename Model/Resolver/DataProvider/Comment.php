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
            'parent_id' => $comment->getParentComment(),
            'post_id' => $comment->getPostId(),
            'customer_id' => $comment->getCustomerId(),
            'admin_id' => $comment->getAdminId(),
            'is_active' => $comment->isActive(),
            'author_type' => $comment->getAuthorType(),
            'author_nickname' => $comment->getAuthorNickname(),
            'author_email' => $comment->getAuthorEmail(),
            'text' => $comment->getText(),
            'creation_time' => $comment->getPublishDate(),
            'update_time' => $comment->getUpdateTime(),
            //'child_id' => $comment->getChildComments(), //object Comment\Collection
            //'post' => $comment->getPost(), //object Post
            //'author' => $comment->getAuthor(), //object Author
        ];
        return $commentData;
    }
}
