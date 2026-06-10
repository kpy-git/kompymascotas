<?php

declare(strict_types=1);

use PrestaShop\Module\KpyProductForm\Entity\KpyCombination;
use PrestaShop\Module\KpyProductForm\Form\Modifier\CombinationFormModifier;
use PrestaShop\Module\KpyProductForm\Install\Installer;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\ValueObject\CombinationId;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyProductForm extends Module
{
    public function __construct()
    {
        $this->name = 'kpyproductform';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Product Form', [], 'Modules.Kpyproductform.Admin');
        $this->description = $this->trans('Add extra fields in product/combination forms.', [], 'Modules.Kpyproductform.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyproductform.Admin');

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

    /**
     * Hook that modifies the combination form structure.
     *
     * @param array $params
     */
    public function hookActionCombinationFormFormBuilderModifier(array $params): void
    {
        /** @var CombinationFormModifier $productFormModifier */
        $productFormModifier = $this->get(CombinationFormModifier::class);
        $combinationId = isset($params['id']) ? new CombinationId((int)$params['id']) : null;

        $productFormModifier->modify($combinationId, $params['form_builder']);
    }

    /**
     * Hook called after form is submitted and combination is updated, custom data is updated here.
     *
     * @param array $params
     */
    public function hookActionAfterUpdateCombinationFormFormHandler(array $params): void
    {
        // file_put_contents(_PS_LOG_DIR_ . 'kpyproductform.json', json_encode($params));
        $combinationId = $params['form_data']['id'];
        $combination = new KpyCombination($combinationId);
        $combination->active = $params['form_data']['kpy_active'] ?? true;

        $this->persistCombination($combination, $combinationId);

        $packs = Product::getPacksArrayBySku($params['form_data']['product_id'], $combinationId);

        if (!empty($packs)) {
            foreach ($packs as $pack) {
                [$idProduct, $idProductAttributePack] = explode('-', $pack);
                $combinationPack = new KpyCombination((int)$idProductAttributePack);
                $combinationPack->active = $params['form_data']['kpy_active'] ?? true;
                $this->persistCombination($combinationPack, (int)$idProductAttributePack);
            }
        }
    }

    private function persistCombination(KpyCombination $combination, int $combinationId): void
    {
        /* guarda/actualiza la tabla kpy_product_attribute */
        if (empty($combination->id)) {
            // If custom is not found it has not been created yet, so we force its ID to match the combination ID
            $combination->id = $combinationId;
            $combination->force_id = true;
            $combination->add();
        } else {
            $combination->update();
        }
    }

    public function hookActionProductGetAttributesGroupsBefore(array $params): void
    {
        if ($params['id_product_attribute'] !== null) {
            return;
        }

        // modifica la consulta para no sacar las combinaciones desactivadas en la página de producto
        $params['query']->where('not exists (select 1 
            from ' . _DB_PREFIX_ .'kpy_product_attribute kpa 
            where pa.id_product_attribute=kpa.id_product_attribute and kpa.active=0)');
    }
}
