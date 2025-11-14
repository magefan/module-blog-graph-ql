<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
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
     * Returns a custom blog page data structure if the original result is empty.
     *
     * @param mixed $subject
     * @param mixed|Value $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterResolve(
        $subject,
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

        $type = $blogPage['type'];
        $type = str_replace('blog_', 'mf_blog_', $type);
        if (stripos($type, 'mf_blog_') === false) {
            $type = 'mf_blog_' . $type;
        }
        $type = strtoupper($type);

        $result = [
            'id' => $blogPage['id'],
            'type' => $type,
            'type_id' => $type,
            'relative_url' => $path,
            'redirect_code' => 0
        ];

        return $result;
    }
}
