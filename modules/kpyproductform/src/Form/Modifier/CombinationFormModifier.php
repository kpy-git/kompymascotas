<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyProductForm\Form\Modifier;

use PrestaShop\Module\KpyProductForm\Entity\KpyCombination;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\ValueObject\CombinationId;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\FormBuilderModifier;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CombinationFormModifier
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FormBuilderModifier
     */
    private $formBuilderModifier;

    /**
     * @param TranslatorInterface $translator
     * @param FormBuilderModifier $formBuilderModifier
     */
    public function __construct(
        TranslatorInterface $translator,
        FormBuilderModifier $formBuilderModifier
    ) {
        $this->translator = $translator;
        $this->formBuilderModifier = $formBuilderModifier;
    }

    /**
     * @param CombinationId|null $combinationId
     * @param FormBuilderInterface $combinationFormBuilder
     */
    public function modify(
        ?CombinationId $combinationId,
        FormBuilderInterface $combinationFormBuilder
    ): void {
        $idValue = $combinationId?->getValue();
        $kpyCombination = new KpyCombination($idValue);
        $this->addCustomField($kpyCombination, $combinationFormBuilder);
    }

    /**
     * @param KpyCombination $kpyCombination
     * @param FormBuilderInterface $combinationFormBuilder
     *
     * @see demoproductform::hook
     */
    private function addCustomField(KpyCombination $kpyCombination, FormBuilderInterface $combinationFormBuilder): void
    {
        $this->formBuilderModifier->addBefore(
            $combinationFormBuilder,
            'stock',
            'kpy_active',
            SwitchType::class,
            [
                'label' => $this->translator->trans('State', [], 'Modules.Kpyproductform.Admin'),
                'label_attr' => ['class' => 'h3'],
                'inline_switch' => true,
                'choices' => [
                    $this->translator->trans('Inactive', [], 'Modules.Kpyproductform.Admin') => false,
                    $this->translator->trans('Active', [], 'Modules.Kpyproductform.Admin') => true,
                ],
                'data' => $kpyCombination->active,
                'form_theme' => '@PrestaShop/Admin/TwigTemplateForm/prestashop_ui_kit_base.html.twig',
            ]
        );
    }
}
