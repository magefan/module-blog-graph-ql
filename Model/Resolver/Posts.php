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
use Magefan\Blog\Api\PostRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ScopeResolverInterface;

/**
 * Class Posts
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Posts implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var DataProvider\Post
     */
    protected $postDataProvider;
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
     * Posts constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PostRepositoryInterface $postRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param DataProvider\Post $postDataProvider
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PostRepositoryInterface $postRepository,
        SortOrderBuilder $sortOrderBuilder,
        DataProvider\Post $postDataProvider,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->postRepository = $postRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->postDataProvider = $postDataProvider;
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
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $searchCriteria = $this->searchCriteriaBuilder->build('di_build_magefan_blog_post', $args);
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

        if (isset($args['filter']['post_id']['in'])) {
            $postIdFilter = $this->filterBuilder
                ->setField('post_id')
                ->setValue($args['filter']['post_id']['in'])
                ->setConditionType('in')
                ->create();
            $filterGroups[] = $this->filterGroupBuilder->addFilter($postIdFilter)->create();
        }

        $searchCriteria->setFilterGroups($filterGroups);

        array_key_exists('allPosts', $args) && $args['allPosts'] ?:
            $searchCriteria
                ->setPageSize($args['pageSize'])
                ->setCurrentPage($args['currentPage']);

        if (isset($args['sort'])) {
            $sortOrder = $this->sortOrderBuilder
                ->setField(isset($args['sortFiled']) ? $args['sortFiled'] : 'update_time')
                ->setDirection($args['sort'][0])
                ->create();
            $searchCriteria->setSortOrders([$sortOrder]);
        }

        $searchResult = $this->postRepository->getList($searchCriteria);

        //possible division by 0
        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $searchCriteria->getCurrentPage();
        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        $items = $searchResult->getItems();
        $fields = $info ? $info->getFieldSelection(10) : null;

        foreach ($items as $k => $data) {
            $items[$k] = $this->postDataProvider->getData(
                $data['post_id'],
                isset($fields['items']) ? $fields['items'] : null,
                $storeId
            );
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'total_pages' => $maxPages,
            'items' => $items
        ];
    }
}
