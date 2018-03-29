<?php

namespace MagentoEse\DemoSampleOrderData\Model;


use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Rma\Model\Rma\Status\HistoryFactory;
use Magento\Rma\Model\RmaFactory as RmaFactory;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Model\Rma\RmaDataMapper;
use Magento\Sales\Model\OrderRepository as OrderRepository;
use Magento\Setup\Exception;
use Magento\Eav\Model\Entity\AttributeFactory as EntityAttribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as AttributeCollection;

class Rma
{
    /** @var  RmaInterface */
    private $rmaInterface;

    /** @var  RmaDataMapper */
    private $rmaDataMapper;

    /** @var OrderRepository  */
    private $orderRepository;

    /** @var HistoryFactory  */
    private $rmaComment;

    /** @var  EntityAttribute */
    private $entityAttribute;

    /** @var  AttributeCollection */
    private $attributeCollection;

    /** @var CreditMemos  */
    private $creditMemos;

    public function __construct(
        SampleDataContext $sampleDataContext,
        RmaFactory $rmaInterface,
        RmaDataMapper $rmaDataMapper,
        OrderRepository $orderRepository,
        HistoryFactory $rmaComment,
        EntityAttribute $entityAttribute,
        AttributeCollection $attributeCollection,
        CreditMemos $creditMemos
    )
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->rmaInterface = $rmaInterface;
        $this->rmaDataMapper = $rmaDataMapper;
        $this->orderRepository = $orderRepository;
        $this->rmaComment = $rmaComment;
        $this->entityAttribute = $entityAttribute;
        $this->attributeCollection = $attributeCollection;
        $this->creditMemos = $creditMemos;
    }

    public function addRMA(array $dataFixtures){

        foreach ($dataFixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);
            $entityType = 'rma_item';
            $rmaArray = [];
            foreach ($rows as $row) {

                foreach ($row as $key => $value) {
                    $rmaArray[$header[$key]] = $value;
                }
                $order = $this->orderRepository->get($rmaArray['orderid']);
                $orderItems = $order->getItems();
                $rmaData = ['comment' => ['comment' => $rmaArray['comment'], "is_visible_on_front" => 1], 'rma_confirmation' => 1];
                $itemData = [];
                foreach ($orderItems as $item) {
                    $itemData = ['qty_requested' => $item->getQtyShipped(),
                        'reason' => $this->getOptionCode($entityType, 'reason', $rmaArray['reason']),
                        'condition' => $this->getOptionCode($entityType, 'condition', $rmaArray['condition']),
                        'resolution' => $this->getOptionCode($entityType, 'resolution', $rmaArray['resolution']),
                        'order_item_id' => $item->getItemId(), 'qty_authorized' => $item->getQtyShipped(),
                        'qty_approved' => 1, 'status' => 'received'];
                }
                $rmaData['items'] = [$itemData];
                $postData = ['comment'=>['comment'=>'comment',"is_visible_on_front"=>1],'rma_confirmation'=>1,
                    'items'=>[['qty_requested'=>1,'reason'=>239,'condition'=>236,'resolution'=>234,'order_item_id'=>4009,'qty_authorized'=>1,'qty_approved'=>1,'status'=>'received']]];

                /** @var \Magento\Rma\Model\Rma $rma */
                $rma = $this->rmaInterface->create();
                $saveRequest = $this->rmaDataMapper->filterRmaSaveRequest($rmaData);
                $rma->setData($this->rmaDataMapper->prepareNewRmaInstanceData($saveRequest,$order));
                $rma->saveRma($saveRequest);
                $visible = isset($saveRequest['comment']['is_visible_on_front']);
                /** @var $customComment \Magento\Rma\Model\Rma\Status\History */
                $customComment = $this->rmaComment->create();
                $customComment->setRmaEntityId($rma->getEntityId());
                $customComment->saveComment($saveRequest['comment']['comment'], $visible, true);
                $rma->close()->save();
                $this->creditMemos->createRefund($rmaArray['orderid']);
            }
        }

    }

    /**
     * @param $entityType
     * @param $attributeCode
     * @return int
     */
    public function getOptionCode($entityType, $attributeCode, $attributeValue)
    {
        /** @var Attribute $attribute */
        $attribute = $this->entityAttribute->create();

        $attributeInfo = $attribute->loadByCode($entityType, $attributeCode);

       /**
         * Get all options name and value of the attribute
         */
        $attributeId = $attributeInfo->getAttributeId();
        $collection = $this->attributeCollection->create();
        $attributeOptionAll = $collection
            ->setPositionOrder('asc')
            ->setAttributeFilter($attributeId)
            ->setStoreFilter()
            ->load();
        foreach($attributeOptionAll as $option){
            if($option['default_value']==$attributeValue){
                return $option['option_id'];
            }
        }
    }

}