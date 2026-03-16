<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ScopeResolverInterface;

class Authors implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var DataProvider\Author
     */
    protected $authorDataProvider;
    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * Authors constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AuthorRepositoryInterface $authorRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param DataProvider\Author $authorDataProvider
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AuthorRepositoryInterface $authorRepository,
        SortOrderBuilder $sortOrderBuilder,
        DataProvider\Author $authorDataProvider,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->authorRepository = $authorRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->authorDataProvider = $authorDataProvider;
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
        ?array $value = null,
        ?array $args = null
    ) {
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $searchCriteria = $this->searchCriteriaBuilder->build('di_build_magefan_blog_authot', $args);
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
            ->setValue($args['storeId'] ?? $scope)
            ->setConditionType('eq')
            ->create();
        $filterGroups[] = $this->filterGroupBuilder->addFilter($scopeFilter)->create();

        if (isset($args['filter']['author_id']['in'])) {
            $authorIdFilter = $this->filterBuilder
                ->setField('author_id')
                ->setValue($args['filter']['author_id']['in'])
                ->setConditionType('in')
                ->create();
            $filterGroups[] = $this->filterGroupBuilder->addFilter($authorIdFilter)->create();
        }

        $searchCriteria->setFilterGroups($filterGroups);


        if (isset($args['sort'])) {
            $sortOrder = $this->sortOrderBuilder
                ->setField(isset($args['sortFiled']) ? $args['sortFiled'] : 'update_time')
                ->setDirection($args['sort'][0])
                ->create();
            $searchCriteria->setSortOrders([$sortOrder]);
        }

        $searchResult = $this->authorRepository->getList($searchCriteria);

        $items = $searchResult->getItems();
        $fields = $info ? $info->getFieldSelection(10) : null;

        foreach ($items as $k => $data) {
            $items[$k] = $this->authorDataProvider->getData(
                $data,
                isset($fields['items']) ? $fields['items'] : null,
                $storeId
            );
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $items
        ];
    }
}
