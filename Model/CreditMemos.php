<?php

namespace MagentoEse\DemoSampleOrderData\Model;


use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order\CreditmemoFactory as CreditMemoFactory;
use \Magento\Sales\Api\RefundInvoiceInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;

class CreditMemos
{
    /** @var OrderRepository  */
    private $orderRepository;

    /** @var CreditMemo  */
    private $creditMemoFactory;

    /**
     * CreditMemos constructor.
     * @param OrderRepository $orderRepository
     * @param CreditMemo $creditMemo
     */
    public function __construct(OrderRepository $orderRepository, CreditMemoFactory $creditMemoFactory, RefundInvoiceInterface $refundInvoice)
    {
        $this->orderRepository = $orderRepository;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->refundInvoice = $refundInvoice;
    }

    public function createRefunds(){

        /** @var Order $order */
        $order = $this->orderRepository->get(2);
        /** @var CreditMemo $creditMemo */
        $creditMemo = $this->creditMemoFactory->createByOrder($order);
        $creditMemo->setBaseAdjustment(20);
        $creditMemo->setBaseAdjustmentPositive(20);
        $creditMemo->collectTotals();
        $creditMemo->setState(2);
        $creditMemo->save();
    }

}