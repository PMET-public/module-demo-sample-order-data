<?php

namespace MagentoEse\DemoSampleOrderData\Model;

use Magento\Framework\App\State;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order\CreditmemoFactory as CreditMemoFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Api\CreditmemoItemRepositoryInterface as CreditMemoItemRepository;

class CreditMemos
{
    /** @var OrderRepository  */
    private $orderRepository;

    /** @var CreditMemo  */
    private $creditMemoFactory;

    /** @var  CreditMemoItemRepository */
    private $creditMemoItemRepository;

    /**
     * CreditMemos constructor.
     * @param OrderRepository $orderRepository
     * @param CreditMemoFactory $creditMemoFactory
     * @param CreditMemoItemRepository $creditMemoItemRepository
     * @param State $state
     */
    public function __construct(OrderRepository $orderRepository,
                                CreditMemoFactory $creditMemoFactory,
                                CreditMemoItemRepository $creditMemoItemRepository,
                                State $state
    )
    {

        $this->orderRepository = $orderRepository;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoItemRepository = $creditMemoItemRepository;
        try{
            $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }

    }

    public function createRefund($orderId){

        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);
        /** @var CreditMemo $creditMemo */
        $creditMemo = $this->creditMemoFactory->createByOrder($order);
        //$creditMemo->setBaseAdjustment(10);
        //$creditMemo->setBaseAdjustmentPositive(10);
        /** @var \Magento\Sales\Api\Data\CreditmemoItemInterface $items */
        $items = $creditMemo->getItems();
        foreach($items as $item){
            $item->setQty(1);
            break;
        }
        $creditMemo->setItems($items);
        //$creditMemo->collectTotals();
        $creditMemo->setState(2);
        $creditMemo->save();
    }

}