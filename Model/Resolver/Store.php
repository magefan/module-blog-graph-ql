<?php
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\Resolver;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;


class Store implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $stores = [
            [
                'name' => 'Brick and Mortar Kolbermoor',
                'street' => 'JosefMeier Straße',
                'street_num' => '1002',
                'postcode' => '83059',
            ],
            [
                'name' => 'Brick and Mortar  Erfurt',
                'street' => 'Max Meier Straße',
                'street_num' => '102',
                'postcode' => '99338',
            ],
        ];
        return [
            'total_count' => count($stores),
            'items' => $stores
        ];
    }
}
