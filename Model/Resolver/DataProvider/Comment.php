<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\CommentRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Comment
 */
class Comment
{
    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * Comment constructor.
     * @param CommentRepositoryInterface $commentRepository
     */
    public function __construct(
        CommentRepositoryInterface $commentRepository
    )
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * @param string $commentId
     * @param null $fields
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $commentId, $fields = null): array
    {
        $comment = $this->commentRepository->getFactory()->create();
        $comment->getResource()->load($comment, $commentId);

        if (!$comment->isActive()) {
            throw new NoSuchEntityException();
        }

        return $comment->getDynamicData($fields);
    }
}
