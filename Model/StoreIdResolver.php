<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogGraphQl\Model;

use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class StoreIdResolver
{
    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(ScopeResolverInterface $scopeResolver)
    {
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * Get requested store id, current store is used if the storeId argument is empty
     *
     * @param array|null $args
     * @return int
     * @throws GraphQlNoSuchEntityException
     */
    public function getStoreId(?array $args): int
    {
        $storeId = (int)($args['storeId'] ?? 0);

        try {
            $store = $this->scopeResolver->getScope($storeId ?: null);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('Requested store is not found'), $e);
        }

        if ($storeId && !$store->getIsActive()) {
            throw new GraphQlNoSuchEntityException(__('Requested store is not found'));
        }

        return (int)$store->getId();
    }
}
