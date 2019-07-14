<?php

namespace Plugin\SimpleNemPay\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SimpleNemPayType extends AbstractType {
    private $app;

    public function __construct(\Eccube\Application $app) {
        $this->app = $app;
    }

    /**
     * Build payment type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'simple_nempay';
    }

}
