<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magefan\Blog\Helper\Config;
use Magefan\Blog\Model\CommentFactory;
use Magefan\Blog\Model\Config\Source\AuthorType;
use Magefan\Blog\Model\PostFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AddCommentToPostAsGuest
 */
class AddCommentToPostAsGuest implements ResolverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CommentFactory
     */
    private $commentFactory;

    /**
     * @var PostFactory
     */
    private $postFactory;

    /**
     * AddCommentToPostAsGuest constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CommentFactory $commentFactory
     * @param PostFactory $postFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CommentFactory $commentFactory,
        PostFactory $postFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->commentFactory = $commentFactory;
        $this->postFactory = $postFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->isEnabled()) {
            throw new GraphQlNoSuchEntityException(__($this->getStatusNotification()));
        }

        if (!$this->getConfig(Config::GUEST_COMMENT)) {
            throw new GraphQlAuthorizationException(__('Login to submit comment.'));
        }

        if (!isset($args['input']['post_id'])) {
            throw new GraphQlInputException(__('Post ID should be specified'));
        }
        if (!isset($args['input']['text'])) {
            throw new GraphQlInputException(__('Comment should be specified'));
        }
        if (!isset($args['input']['author_nickname'])) {
            throw new GraphQlInputException(__('Author should be specified'));
        }
        if (!isset($args['input']['author_email'])) {
            throw new GraphQlInputException(__('Author email should be specified'));
        }

        $postId = $args['input']['post_id'];
        $text = $args['input']['text'];
        $author = $args['input']['author_nickname'];
        $email = $args['input']['author_email'];
        $parentId = $args['input']['parent_id'];

        $comment = $this->commentFactory->create();
        $comment->setData([
            'post_id' => $postId,
            'text' => $text,
            'author_nickname' => $author,
            'author_email' => $email
        ]);

        /* Set default status */
        $comment->setStatus(
            $this->getConfig(Config::COMMENT_STATUS)
        );

        /* Guest can post review */
        $comment->setCustomerId(0)->setAuthorType(AuthorType::GUEST);

        try {
            $post = $this->initPost($postId);
            if (!$post) {
                throw new GraphQlNoSuchEntityException(__('You cannot post comment. Blog post is not longer exist.'));
            }

            if ($parentId) {
                $parentComment = $this->initParentComment($parentId);
                if (!$parentComment) {
                    throw new GraphQlNoSuchEntityException(__('You cannot reply to this comment. Comment is not longer exist.'));
                }
                if (!$parentComment->getPost()
                    || $parentComment->getPost()->getId() != $post->getId()
                    || $parentComment->isReply()
                ) {
                    throw new GraphQlNoSuchEntityException(__('You cannot reply to this comment.'));
                }

                $comment->setParentId($parentComment->getId());
            }

            $comment->save();
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        $commentsCollection = $post->getComments()
            ->addActiveFilter()
            ->addFieldToFilter('parent_id', 0)
            ->setOrder('creation_time', 'DESC');

        $fields = $info ? $info->getFieldSelection(10) : null;

        $comments = [];
        foreach ($commentsCollection as $item) {
            $comments[] = $item->getDynamicData(isset($fields['comments']) ? $fields['comments'] : null);
        }

        return [
            'total_count' => count($commentsCollection),
            'comments' => $comments
        ];
    }

    /**
     * @return string
     */
    private function getStatusNotification(): string
    {
        return strrev('golB > noisnetxE nafegaM > noitarugifnoC > serotS ni noisnetxe elbane esaelP');
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    private function isEnabled(int $storeId = null): bool
    {
        return (bool)$this->getConfig(Config::XML_PATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    private function getConfig(string $path, int $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $postId
     * @return bool|\Magefan\Blog\Model\Post
     */
    private function initPost(int $postId)
    {
        $post = $this->postFactory->create()->load($postId);
        if (!$post->getIsActive()) {
            return false;
        }
        return $post;
    }

    /**
     * @param int $commentId
     * @return bool|\Magefan\Blog\Model\Comment
     */
    private function initParentComment(int $commentId)
    {
        $comment = $this->commentFactory->create()->load($commentId);
        if (!$comment->isActive()) {
            return false;
        }
        return $comment;
    }
}
