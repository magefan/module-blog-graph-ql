<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class BlogCategoryTypeResolver implements TypeResolverInterface
{
    const MF_BLOG_CATEGORY = 'MF_BLOG_CATEGORY';
    const TYPE_RESOLVER = 'BlogCategory';

    /**
     * @inheritdoc
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id']) && $data['type_id'] == self::MF_BLOG_CATEGORY) {
            return self::TYPE_RESOLVER;
        }
        return '';
    }
}
