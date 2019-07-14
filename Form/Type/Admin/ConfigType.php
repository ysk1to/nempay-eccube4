<?php

namespace Plugin\SimpleNemPay\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    private $app;
    private $settingData;

    public function __construct(\Eccube\Application $app, $settingData = array())
    {
        $this->app = $app;
        $this->settingData = $settingData;
    }

    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($this->settingData['prod_mode'])) {
            $this->settingData['prod_mode'] = 0;
        }
        if (!isset($this->settingData['seller_addr'])) {
            $this->settingData['seller_addr'] = null;
        }
        
        $builder
            ->add('prod_mode', 'choice', array(
                'label' => '環境切り替え',
                'choices' => array(
                    0 => 'テスト環境',
                    1 => '本番環境',
                ),
                'data' => $this->settingData['prod_mode'],
                'multiple' => false,
                'expanded' => true,
            ))

            ->add('seller_addr', 'text', array(
                'label' => '出品者アカウント',
                'required' => false,
                'attr' => array(
                    'class' => 'lockon_card_row',
                ),
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ 出品者アカウントが入力されていません。')),
                    new Assert\Length(array('max' => $this->app['config']['stext_len'])),
                ),
                'data' => $this->settingData['seller_addr'],
            ))

            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config';
    }
}
