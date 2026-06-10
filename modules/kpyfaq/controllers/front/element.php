<?php

use PrestaShop\Module\KpyFaq\Exception\KpyFaqException;
use PrestaShop\Module\KpyFaq\Repository\KpyFaqElementRepository;
use PrestaShop\Module\KpyFaq\Repository\KpyFaqSectionRepository;

class KpyFaqElementModuleFrontController extends ModuleFrontController
{
    private KpyFaqSectionRepository $kpyFaqSectionRepository;

    private KpyFaqElementRepository $kpyFaqElementRepository;

    public function init(): void
    {
        parent::init();

        $this->kpyFaqSectionRepository = new KpyFaqSectionRepository();
        $this->kpyFaqElementRepository = new KpyFaqElementRepository();
    }

    public function initContent(): void
    {
        if (!Tools::getIsset('name')) {
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'display'));
        }

        try {
            $element = $this->kpyFaqElementRepository->findByLinkRewrite(Tools::getValue('name'), $this->context->language->id);

            $section = $this->kpyFaqSectionRepository->findById($element->getSectionId(), $this->context->language->id, true);

            $this->context->smarty->assign([
                'element' => $element->toArray(),
                'section' => $section->toArray(),
                'current_element' => $element->getId(),
                'fc_url' => $this->context->link->getBaseLink() . $this->trans('support', [], 'Modules.Kpyfaq.Links'),
            ]);

            $this->setTemplate('module:' . $this->module->name . '/views/templates/front/element.tpl');

            parent::initContent();

        } catch (KpyFaqException $e) {
            $this->errors[] = $e->getMessage();

            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'display'));
        }
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            '/modules/' . $this->module->name . '/views/css/kpyfaq.css',
            ['media' => 'all', 'priority' => 1000]
        );
    }

    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->getTranslator()->trans('Support center', [], 'Modules.Kpyfaq.Shop'),
            'url' => $this->context->link->getModuleLink($this->module->name, 'display'),
        ];

        $element = $this->kpyFaqElementRepository->findByLinkRewrite(Tools::getValue('name'), $this->context->language->id);

        $section = $this->kpyFaqSectionRepository->findById($element->getSectionId(), $this->context->language->id);
        $breadcrumb['links'][] = [
            'title' => $section->getTitle(),
            'url' => '#',
        ];

        $breadcrumb['links'][] = [
            'title' => $element->getQuestion(),
            'url' => '#',
        ];

        return $breadcrumb;
    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->getTranslator()->trans('Support center', [], 'Modules.Kpyfaq.Shop') .
            ' - ' . Configuration::get('PS_SHOP_NAME');

        return $page;
    }
}