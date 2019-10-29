<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magefan\BlogGraphQl\Model\UrlResolver\Router;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Entity Url Plugin
 */
class EntityUrl
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * EntityUrl constructor.
     * @param Router $router
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Router $router,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->router = $router;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    protected function isBlogPlusPermalinkEnabled($storeId = null)
    {
        return $this->getConfigValue(
                'mfblog/advanced_permalink/enabled',
                $storeId
            ) && $this->getConfigValue(
                'mfblog/general/enabled',
                $storeId
            );
    }

    /**
     * @param \Magento\UrlRewriteGraphQl\Model\Resolver\EntityUrl $subject
     * @param $result
     * @param $field
     * @param $context
     * @param $info
     * @param null $value
     * @param null $args
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterResolve(
        \Magento\UrlRewriteGraphQl\Model\Resolver\EntityUrl $subject,
        $result,
        $field,
        $context,
        $info,
        $value = null,
        $args = null
    ) {
        $url = $args['url'];
        if (substr($url, 0, 1) === '/' && $url !== '/') {
            $url = ltrim($url, '/');
        }

        $this->isBlogPlusPermalinkEnabled()
            ? $blogPage = $this->router->getBlogPlusPage($url)
            : $blogPage = $this->router->getBlogPage($url);

        switch ($blogPage[1]) {
            case 1:
                $type = 'POST';
                break;
            case 2:
                $type = 'CATEGORY';
                break;
            case 3:
                $type = 'TAG';
                break;
            case 4:
                $type = 'AUTHOR';
                break;
            case 5:
                $type = 'ARCHIVE';
                break;
            case 6:
                $type = 'SEARCH';
                break;
            default:
                $type = null;
        }

        $result = ['id' => $blogPage[0], 'type' => $type];

        return $result;
    }
}
