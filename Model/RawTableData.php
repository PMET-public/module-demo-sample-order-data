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

    public function install(array $dataFixtures)
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
            $columnList=implode(",",$header);
            $sql = "INSERT INTO ".$tableName." (".$columnList.") values (";
            foreach ($dataArray as $insert){
                foreach($header as $column){
                    $sql = $sql."'".$insert[$column]."',";
                }
                $sql=rtrim($sql, ",")."),(";
            }
            $sql=rtrim($sql, ",(");
            $connection = $this->resourceConnection->getConnection();
            $connection->query($sql);

            echo date('H:i:s', time()) ."\n";

        }

    }

    public function setFixtures(array $fixtures)
    {
        $this->fixtures = $fixtures;
        return $this;
    }

}
