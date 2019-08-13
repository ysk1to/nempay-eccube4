<?php

namespace Plugin\SimpleNemPay\Service\Method;

use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\SimpleNemPay\Entity\Master\NemStatus;
use Plugin\SimpleNemPay\Repository\Master\NemStatusRepository;
use Symfony\Component\Form\FormInterface;

class SimpleNemPay implements PaymentMethodInterface
{
    /**
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
            PurchaseFlow $shoppingPurchaseFlow,
            NemStatusRepository $nemStatusRepository
    ) {
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->nemStatusRepository = $nemStatusRepository;
    }

    /**
     * {@inheritdoc}
     */  
    public function verify()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        return false;
    }

    /**
     * {@inheritdoc}
     */ 
    public function checkout()
    {
        $NemStatus = $this->nemStatusRepository->find(NemStatus::PAY_WATING);
        $this->Order->setNemStatus($NemStatus);

        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}
