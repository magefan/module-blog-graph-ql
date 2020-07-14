<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magefan\Blog\Helper\Config;
use Magefan\Blog\Api\CommentRepositoryInterface;
use Magefan\Blog\Model\Config\Source\AuthorType;
use Magefan\Blog\Api\PostRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AddCommentToPost
 */
class AddCommentToPost implements ResolverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

    /**
     * AddCommentToPost constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CommentRepositoryInterface $commentRepository
     * @param PostRepositoryInterface $postRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CommentRepositoryInterface $commentRepository,
        PostRepositoryInterface $postRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->scopeConfig->getValue(Config::XML_PATH_EXTENSION_ENABLED, ScopeInterface::SCOPE_STORE)) {
            throw new GraphQlNoSuchEntityException(__(
                strrev('golB > noisnetxE nafegaM > noitarugifnoC > serotS ni noisnetxe elbane esaelP')
            ));
        }

        if (!$this->scopeConfig->getValue(Config::GUEST_COMMENT, ScopeInterface::SCOPE_STORE)
            && !$context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('Login to submit comment.'));
        }

        if (empty($args['input']['post_id'])) {
            throw new GraphQlInputException(__('Post ID should be specified'));
        }
        if (empty($args['input']['text'])) {
            throw new GraphQlInputException(__('Comment should be specified'));
        }
        if (empty($args['input']['author_nickname'])) {
            throw new GraphQlInputException(__('Author should be specified'));
        }
        if (empty($args['input']['author_email'])) {
            throw new GraphQlInputException(__('Author email should be specified'));
        }

        $postId = $args['input']['post_id'];
        $text = $args['input']['text'];
        $author = $args['input']['author_nickname'];
        $email = $args['input']['author_email'];

        empty($args['input']['parent_id']) ? $parentId = 0 : $parentId = $args['input']['parent_id'];

        $comment = $this->commentRepository->getFactory()->create();
        $comment->setData([
            'post_id' => $postId,
            'text' => $text,
            'author_nickname' => $author,
            'author_email' => $email
        ]);

        /* Set default status */
        $comment->setStatus(
            $this->scopeConfig->getValue(Config::COMMENT_STATUS, ScopeInterface::SCOPE_STORE)
        );

        /* Guest can post review */
        $comment->setCustomerId(0)->setAuthorType(AuthorType::GUEST);

        try {
            try {
                $post = $this->postRepository->getById($postId);

                if (!$post->getIsActive()) {
                    throw new GraphQlNoSuchEntityException(__('You cannot post comment. Blog post is not longer exist.'));
                }
            } catch (\Exception $e) {

            }

            if ($parentId) {
                try {
                    $parentComment = $this->commentRepository->getById($parentId);

                    if (!$parentComment->isActive()) {
                        throw new GraphQlNoSuchEntityException(__('You cannot reply to this comment. Comment is not longer exist.'));
                    }
                    if (!$parentComment->getPost()
                        || $parentComment->getPost()->getId() != $post->getId()
                        || $parentComment->isReply()
                    ) {
                        throw new GraphQlNoSuchEntityException(__('You cannot reply to this comment.'));
                    }

                    $comment->setParentId($parentComment->getId());
                } catch (\Exception $e) {

                }
            }

            $comment->save();
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        $commentsCollection = $post->getComments()
            ->addActiveFilter()
            ->addFieldToFilter('parent_id', 0)
            ->setPageSize($args['pageSize'])
            ->setCurPage($args['currentPage'])
            ->setOrder('creation_time', 'DESC');

        //possible division by 0
        if ($commentsCollection->getPageSize()) {
            $maxPages = ceil($commentsCollection->getSize() / $commentsCollection->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $commentsCollection->getCurPage();
        if ($commentsCollection->getCurPage() > $maxPages && $commentsCollection->getSize() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        $fields = $info ? $info->getFieldSelection(10) : null;

        $comments = [];
        foreach ($commentsCollection as $item) {
            $comments[] = $item->getDynamicData(isset($fields['comments']) ? $fields['comments'] : null);
        }

        return [
            'total_count' => $commentsCollection->getSize(),
            'total_pages' => $maxPages,
            'comments' => $comments
        ];
    }
}
