<?php

use PrestaShop\Module\KpyFaq\Entity\KpyFaqSection;
use PrestaShop\Module\KpyFaq\Repository\KpyFaqElementRepository;
use PrestaShop\Module\KpyFaq\Repository\KpyFaqSectionRepository;

class KpyFaqDisplayModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        $sectionRepository = new KpyFaqSectionRepository();
        $sections = $sectionRepository->findAll($this->context->language->id, true);

        $this->context->smarty->assign([
            'frequently' => $this->getFrequentlyQuestion([1, 3, 6, 8, 9, 12]),
            'sections' => array_map(static fn(KpyFaqSection $section) => $section->toArray(), $sections),
            'fc_url' => $this->context->link->getBaseLink() . $this->translator->trans('support', [], 'Modules.Kpyfaq.Links'),
        ]);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/display.tpl');

        parent::initContent();
    }

    private function getFrequentlyQuestion(array $elements): array
    {
        $elementRepository = new KpyFaqElementRepository();

        return array_map(function (int $idElement) use ($elementRepository) {
            return $elementRepository->findById($idElement, $this->context->language->id)->toArray();
        }, $elements);
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

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->getTranslator()->trans('Support center', [], 'Modules.Kpyfaq.Shop') .
            ' - ' . Configuration::get('PS_SHOP_NAME');

        return $page;
    }
}