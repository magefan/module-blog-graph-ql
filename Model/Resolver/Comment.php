<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Class Comment
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Comment implements ResolverInterface
{
    /**
     * @var DataProvider\Comment
     */
    private $commentDataProvider;

    /**
     * Comment constructor.
     * @param DataProvider\Comment $commentDataProvider
     */
    public function __construct(DataProvider\Comment $commentDataProvider)
    {
        $this->commentDataProvider = $commentDataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $commentId = $this->getCommentId($args);
        $commentData = $this->getCommentData($commentId);
        return  $commentData;
    }

    /**
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function getCommentId(array $args): int
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"Comment id should be specified'));
        }

        return (int)$args['id'];
    }

    /**
     * @param int $commentId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getCommentData(int $commentId): array
    {
        try {
            $commentData = $this->commentDataProvider->getData($commentId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $commentData;
    }
}
