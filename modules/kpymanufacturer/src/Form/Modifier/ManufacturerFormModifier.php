<?php

namespace PrestaShop\Module\KpyManufacturer\Form\Modifier;

use PrestaShopBundle\Form\FormBuilderModifier;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ManufacturerFormModifier
{
    private TranslatorInterface $translator;

    private FormBuilderModifier $formBuilderModifier;

    public function __construct(
        TranslatorInterface $translator,
        FormBuilderModifier $formBuilderModifier
    ) {
        $this->translator = $translator;
        $this->formBuilderModifier = $formBuilderModifier;
    }

    public function addLinkRewriteField(
        string $rewrite,
        FormBuilderInterface $manufacturerFormBuilder
    ): void
    {
        $this->formBuilderModifier->addBefore(
            $manufacturerFormBuilder,
            'meta_title',
            'link_rewrite',
            TextType::class,
            [
                'required' => true,
                'label' => $this->translator->trans('Link Rewrite', [], 'Modules.Kpymanufacturer.Admin'),
                'data' => $rewrite,
                'help' => $this->translator->trans('Allowed characters: letters, underscores (_) and hyphens (-). Without spaces and accented and ñ', [], 'Modules.Kpymanufacturer.Admin')
            ]
        );
    }

    public function addCategoryRelatedField(int $categoryId, FormBuilderInterface $manufacturerFormBuilder): void
    {
        $this->formBuilderModifier->addBefore(
            $manufacturerFormBuilder,
            'meta_title',
            'category_related',
            TextType::class,
            [
                'required' => false,
                'label' => $this->translator->trans('Category related', [], 'Modules.Kpymanufacturer.Admin'),
                'data' => $categoryId > 0 ? $categoryId : '',
                'help' => $this->translator->trans('Corresponding category in the "Shop by Brand" section', [], 'Modules.Kpymanufacturer.Admin')
            ]
        );
    }
}