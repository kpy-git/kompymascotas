<?php

use PrestaShop\Module\KpyPets\Config\KpyPetsConfig;
use PrestaShop\Module\KpyPets\Entity\Pet;
use Prestashop\Module\KpyPets\Exception\PetException;
use PrestaShop\Module\KpyPets\Form\PetForm;
use PrestaShop\Module\KpyPets\Repository\PetRepository;

class KpyPetsManageModuleFrontController extends ModuleFrontController
{
    public $guestAllowed = false;

    public function initContent()
    {
        $pet = new Pet();

        if (Tools::getValue('id')) {
            if (!$this->validateToken(Tools::getValue('token'))) {
                $this->errors[] = 'Invalid token';
                Tools::redirect($this->context->link->getModuleLink($this->module->name, 'display'));
            }

            $repository = new PetRepository();

            $pet = $repository->getPetById((int)Tools::getValue('id'));

            if ($pet->getIdCustomer() !== $this->context->customer->id) {
                throw new PetException('### posible hack ### The pet does not belong to the customer');
            }
        }

        $petForm = new PetForm($this->module->getTranslator(), $this->context->language);

        $this->context->smarty->assign([
            'module_img' => $this->module->getPathUri() . '/views/img/',
            'csrf_token' => Tools::getToken(),
            'form_fields' => $petForm->getFormFields($pet),
            'show_voucher_info' => $this->module->getCustomerPetsCount(false) === 0 && !$this->context->customer->newsletter,
            'voucher_amount' => (float)Configuration::get(KpyPetsConfig::CART_RULE_AMONT),
            'page_title' => Tools::getIsset('id')
                ? $this->trans( 'Edit pet', [], 'Modules.Kpypets.Shop')
                : $this->trans( 'Add new pet', [], 'Modules.Kpypets.Shop')
        ]);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/pet-form.tpl');

        parent::initContent();
    }

    private function validateToken(string $token): bool
    {
        return $token === Tools::getToken();
    }

    public function postProcess(): void
    {
        if (Tools::isSubmit('pet-form')) {
            $this->processForm();
        }

        if (Tools::getIsset('delete')) {
            $repository = new PetRepository();
            $repository->disablePetById((int)Tools::getValue('id'));
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'display'));
        }

        if ($this->ajax) {
            $this->ajaxProcess();
        }
    }

    public function ajaxProcess(): void
    {
        ob_clean();
        header('Content-Type: application/json:charset=UTF-8');

        $repository = new PetRepository();

        $breeds = $repository->getAvailableBreedByKind((int)Tools::getValue('kind'), $this->context->language->id);

        // para JS si se usa el array sin modificar ordenada las claves (son enteros) y no respetará el orden

        $jsBreeds = [];
        foreach ($breeds as $id => $breed) {
            $jsBreeds[] = [
                'id' => $id,
                'name' => $breed,
            ];
        }

        $this->ajaxRender(json_encode([
            'code' => 200,
            'breeds' => $jsBreeds,
        ]));
    }

    public function processForm(): void
    {
        if (!$this->validateToken(Tools::getValue('token'))) {
            $this->errors[] = 'Invalid token';
            return;
        }

        $pet = new Pet();
        $pet
            ->setId((int)Tools::getValue('id', 0))
            ->setName(Tools::getValue('name'))
            ->setKind((int)Tools::getValue('kind'))
            ->setSex(Tools::getValue('sex'))
            ->setNeutered((int)Tools::getValue('neutered', 0) == 1)
            ->setBirthdate(Tools::getValue('birthdate'))
            ->setBreed(Tools::getValue('breed'))
            ->setHairColor(Tools::getValue('haircolor', ''))
            ->setWeight((int)Tools::getValue('weight', 0))
            ->setAcquisition((int)Tools::getValue('acquisition'))
            ->setHabitat((int)Tools::getValue('habitat'))
            ->setLongHair((int)Tools::getValue('longhair', 0) == 1)
            ->setSleepsOut((int)Tools::getValue('sleeps-out', 0) == 1)
            ->setIdCustomer($this->context->customer->id);

        $petRepository = new PetRepository();

        $diseases = $petRepository->getAvailableDiseases($this->context->language->id);

        $pet->setDiseases([]);

        foreach ($diseases as $id_desease => $name) {
            if (!empty(Tools::getValue('disease-' . $id_desease))) {
                $pet->addDisease((int)$id_desease);
            }
        }

        if ($petRepository->savePet($pet)) {
            if (!$pet->getId()) {
                if ((int)Tools::getValue('newsletter', 0)) {
                    $this->newsletterRegisterUser();
                }

                $this->module->createVoucherIfApplicable();
            }

            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'display'));
        }
    }

    private function newsletterRegisterUser(): void
    {
        Db::getInstance()->execute(
            "UPDATE " . _DB_PREFIX_ . "customer 
                SET `newsletter` = 1, `newsletter_date_add` = NOW()
                WHERE id = {$this->context->customer->id}"
        );

        $this->context->customer->newsletter = 1;
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            '/modules/' . $this->module->name . '/views/css/manage.css',
            ['media' => 'all', 'priority' => 1000]
        );

        $this->registerJavascript(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-script',
            '/modules/' . $this->module->name . '/views/js/manage.js',
            ['priority' => 1000]
        );
    }

    protected function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();

        $breadcrumb['links'][] = [
            'title' => $this->trans('My pets', [], 'Modules.Kpypets.Shop'),
            'url' => $this->context->link->getModuleLink($this->module->name, 'display'),
        ];

        return $breadcrumb;
    }

}