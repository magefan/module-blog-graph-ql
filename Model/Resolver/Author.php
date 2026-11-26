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

class Author implements ResolverInterface
{
    /**
     * @var DataProvider\Author
     */
    private $authorDataProvider;

    /**
     * Author constructor.
     * @param DataProvider\Author $authorDataProvider
     */
    public function __construct(DataProvider\Author $authorDataProvider)
    {
        $this->authorDataProvider = $authorDataProvider;
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
        $authorId = $this->getAuthorId($args);
        $fields = $info ? $info->getFieldSelection(10) : null;

        try {
            $authorData = $this->authorDataProvider->getData($authorId, $fields, $storeId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $authorData;
    }

    /**
     * Get author id
     *
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getAuthorId(array $args): string
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"Author id should be specified'));
        }

        return (string)$args['id'];
    }
}
