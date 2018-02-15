<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\DemoSampleOrderData\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    public $_resourceConfig;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    private $encrypted;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;

    }

    public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context )
    {
        if (version_compare($context->getVersion(), '0.0.2', '<=')) {
            //add base currency code to existing orders for advanced Reporting
            $connection = $this->resourceConnection->getConnection();
            $orderTableName = $connection->getTableName('sales_order');
            $sql = "update " . $orderTableName . " set base_currency_code =  'USD' where base_currency_code is NULL";
            $connection->query($sql);
            $orderItemTableName = $connection->getTableName('sales_order_item');
            $sql = "update " . $orderTableName . " so, ".$orderItemTableName." oi set oi.created_at = so.created_at, oi.updated_at = so.updated_at where oi.order_id = so.entity_id";
            $connection->query($sql);
        }
        if (version_compare($context->getVersion(), '0.0.3', '<=')) {
            //set user create dates
            $connection = $this->resourceConnection->getConnection();
            $customerTableName = $connection->getTableName('customer_entity');
            $sql = "select DATEDIFF(now(), max(created_at)) * 24 + EXTRACT(HOUR FROM now()) - EXTRACT(HOUR FROM max(created_at)) -1 as hours from ".$customerTableName." where entity_id > 10";
            $result = $connection->fetchAll($sql);
            $dateDiff =  $result[0]['hours']-25;
            $sql = "update ".$customerTableName." set created_at =  DATE_ADD(created_at,INTERVAL ".$dateDiff." HOUR), updated_at =  DATE_ADD(created_at,INTERVAL ".$dateDiff." HOUR) where entity_id > 10";
            $connection->query($sql);
       }
        if (version_compare($context->getVersion(), '0.0.4', '<=')) {
            //add shipping data to orders
            $connection = $this->resourceConnection->getConnection();
            $salesOrderTable = $connection->getTableName('sales_order');
            $salesGridTable = $connection->getTableName('sales_order_grid');
            $sql = "update ".$salesOrderTable." a, ".$salesGridTable." b set a.base_shipping_amount = b.shipping_and_handling, a.base_shipping_invoiced = b.shipping_and_handling where a.entity_id = b.entity_id and a.base_shipping_amount is null";
            $connection->query($sql);
        }
    }

}
