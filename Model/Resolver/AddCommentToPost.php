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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * AddCommentToPost constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CommentRepositoryInterface $commentRepository
     * @param PostRepositoryInterface $postRepository
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CommentRepositoryInterface $commentRepository,
        PostRepositoryInterface $postRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
        $this->customerRepository = $customerRepository;
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

        if (empty($args['input']['post_id'])) {
            throw new GraphQlInputException(__('Post ID should be specified'));
        }
        if (empty($args['input']['text'])) {
            throw new GraphQlInputException(__('Comment should be specified'));
        }

        $postId = (int)$args['input']['post_id'];
        $text = $args['input']['text'];
        $parentId = empty($args['input']['parent_id']) ?  0 : $args['input']['parent_id'];

        $comment = $this->commentRepository->getFactory()->create();
        $comment->setData([
            'post_id' => $postId,
            'text' => $text
        ]);

        if ($context->getExtensionAttributes()->getIsCustomer()) {
            try {
                $customer = $this->customerRepository->getById($context->getUserId());
            } catch (NoSuchEntityException $e) {
                throw new GraphQlNoSuchEntityException(__('Customer is no longer exist.'));
            }

            $comment->setCustomerId($customer->getId())
                ->setAuthorNickname($customer->getFirstname(). ' ' .$customer->getLastname())
                ->setAuthorEmail($customer->getEmail())
                ->setAuthorType(AuthorType::CUSTOMER);
        } elseif ($this->scopeConfig->getValue(Config::GUEST_COMMENT, ScopeInterface::SCOPE_STORE)) {
            if (empty($args['input']['author_nickname'])) {
                throw new GraphQlInputException(__('Author should be specified'));
            }
            if (empty($args['input']['author_email'])) {
                throw new GraphQlInputException(__('Author email should be specified'));
            }

            $author = $args['input']['author_nickname'];
            $email = $args['input']['author_email'];

            /* Guest can post comment */
            $comment->setCustomerId(0)->setAuthorType(AuthorType::GUEST);
            $comment->addData([
                'author_nickname' => $author,
                'author_email' => $email
            ]);
        } else {
            throw new GraphQlAuthorizationException(__('Login to submit comment.'));
        }

        /* Set default status */
        $comment->setStatus(
            $this->scopeConfig->getValue(Config::COMMENT_STATUS, ScopeInterface::SCOPE_STORE)
        );

        try {
            try {
                $post = $this->postRepository->getById($postId);
            } catch (NoSuchEntityException $e) {
                throw new GraphQlNoSuchEntityException(__('Blog post is not longer exist.'));
            }

            if (!$post->getIsActive()) {
                throw new GraphQlNoSuchEntityException(__('You cannot post comment. Blog post is not longer exist.'));
            }

            if ($parentId) {
                try {
                    $parentComment = $this->commentRepository->getById($parentId);
                } catch (NoSuchEntityException $e) {
                    throw new GraphQlNoSuchEntityException(__('Comment is not longer exist.'));
                }

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
            }

            $this->commentRepository->save($comment);
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
            'total_pages' => $maxPages,
            'comments' => $comments
        ];
    }
}
