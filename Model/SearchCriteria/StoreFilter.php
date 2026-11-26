<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Pass store id to the blog collection as a scalar.
 *
 * Default filter processor passes ['eq' => $storeId], in this case the collection does not remember
 * the store id, so the store view specific data (Blog Extra) is not loaded.
 */
class StoreFilter implements CustomFilterInterface
{
    /**
     * Apply store filter to collection
     *
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $value = $filter->getValue();

        if (($filter->getConditionType() ?: 'eq') !== 'eq' || !is_numeric($value)) {
            return false;
        }

        /* Default author collection (admin users) is not related to stores */
        if (method_exists($collection, 'addStoreFilter')) {
            $collection->addStoreFilter((int)$value);
        }

        return true;
    }
}
