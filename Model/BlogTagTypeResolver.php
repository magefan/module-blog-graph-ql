<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare (strict_types = 1);

namespace Magefan\BlogGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class BlogTagTypeResolver implements TypeResolverInterface
{
    const MF_BLOG_TAG = 'MF_BLOG_TAG';
    const TYPE_RESOLVER = 'BlogTag';

    /**
     * @inheritdoc
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id']) && $data['type_id'] == self::MF_BLOG_TAG) {
            return self::TYPE_RESOLVER;
        }
        return '';
    }
}
