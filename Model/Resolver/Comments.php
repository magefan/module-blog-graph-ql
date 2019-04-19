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
use Magefan\Blog\Api\CommentRepositoryInterface;

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
     * Comments constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CommentRepositoryInterface $commentRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CommentRepositoryInterface $commentRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->commentRepository = $commentRepository;
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
        $searchCriteria = $this->searchCriteriaBuilder->build('magefan_blog_comments', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);
        $searchResult = $this->commentRepository->getList($searchCriteria);
        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $searchResult->getItems()
        ];
    }
}
