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
 * Class Post
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Post implements ResolverInterface
{
    /**
     * @var DataProvider\Post
     */
    private $postDataProvider;

    /**
     * Post constructor.
     * @param DataProvider\Post $postDataProvider
     */
    public function __construct(DataProvider\Post $postDataProvider)
    {
        $this->postDataProvider = $postDataProvider;
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
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $postId = $this->getPostId($args);
        $fields = $info ? $info->getFieldSelection(10) : null;

        try {
            $postData = $this->postDataProvider->getData($postId, $fields, $storeId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return  $postData;
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getPostId(array $args): string
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"Post id should be specified'));
        }

        return (string)$args['id'];
    }
}
