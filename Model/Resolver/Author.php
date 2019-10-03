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
 * Class Author
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
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
        array $value = null,
        array $args = null
    ) {
        $authorId = $this->getAuthorId($args);
        $authorData = $this->getAuthorData($authorId);
        return  $authorData;
    }

    /**
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

    /**
     * @param string $authorId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getAuthorData(string $authorId): array
    {
        try {
            $authorData = $this->authorDataProvider->getData($authorId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $authorData;
    }
}
