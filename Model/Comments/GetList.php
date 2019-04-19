<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Comments;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magefan\Blog\Model\ResourceModel\Comment\Collection;
use Magefan\Blog\Model\ResourceModel\Comment\CollectionFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Class GetList
 * @package Magefan\BlogGraphQl\Model\Comments
 */
class GetList
{
    /**
     * @var CollectionFactory
     */
    private $commentCollectionFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var SearchResultsInterfaceFactory
     */
    private $storeSearchResultsInterfaceFactory;

    /**
     * GetList constructor.
     * @param CollectionFactory $commentCollectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SearchResultsInterfaceFactory $storeSearchResultsInterfaceFactory
     */
    public function __construct(
        CollectionFactory $commentCollectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchResultsInterfaceFactory $storeSearchResultsInterfaceFactory
    ) {
        $this->commentCollectionFactory = $commentCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeSearchResultsInterfaceFactory = $storeSearchResultsInterfaceFactory;
    }

    /**
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return SearchResultsInterface
     */
    public function execute(SearchCriteriaInterface $searchCriteria = null)
    {
        /** @var Collection $commentCollection */
        $commentCollection = $this->commentCollectionFactory->create();
        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $commentCollection);
        }
        /** @var SearchResultsInterface $searchResult */
        $searchResult = $this->storeSearchResultsInterfaceFactory->create();
        $searchResult->setItems($commentCollection->getItems());
        $searchResult->setTotalCount($commentCollection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
