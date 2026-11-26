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

class Tag implements ResolverInterface
{
    /**
     * @var DataProvider\Tag
     */
    private $tagDataProvider;

    /**
     * Tag constructor.
     * @param DataProvider\Tag $tagDataProvider
     */
    public function __construct(DataProvider\Tag $tagDataProvider)
    {
        $this->tagDataProvider = $tagDataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $tagId = $this->getTagId($args);
        $fields = $info ? $info->getFieldSelection(10) : null;

        try {
            $tagData = $this->tagDataProvider->getData($tagId, $fields, $storeId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $tagData;
    }

    /**
     * Get tag id
     *
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getTagId(array $args): string
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"Tag id should be specified'));
        }

        return (string)$args['id'];
    }
}
