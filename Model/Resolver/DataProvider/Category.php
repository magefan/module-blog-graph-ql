<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\State;
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
        FilterEmulate $widgetFilter,
        State $state
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->widgetFilter       = $widgetFilter;
        $this->state              = $state;
    }

    /**
     * @param string $categoryId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $categoryId): array
    {
        $category = $this->categoryRepository->getFactory()->create();
        $category->getResource()->load($category, $categoryId);

        if (!$category->isActive()) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            'frontend',
            function () use ($category, &$data) {
                $data = $category->getDynamicData();

                return $data;
            }
        );

        return $data;
    }
}
