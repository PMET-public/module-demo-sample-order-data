<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DemoSampleOrderData\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;

/**
 * Class Product
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RawTableData
{

    protected $fixtureManager;
    protected $csvReader;
    protected $objectManager;
    protected $resourceConnection;


    public function __construct(
        SampleDataContext $sampleDataContext,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->resourceConnection = $resourceConnection;

    }

    public function install(array $dataFixtures, $updateIds)
    {

        foreach ($dataFixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);

            if (!file_exists($fileName)) {
                continue;
            }
            echo date('H:i:s', time()) ."\n";
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
            }
            $this->moveData($stageTableName,$tableName);
            $this->dropTable($stageTableName);
            unset($dataArray);

        }

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

    /*public function setFixtures(array $fixtures)
    {
        $this->fixtures = $fixtures;
        return $this;
    }*/

}
