<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Category
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Category
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Category constructor.
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        FilterEmulate $widgetFilter
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->widgetFilter = $widgetFilter;
    }

    /**
     * @param string $categoryId
     * @param null $fields
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $categoryId , $fields = null): array
    {
        $category = $this->categoryRepository->getFactory()->create();
        $category->getResource()->load($category, $categoryId);

        if (!$category->isActive()) {
            throw new NoSuchEntityException();
        }

        return $category->getDynamicData($fields);
    }
}
