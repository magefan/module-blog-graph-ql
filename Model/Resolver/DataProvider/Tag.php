<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\TagRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;

/**
 * Class Tag
 * @package Magefan\BlogGraphQl\Model\Resolver\DataProvider
 */
class Tag
{
    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

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
     * Tag constructor.
     * @param TagRepositoryInterface $tagRepository
     * @param State $state
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        TagRepositoryInterface $tagRepository,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->tagRepository = $tagRepository;
        $this->state = $state;
        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->scopeConfig = $scopeConfig;
        $this->scopeResolver = $scopeResolver;
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

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($tag, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);

                $data = $this->getDynamicData($tag);

                return $data;
            }
        );

        return $data;
    }

    /**
     * Prepare all additional data
     * @param $tag
     * @param null $fields
     * @return mixed
     */
    public function getDynamicData($tag, $fields = null)
    {
        $data = $tag->getData();

        $keys = [
            'meta_description',
            'meta_title',
            'tag_url',
        ];

        foreach ($keys as $key) {
            $method = 'get' . str_replace(
                    '_',
                    '',
                    ucwords($key, '_')
                );
            $data[$key] = $tag->$method();
            if ($key === 'tag_url') {
                $data[$key] = str_replace(
                    '/' . $this->scopeResolver->getScope()->getCode() . '/',
                    '/',
                    $data[$key]
                );
            }
        }

        return $data;
    }
}
