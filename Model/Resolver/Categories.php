<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magefan\Blog\Api\CategoryRepositoryInterface;

/**
 * Class Categories
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Categories implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepositoryInterface;

    /**
     * @var DataProvider\Category
     */
    protected $categoryDataProvider;

    /**
     * Categories constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param DataProvider\Category $categoryDataProvider
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CategoryRepositoryInterface $categoryRepositoryInterface,
        DataProvider\Category $categoryDataProvider
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
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
        $searchCriteria = $this->searchCriteriaBuilder->build('magefan_blog_categories', $args);
        $searchResult = $this->categoryRepositoryInterface->getList($searchCriteria);
        $items = $searchResult->getItems();
        $fields = $info ? $info->getFieldSelection(10) : null;

        foreach ($items as $k => $data) {
            $items[$k] = $this->categoryDataProvider->getData(
                $data['category_id'],
                isset($fields['items']) ? $fields['items'] : null
            );
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $items
        ];
    }
}
