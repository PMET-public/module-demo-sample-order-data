<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DemoSampleOrderData\Model;

use Braintree\Exception;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;

/**
 * Class Product
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddOrderData
{

    protected $fixtureManager;
    protected $csvReader;
    protected $objectManager;
    protected $resourceConnection;
    protected $updateSalesData;


    public function __construct(
        SampleDataContext $sampleDataContext,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MagentoEse\SalesSampleData\Cron\UpdateSalesData $updateSalesData
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->resourceConnection = $resourceConnection;
        $this->updateSalesData = $updateSalesData;

    }

    public function install(array $dataFixtures, $updateIds,$hourShift = 0)
    {

        foreach ($dataFixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);

            if (!file_exists($fileName)) {
                continue;
            }
            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $dataArray[] = array_combine($header, $row);
            }
            $tableName = basename($fileName,".csv");
            $stageTableName = $tableName.'_copy';
            $this->copyTable($tableName,$stageTableName);
            $columnList=implode(",",$header);
            $sql = "INSERT INTO ".$stageTableName." (".$columnList.") values (";
            foreach ($dataArray as $insert){
                foreach($header as $column){
                    $sql = $sql."'".$insert[$column]."',";
                }
                $sql=rtrim($sql, ",")."),(";
            }

            $sql=rtrim($sql, ",(");
            $connection = $this->resourceConnection->getConnection();
            $connection->query($sql);
            if($updateIds){
                $this->updateCustomerIds($stageTableName);
                $this->setIncrementId($stageTableName);
            }
            $this->pushDates($stageTableName,$hourShift);
            $this->moveData($stageTableName,$tableName);
            $this->dropTable($stageTableName);

            unset($dataArray);

        }
        $this->updateSalesData->refreshStatistics();

    }

    private function copyTable($originalTableName,$copyTableName){
        $connection = $this->resourceConnection->getConnection();
        $sql='drop table if exists '.$copyTableName;
        $connection->query($sql);
        $sql='create table '.$copyTableName.' as select * from '.$originalTableName.' where 1=0';
        $connection->query($sql);

    }
    private function updateCustomerIds($tableName){
        $connection = $this->resourceConnection->getConnection();
        $sql='update '.$tableName.' t, customer_entity c set t.customer_id = c.entity_id where c.email = t.customer_email';
        $connection->query($sql);
    }

    private function moveData($moveFrom, $moveTo){
        $connection = $this->resourceConnection->getConnection();
        $sql='insert into '.$moveTo.' select * from '.$moveFrom;
        $connection->query($sql);
    }

    private function dropTable($tablename){
        $connection = $this->resourceConnection->getConnection();
        $sql='drop table if exists '.$tablename;
        $connection->query($sql);
    }

   private function setIncrementId($stageTableName){
       $connection = $this->resourceConnection->getConnection();
       $sql="insert into sequence_order_1 select max(entity_id) from ".$stageTableName." where entity_id not in (select sequence_value from sequence_order_1)";
       $connection->query($sql);
   }

   public function pushDates($stageTableName,$hourShift){
       $connection = $this->resourceConnection->getConnection();
       $sql = "select DATEDIFF(now(), max(created_at)) * 24 + EXTRACT(HOUR FROM now()) - EXTRACT(HOUR FROM max(created_at)) -1 as hours from ".$stageTableName;
       $result = $connection->fetchAll($sql);
       $dateDiff =  $result[0]['hours']+$hourShift;
       $sql = "update " . $stageTableName . " set created_at =  DATE_ADD(created_at,INTERVAL ".$dateDiff." HOUR), updated_at =  DATE_ADD(updated_at,INTERVAL ".$dateDiff." HOUR)";
       $connection->query($sql);
   }

}