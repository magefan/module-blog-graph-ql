<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Store\Model\ScopeInterface;

class Author
{
    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var array|null
     */
    private $activePostAuthorIds;

    /**
     * Author constructor.
     * @param AuthorRepositoryInterface $authorRepository
     * @param State $state
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeResolverInterface $scopeResolver
     * @param ResourceConnection|null $resourceConnection
     * @param ModuleManager|null $moduleManager
     */
    public function __construct(
        AuthorRepositoryInterface $authorRepository,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig,
        ScopeResolverInterface $scopeResolver,
        ?ResourceConnection $resourceConnection = null,
        ?ModuleManager $moduleManager = null
    ) {
        $this->authorRepository = $authorRepository;
        $this->state = $state;
        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->scopeConfig = $scopeConfig;
        $this->scopeResolver = $scopeResolver;
        $this->resourceConnection = $resourceConnection
            ?: ObjectManager::getInstance()->get(ResourceConnection::class);
        $this->moduleManager = $moduleManager
            ?: ObjectManager::getInstance()->get(ModuleManager::class);
    }

    /**
     * Get IDs of admin users that have at least one active post.
     *
     * Returns null when Magefan_BlogAuthor is enabled, as authors are separate entities there.
     *
     * @return array|null
     */
    public function getActivePostAuthorIds(): ?array
    {
        if ($this->moduleManager->isEnabled('Magefan_BlogAuthor')) {
            return null;
        }

        if (null === $this->activePostAuthorIds) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->distinct()
                ->from($this->resourceConnection->getTableName('magefan_blog_post'), ['author_id'])
                ->where('is_active = ?', 1)
                ->where('author_id > ?', 0);
            $this->activePostAuthorIds = array_map('intval', $connection->fetchCol($select));
        }

        return $this->activePostAuthorIds;
    }

    /**
     * Get author data
     *
     * @param mixed $authorId
     * @param mixed $fields
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData($authorId, $fields = null): array
    {
        if (is_object($authorId)) {
            $author = $authorId;
        } else {
            try {
                $author = $this->authorRepository->getById((int)$authorId);
            } catch (\Exception $e) {
                throw new NoSuchEntityException();
            }
        }

        if (!$author->isActive()) {
            throw new NoSuchEntityException();
        }

        $activePostAuthorIds = $this->getActivePostAuthorIds();
        if (null !== $activePostAuthorIds && !in_array((int)$author->getId(), $activePostAuthorIds, true)) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($author, $fields, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);

                $data = $this->getDynamicData($author, $fields);

                return $data;
            }
        );

        return $data;
    }

    /**
     * Prepare all additional data
     *
     * @param mixed $author
     * @param mixed $fields
     * @return mixed
     */
    public function getDynamicData($author, $fields = null)
    {
        $data = $author->getData();

        $keys = [
            'meta_description',
            'meta_title',
            'author_url',
            'name',
            'title',
            'identifier',
            'featured_image',
            'filtered_content',
            'short_filtered_content'
        ];

        $data['author_id'] = $author->getId();

        foreach ($keys as $key) {
            if (null === $fields || array_key_exists($key, $fields)) {
                $method = 'get' . str_replace(
                    '_',
                    '',
                    ucwords($key, '_')
                );
                $data[$key] = $author->$method();
                if ($key === 'author_url') {
                    $data[$key] = str_replace(
                        '/' . $this->scopeResolver->getScope()->getCode() . '/',
                        '/',
                        $data[$key]
                    );
                }
            }
        }

        return $data;
    }
}
