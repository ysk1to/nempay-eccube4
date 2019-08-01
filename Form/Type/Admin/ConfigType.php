<?php

namespace Plugin\SimpleNemPay\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\SimpleNemPay\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ConfigType constructor.
     * 
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
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
        $builder
            ->add('env', ChoiceType::class, [
                'choices' => [
                    'テスト環境' => $this->eccubeConfig['simple_nem_pay']['env']['sandbox'],
                    '本番環境' => $this->eccubeConfig['simple_nem_pay']['env']['prod'],
                ],
                'multiple' => false,
                'expanded' => true,
            ])

            ->add('seller_nem_addr', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 出品者NEMアドレスが入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
