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

        return $this->getDynamicData($category, $fields);
    }

    /**
     * Prepare all additional data
     * @param $category
     * @param null $fields
     * @return mixed
     */
    public function getDynamicData($category, $fields = null)
    {
        $data = $category->getData();

        $keys = [
            'meta_description',
            'meta_title',
            'category_url',
        ];

        foreach ($keys as $key) {
            $method = 'get' . str_replace(
                    '_',
                    '',
                    ucwords($key, '_')
                );
            $data[$key] = $category->$method();
        }

        if (is_array($fields) && array_key_exists('breadcrumbs', $fields)) {
            $breadcrumbs = [];

            $categoryData = $category;
            $parentCategories = [];
            while ($parentCategory = $categoryData->getParentCategory()) {
                $parentCategories[] = $categoryData = $parentCategory;
            }

            for ($i = count($parentCategories) - 1; $i >= 0; $i--) {
                $categoryData = $parentCategories[$i];

                $breadcrumbs[] = [
                    'category_id' => $categoryData->getId(),
                    'category_name' => $categoryData->getTitle(),
                    'category_level' => $categoryData->getLevel(),
                    'category_url_key' => $categoryData->getIdentifier(),
                    'category_url_path' => $categoryData->getUrl(),
                ];
            }

            $categoryData = $category;
            $breadcrumbs[] = [
                'category_id' => $categoryData->getId(),
                'category_name' => $categoryData->getTitle(),
                'category_level' => $categoryData->getLevel(),
                'category_url_key' => $categoryData->getIdentifier(),
                'category_url_path' => $categoryData->getUrl(),
            ];

            $data['breadcrumbs'] = $breadcrumbs;
        }

        if (is_array($fields) && array_key_exists('parent_category_id', $fields)) {
            $data['parent_category_id'] = $category->getParentCategory() ? $category->getParentCategory()->getId() : 0;
        }

        if (is_array($fields) && array_key_exists('category_level', $fields)) {
            $data['category_level'] = $category->getLevel();
        }

        if (is_array($fields) && array_key_exists('posts_count', $fields)) {
            $data['posts_count'] = $category->getPostsCount();
        }

        if (is_array($fields) && array_key_exists('category_url_path', $fields)) {
            $data['category_url_path'] = $category->getUrl();
        }

        return $data;
    }
}
