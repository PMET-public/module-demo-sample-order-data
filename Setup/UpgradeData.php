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
            $connection = $this->resourceConnection->getConnection();
            $salesOrderTable = $connection->getTableName('sales_order');
            $salesGridTable = $connection->getTableName('sales_order_grid');
            $salesOrderAddressTable = $connection->getTableName('sales_order_address');
            $customerAddressEntityTable = $connection->getTableName('customer_address_entity');
            //add shipping costs to orders
            $sql = "update ".$salesOrderTable." a, ".$salesGridTable." b set a.base_shipping_amount = b.shipping_and_handling, a.base_shipping_invoiced = b.shipping_and_handling where a.entity_id = b.entity_id and a.base_shipping_amount is null";
            $connection->query($sql);
            //create appropriate sales_order_address records
            $sql = "INSERT INTO ".$salesOrderAddressTable." (parent_id, customer_address_id, region_id, customer_id, region, postcode, lastname, street, city, email, telephone, country_id, firstname, address_type) select so.entity_id,cae.entity_id,cae.region_id,cae.parent_id,cae.region, cae.postcode,cae. lastname, cae.street, cae.city, so.customer_email, cae.telephone, cae.country_id, cae.firstname, 'billing' from ".$customerAddressEntityTable." cae, ".$salesOrderTable." so where cae.parent_id = so.customer_id";
            $connection->query($sql);
            $sql = "INSERT INTO ".$salesOrderAddressTable." (parent_id, customer_address_id, region_id, customer_id, region, postcode, lastname, street, city, email, telephone, country_id, firstname, address_type) select so.entity_id,cae.entity_id,cae.region_id,cae.parent_id,cae.region, cae.postcode,cae. lastname, cae.street, cae.city, so.customer_email, cae.telephone, cae.country_id, cae.firstname, 'shipping' from ".$customerAddressEntityTable." cae, ".$salesOrderTable." so where cae.parent_id = so.customer_id";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesOrderAddressTable." soa set so.billing_address_id = soa.entity_id where soa.parent_id = so.entity_id and soa.address_type = 'billing' and so.billing_address_id is null;";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesOrderAddressTable." soa set so.billing_address_id = soa.entity_id where soa.parent_id = so.entity_id and soa.address_type = 'shipping' and so.billing_address_id is null;";
            $connection->query($sql);
            //add sales tax to orders from certain states
            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.0825,2), so.tax_amount = round(so.base_grand_total*.0825,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, CA %'";
            $connection->query($sql);

            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.0825,2), so.tax_amount = round(so.base_grand_total*.0825,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, MI %'";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.08375,2), so.tax_amount = round(so.base_grand_total*.08375,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, NY %'";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.0875,2), so.tax_amount = round(so.base_grand_total*.0875,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, IL %'";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.07,2), so.tax_amount = round(so.base_grand_total*.07,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, IN %'";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.05125,2), so.tax_amount = round(so.base_grand_total*.05125,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, NM %'";
            $connection->query($sql);
            $sql = "update ".$salesOrderTable." so, ".$salesGridTable." sog set so.base_tax_amount = round(so.base_grand_total*.047,2), so.tax_amount = round(so.base_grand_total*.047,2),so.base_shipping_tax_amount = 0, so.base_to_order_rate = 1, so.shipping_tax_amount = 0 where so.entity_id = sog.entity_id and so.entity_id > 100 and sog.shipping_address like '%, UT %'";
            $connection->query($sql);
        }
    }

}
