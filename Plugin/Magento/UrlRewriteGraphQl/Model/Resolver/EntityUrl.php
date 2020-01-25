<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magefan\Blog\Api\UrlResolverInterface;

/**
 * Class Entity Url Plugin
 */
class EntityUrl
{
    /**
     * @var UrlResolverInterface
     */
    protected $urlResolver;

    /**
     * EntityUrl constructor.
     * @param UrlResolverInterface $urlResolver
     */
    public function __construct(
        UrlResolverInterface $urlResolver
    ) {
        $this->urlResolver = $urlResolver;
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
        if (!empty($result)) {
            return $result;
        }

        $path = $args['url'];
        $blogPage = $this->urlResolver->resolve($path);

        if (!$blogPage || empty($blogPage['type']) || empty($blogPage['id'])) {
            return $result;
        }

        $result = [
            'id' => $blogPage['id'],
            'type' => strtoupper($blogPage['type'])
        ];

        return $result;
    }
}
