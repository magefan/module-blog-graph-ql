<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magefan\Blog\Api\TagRepositoryInterface;

/**
 * Class Tags
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Tags implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

    /**
     * @var DataProvider\Tag
     */
    protected $tagDataProvider;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * Comments constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TagRepositoryInterface $tagRepository
     * @param DataProvider\Tag $tagDataProvider
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TagRepositoryInterface $tagRepository,
        DataProvider\Tag $tagDataProvider,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->tagRepository = $tagRepository;
        $this->tagDataProvider = $tagDataProvider;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->scopeResolver = $scopeResolver;
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
        $searchCriteria = $this->searchCriteriaBuilder->build('magefan_blog_tags', $args);
        $statusFilter = $this->filterBuilder
            ->setField('is_active')
            ->setValue(1)
            ->setConditionType('eq')
            ->create();

        $filterGroups = $searchCriteria->getFilterGroups();
        $filterGroups[] = $this->filterGroupBuilder->addFilter($statusFilter)->create();

        $scope = $this->scopeResolver->getScope()->getId();

        $scopeFilter = $this->filterBuilder
            ->setField('store_id')
            ->setValue($scope)
            ->setConditionType('eq')
            ->create();
        $filterGroups[] = $this->filterGroupBuilder->addFilter($scopeFilter)->create();

        $searchCriteria->setFilterGroups($filterGroups);

        $searchResult = $this->tagRepository->getList($searchCriteria);
        $items = $searchResult->getItems();

        foreach ($items as $k => $data) {
            $items[$k] = $this->tagDataProvider->getData($data['tag_id']);
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $items
        ];
    }
}
