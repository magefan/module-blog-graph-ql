<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Author
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Author
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

    /**
     * @var Magento\Framework\App\State
     */
    protected $state;

    /**
     * Author constructor.
     * @param AuthorRepositoryInterface $authorRepository
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        AuthorRepositoryInterface $authorRepository,
        FilterEmulate $widgetFilter,
        State $state
    ) {
        $this->authorRepository = $authorRepository;
        $this->widgetFilter     = $widgetFilter;
        $this->state            = $state;
    }

    /**
     * @param string $authorId
     * @return array
     */
    public function getData(string $authorId): array
    {
        $author = $this->authorRepository->getFactory()->create();
        $author->getResource()->load($author, $authorId);

        $data = [];
        $this->state->emulateAreaCode(
            'frontend',
            function () use ($author, &$data) {
                $data = $author->getDynamicData();

                return $data;
            }
        );

        return $data;
    }
}
