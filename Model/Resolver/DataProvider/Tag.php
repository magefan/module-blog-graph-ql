<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\TagRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Tag
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Tag
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

    /**
     * Tag constructor.
     * @param TagRepositoryInterface $tagRepository
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        TagRepositoryInterface $tagRepository,
        FilterEmulate $widgetFilter
    ) {
        $this->tagRepository = $tagRepository;
        $this->widgetFilter = $widgetFilter;
    }

    /**
     * @param string $tagId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $tagId): array
    {
        $tag = $this->tagRepository->getFactory()->create();
        $tag->getResource()->load($tag, $tagId);

        if (!$tag->isActive()) {
            throw new NoSuchEntityException();
        }

        return $tag->getDynamicData();
    }
}
