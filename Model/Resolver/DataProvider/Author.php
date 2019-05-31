<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * Author constructor.
     * @param AuthorRepositoryInterface $authorRepository
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        AuthorRepositoryInterface $authorRepository,
        FilterEmulate $widgetFilter
    ) {
        $this->authorRepository = $authorRepository;
        $this->widgetFilter = $widgetFilter;
    }

    /**
     * @param int $authorId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(int $authorId): array
    {
        $author = $this->authorRepository->getById($authorId);

        if (false === $author->getData('is_active')) {
            throw new NoSuchEntityException();
        }

        $authorData = [
            'url_key' => $author->getIdentifier(),
            'title' => $author->getTitle(),
            'name' => $author->getName(),
            'url' => $author->getUrl(),
            'author_url' => $author->getAuthorUrl(),
            'creation_time' => $author->getData('creation_time'),
            'is_active' => $author->getData('is_active'),
        ];
        return $authorData;
    }
}
