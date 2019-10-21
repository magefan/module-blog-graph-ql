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
 * Class Tag
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
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
        array $value = null,
        array $args = null
    ) {
        $tagId = $this->getTagId($args);
        $tagData = $this->getTagData($tagId);
        return  $tagData;
    }

    /**
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

    /**
     * @param string $tagId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getTagData(string $tagId): array
    {
        try {
            $tagData = $this->tagDataProvider->getData($tagId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $tagData;
    }
}
