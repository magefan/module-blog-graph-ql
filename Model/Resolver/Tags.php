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
     * Comments constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TagRepositoryInterface $tagRepository
     * @param DataProvider\Tag $tagDataProvider
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TagRepositoryInterface $tagRepository,
        DataProvider\Tag $tagDataProvider
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->tagRepository = $tagRepository;
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
        $searchCriteria = $this->searchCriteriaBuilder->build('magefan_blog_tags', $args);
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
