<?php

namespace Plugin\SimpleNemPay\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Form\Type\Master\OrderStatusType;
use Plugin\SimpleNemPay\Entity\Master\NemStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('OrderStatuses', OrderStatusType::class, [
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('NemStatuses', EntityType::class, [
                'class' => NemStatus::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->orderBy('p.id', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ]);
    }
}
