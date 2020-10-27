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
use Magefan\Blog\Api\CommentRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Class Comments
 * @package Magefan\BlogGraphQl\Model\Resolver
 */
class Comments implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * @var DataProvider\Comment
     */
    private $commentDataProvider;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Comments constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CommentRepositoryInterface $commentRepository
     * @param DataProvider\Comment $commentDataProvider
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CommentRepositoryInterface $commentRepository,
        DataProvider\Comment $commentDataProvider,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->commentRepository = $commentRepository;
        $this->commentDataProvider = $commentDataProvider;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
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
        $parentIdFilter = $this->filterBuilder
            ->setField('parent_id')
            ->setValue(0)
            ->setConditionType('eq')
            ->create();
        $statusFilter = $this->filterBuilder
            ->setField('status')
            ->setValue(1)
            ->setConditionType('eq')
            ->create();
        $postIdFilter = $this->filterBuilder
            ->setField('post_id')
            ->setValue($args['filter']['post_id']['eq'])
            ->setConditionType('eq')
            ->create();

        $sortByCreationTime = $this->sortOrderBuilder
            ->setField('creation_time')
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->build('magefan_blog_comments', $args);
        $searchCriteria->setSortOrders([$sortByCreationTime]);

        $filterGroups = $searchCriteria->getFilterGroups();
        $filterGroups[] = $this->filterGroupBuilder->addFilter($statusFilter)->create();
        $filterGroups[] = $this->filterGroupBuilder->addFilter($postIdFilter)->create();
        $searchCriteria->setFilterGroups($filterGroups);

        $totalCount = $this->commentRepository->getList($searchCriteria)->getTotalCount();

        $filterGroups[] = $this->filterGroupBuilder->addFilter($parentIdFilter)->create();
        $searchCriteria->setFilterGroups($filterGroups);

        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchResult = $this->commentRepository->getList($searchCriteria);

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
            $items[$k] = $this->commentDataProvider->getData(
                $data['comment_id'],
                isset($fields['items']) ? $fields['items'] : null
            );
        }

        return [
            'total_count' => $totalCount,
            'total_pages' => $maxPages,
            'items' => $items
        ];
    }
}
