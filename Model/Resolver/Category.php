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
 * Class Category
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Category implements ResolverInterface
{
    /**
     * @var DataProvider\Category
     */
    private $categoryDataProvider;

    /**
     * Category constructor.
     * @param DataProvider\Category $categoryDataProvider
     */
    public function __construct(
        DataProvider\Category $categoryDataProvider
    ) {
        $this->categoryDataProvider = $categoryDataProvider;
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
        $categoryId = $this->getCategoryId($args);
        $fields = $info ? $info->getFieldSelection(10) : null;

        try {
            $categoryData = $this->categoryDataProvider->getData($categoryId, $fields);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $categoryData;
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getCategoryId(array $args): string
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"Category id should be specified'));
        }

        return (string)$args['id'];
    }
}
