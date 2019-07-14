<?php

namespace Plugin\SimpleNemPay\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\SimpleNemPay\Form\Type\Admin\ConfigType;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     * 
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/simple_nem_pay/config", name="simple_nem_pay_admin_config")
     * @Template("@SimpleNemPay/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            $this->addSuccess('simple_nem_pay.admin.save.success', 'admin');
            return $this->redirectToRoute('simple_nem_pay_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
