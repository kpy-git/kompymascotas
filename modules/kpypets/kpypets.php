<?php

declare(strict_types=1);

use PrestaShop\Module\KpyPets\Config\KpyPetsConfig;
use Prestashop\Module\KpyPets\Exception\PetException;
use PrestaShop\Module\KpyPets\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyPets extends Module
{
    public function __construct()
    {
        $this->name = 'kpypets';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Pets', [], 'Modules.Kpypets.Admin');
        $this->description = $this->trans("Allow customers pets.", [], 'Modules.Kpypets.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpypets.Admin');

    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $installer = new Installer();

        return $installer->install($this);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $installer = new Installer();

        return $installer->uninstall($this);
    }

    /**
     * @see https://devdocs.prestashop.com/8/modules/creation/module-translation/new-system/#translating-your-module
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function hookDisplayCustomerAccount(array $params): string
    {
        $petsCount = $this->getCustomerPetsCount();

        return $this->fetch("module:{$this->name}/views/templates/hook/customerAccount.tpl", [
            'title' => $this->trans('My pets', [], 'Modules.Kpypets.Shop'),
            'subtitle' => $petsCount === 0
                ? $this->trans('You do not have any registered pets', [], 'Modules.Kpypets.Shop')
                : $this->trans('You have %pets% pets registered', ['%pets%' => $petsCount], 'Modules.Kpypets.Shop'),
            'link' => $this->context->link->getModuleLink($this->name, 'display'),
        ]);
    }

    public function getCustomerPetsCount(bool $only_active = true): int
    {
        if (!$this->context->customer->isLogged()) {
            return 0;
        }

        return (int)Db::getInstance()->getValue(
            "SELECT count(*) FROM `" . _DB_PREFIX_ . "kpy_pet`
            WHERE id_customer = " . $this->context->customer->id . ($only_active ? " and active=1" : "")
        );
    }

    public function getCustomerPets(): array
    {
        $sql = "select p.id, p.name, p.birth_date, p.sex, p.id_kind, pr.name as raza, p.hair_color, p.size
            from " . _DB_PREFIX_ . "kpy_pet p
            inner join " . _DB_PREFIX_ . "kpy_pet_races pr
                on pr.id = p.id_race
            where p.id_customer = {$this->context->customer->id}
                and active = 1";

        $results = Db::getInstance()->executeS($sql);

        return array_map(function (array $row) {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'sex' => $row['sex'] === 'M'
                    ? $this->trans('Male', [], 'Modules.Kpypets.Shop')
                    : $this->trans('Female', [], 'Modules.Kpypets.Shop'),
                'weight' => (int)$row['size'],
                'hair_color' => $row['hair_color'],
                'kind' => $this->getPetKindName((int)$row['id_kind']),
                'race' => $row['raza'],
                'age' => $this->calculateAgeFrom($row['birth_date']),
                'icon' => $this->getIconPet((int)$row['id_kind']),
            ];
        }, $results);
    }

    private function calculateAgeFrom(?string $date): string
    {
        if (!$date) {
            return '';
        }

        $diff = (new DateTimeImmutable())->diff(new DateTimeImmutable($date));

        $age = (int)$diff->format('%y');

        if ($age) {
            return $this->trans('%age% years', ['%age%' => $age], 'Modules.Kpypets.Shop');
        }

        $months = $diff->format('%m');

        return $this->trans('%months% months', ['%months%' => $months], 'Modules.Kpypets.Shop');
    }

    public function hookDisplayCustomerAccountMenu(array $params): string
    {
        $link = $this->context->link->getModuleLink($this->name, 'display');
        return $this->fetch("module:{$this->name}/views/templates/hook/customerAccountMenu.tpl", [
            'active' => $link === Tools::getCurrentUrl(),
            'link' => $link,
        ]);
    }

    public function getPetKindName(int $id_kind): string
    {
        return match ($id_kind) {
            1 => $this->trans('Dog', [], 'Modules.Kpypets.Shop'),
            2 => $this->trans('Cat', [], 'Modules.Kpypets.Shop'),
            3 => $this->trans('Rodent', [], 'Modules.Kpypets.Shop'),
            4 => $this->trans('Reptile', [], 'Modules.Kpypets.Shop'),
            5 => $this->trans('Bird', [], 'Modules.Kpypets.Shop'),
            6 => $this->trans('Fish', [], 'Modules.Kpypets.Shop'),
            default => $this->trans('Unknown', [], 'Modules.Kpypets.Shop'),
        };
    }

    public function getIconPet(int $id_kind): string
    {
        return match ($id_kind) {
            1 => 'dog-face.svg',
            2 => 'cat-face.svg',
            3 => 'roedent-face.svg',
            4 => 'turtle.svg',
            5 => 'parrot.svg',
            6 => 'fish.svg',
            default => '',
        };
    }

    public function createVoucherIfApplicable(): void
    {
        /**
         * - es la primera mascota que se registra
         * - el email ha sido validado
         * - está suscrito a la newsletter
         */
        if ($this->getCustomerPetsCount(false) > 1) {
            return;
        }

        $accountVerify = Module::getInstanceByName('kpyaccountverify');

        if ($accountVerify
            && $accountVerify->active
            && !$accountVerify->isAccountVerified($this->context->customer->id)
        ) {
            return;
        }

        if (!$this->context->customer->newsletter) {
            return;
        }

        $codePrefix = Configuration::get(KpyPetsConfig::CART_RULE_PREFFIX);
        $code = $codePrefix . Tools::passwdGen(10 - strlen($codePrefix));

        $name = Configuration::get(KpyPetsConfig::CART_RULE_NAME);

        Db::getInstance()->insert('cart_rule', [
            'id_customer' => $this->context->customer->id,
            'description' => $name,
            'date_from' => date('Y-m-d H:i:s'),
            'date_to' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'quantity' => 1,
            'quantity_per_user' => 1,
            'priority' => 1,
            'partial_use' => 0,
            'code' => $code,
            'minimum_amount' => 0,
            'minimum_amount_tax' => 1,
            'minimum_amount_currency' => 1,
            'minimum_amount_shipping' => 0,
            'country_restriction' => 0,
            'carrier_restriction' => 0,
            'group_restriction' => 0,
            'cart_rule_restriction' => 0,
            'product_restriction' => 0,
            'shop_restriction' => 0,
            'free_shipping' => 0,
            'reduction_percent' => 0,
            'reduction_amount' => (float)Configuration::get(KpyPetsConfig::CART_RULE_AMONT),
            'reduction_tax' =>  1,
            'reduction_currency' => $this->context->currency->id,
            'reduction_product' => 0,
            'gift_product' => 0,
            'gift_product_attribute' => 0,
            'highlight' => 1,
            'active' => 1,
            'date_add' => date("Y-m-d H:i:s"),
            'date_upd' => date("Y-m-d H:i:s"),
        ]);

        $id_cart_rule = (int)Db::getInstance()->getValue("SELECT id_cart_rule FROM " . _DB_PREFIX_ . "cart_rule WHERE code='{$code}'");

        if (!$id_cart_rule) {
            throw new PetException('An error occurred while creating the voucher.');
        }

        Db::getInstance()->insert('cart_rule_lang', [
            'id_cart_rule' => $id_cart_rule,
            'id_lang' => $this->context->language->id,
            'name' => $name,
        ]);
    }
}
