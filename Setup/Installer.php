<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DemoSampleOrderData\Setup;

use Magento\Framework\Setup;


class Installer implements Setup\SampleData\InstallerInterface
{

    protected $sampleOrder;
    protected $customerSetup;
    protected $rawTableData;


    public function __construct(

        \MagentoEse\SalesSampleData\Model\Order $sampleOrder,
        \MagentoEse\DemoSampleOrderData\Model\Customers $customerSetup,
        \MagentoEse\DemoSampleOrderData\Model\RawTableData $rawTableData

    ) {
        $this->sampleOrder = $sampleOrder;
        $this->customerSetup = $customerSetup;
        $this->rawTableData = $rawTableData;

    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {

        $this->customerSetup->install(['MagentoEse_DemoSampleOrderData::fixtures/customers.csv']);
        //$this->rawTableData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order_copy.csv']);
        //$this->rawTableData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order_item.csv']);
        $this->rawTableData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order.csv','MagentoEse_DemoSampleOrderData::fixtures/sales_order_grid.csv'],true);
        //$this->rawTableData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order_grid.csv'],true);
        $this->rawTableData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order_item.csv'],false);
        //$this->sampleOrder->install(['MagentoEse_DemoSampleOrderData::fixtures/orders.csv']);


    }
}