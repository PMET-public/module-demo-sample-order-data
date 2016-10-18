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
    protected $addOrderData;



    public function __construct(

        \MagentoEse\SalesSampleData\Model\Order $sampleOrder,
        \MagentoEse\DemoSampleOrderData\Model\Customers $customerSetup,
        \MagentoEse\DemoSampleOrderData\Model\AddOrderData $addOrderData

    ) {
        $this->sampleOrder = $sampleOrder;
        $this->customerSetup = $customerSetup;
        $this->addOrderData = $addOrderData;

    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {

        $this->customerSetup->install(['MagentoEse_DemoSampleOrderData::fixtures/customers.csv']);
        $this->addOrderData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order.csv','MagentoEse_DemoSampleOrderData::fixtures/sales_order_grid.csv'],true,-25);
        $this->addOrderData->install(['MagentoEse_DemoSampleOrderData::fixtures/sales_order_item.csv'],false,-25);
        $this->sampleOrder->install(['MagentoEse_DemoSampleOrderData::fixtures/orders.csv'],true);



    }
}