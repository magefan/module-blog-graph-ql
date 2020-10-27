<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver\DataProvider;

use Magefan\Blog\Api\CommentRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Class Comment
 */
class Comment
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

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
     * Comment constructor.
     * @param CommentRepositoryInterface $commentRepository
     * @param FilterEmulate $widgetFilter
     * @param State $state
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CommentRepositoryInterface $commentRepository,
        FilterEmulate $widgetFilter,
        State $state,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->commentRepository = $commentRepository;
        $this->widgetFilter = $widgetFilter;
        $this->state = $state;
        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $commentId
     * @param null $fields
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $commentId, $fields = null): array
    {
        $comment = $this->commentRepository->getFactory()->create();
        $comment->getResource()->load($comment, $commentId);

        if (!$comment->isActive()) {
            throw new NoSuchEntityException();
        }

        $data = [];
        $this->state->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($comment, $fields, &$data) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    ScopeInterface::SCOPE_STORE
                );
                $theme = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);

                $data = $this->getDynamicData($comment, $fields);

                return $data;
            }
        );

        return $data;
    }

    /**
     * Prepare all additional data
     * @param $comment
     * @param null $fields
     * @return mixed
     */
    public function getDynamicData($comment, $fields = null)
    {
        $data = $comment->getData();

        if (is_array($fields) && array_key_exists('replies', $fields)) {
            $replies = [];
            foreach ($comment->getRepliesCollection() as $reply) {
                $replies[] = $reply->getDynamicData(
                    isset($fields['replies']) ? $fields['replies'] : null
                );
            }
            $data['replies'] = $replies;
        }

        return $data;
    }
}
