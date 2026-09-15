<?php

if (!defined("_PS_VERSION_")) {
    exit();
}

class KpyFrontControllerVariables extends Module
{
    public function __construct()
    {
        $this->name = "kpyfrontcontrollervariables";
        $this->tab = "front_office_features";
        $this->version = "1.0.0";
        $this->author = "Kpy Team";
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            "min" => "8.2",
            "max" => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans(
            "KPY FrontController Variables",
            [],
            "Modules.Kpyfrontcontrollervariables.Admin",
        );
        $this->description = $this->trans(
            "Description of module",
            [],
            "Modules.Kpyfrontcontrollervariables.Admin",
        );

        $this->confirmUninstall = $this->trans(
            "Are you sure you want to uninstall?",
            [],
            "Modules.Kpyfrontcontrollervariables.Admin",
        );
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook("actionFrontControllerSetVariables")
            && $this->registerHook("displayProductListVariantsDetails");
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayProductListVariantsDetails(array $params): string
    {
        $product = $params['product'];

        if ($product->product_type === 'combinations' && Product::isPienso($product->id)) {
            $variants = $this->getAttributesInfo($product->id_product);

            if (count($variants) <= 1) {
                return '';
            }

            $variantsWithoutPacks = array_column(array_filter($variants, static fn(array $pack) => $pack['is_pack'] === 'no'), 'weight');

            if (empty($variantsWithoutPacks)) {
                return '';
            }
            $minWeight = $this->formatWeight(min($variantsWithoutPacks));
            $maxWeight = $this->formatWeight(max($variantsWithoutPacks));

            $options = $minWeight != $maxWeight ? "desde {$minWeight} hasta {$maxWeight}" : $minWeight;

            $this->smarty->assign([
                'kpy_variants' => $options
            ]);

            return $this->fetch('module:' . $this->name . '/views/templates/hook/display-variants-details.tpl');
        }

        return '';
    }

    private function formatWeight(float $weight): string
    {
        if ($weight < 1) {
            return ($weight * 1000) . 'Gr';
        }

        // si es del tipo 13.0 devolverá 13
        $weightFormatted = (int)$weight == $weight ? (int)$weight : rtrim((string)$weight, '0');

        return $weightFormatted . 'Kg';
    }

    public function hookActionFrontControllerSetVariables(): array
    {
        $params = [
            'is_mobile' => $this->context->isMobile(),
        ];

        if ($this->context->controller instanceof OrderControllerCore) {
            return array_merge($params, [
                'current_step_checkout' => $this->context->controller->getCheckoutProcess()->getCurrentStep()->getIdentifier(),
            ]);
        }

        if ($this->context->controller instanceof ProductControllerCore) {
            $product = $this->context->controller->getProduct();

            $attributes = $this->getAttributesInfo($product->id);

            $attributesPrices = [];
            $attributesUnitPrices = [];

            foreach ($attributes as $attribute) {
                $attributesPrices[$attribute["id_attribute"]] = Product::getPriceStatic(
                    $product->id,
                    true,
                    $attribute["id_product_attribute"],
                    2,
                );

                $weight = (float)$attribute["weight"];
                if ($weight >= 1 && Product::isPienso($product->id)) {
                    $attributesUnitPrices[$attribute["id_attribute"]] = round($attributesPrices[$attribute["id_attribute"]] / $weight, 2);
                }
            }

            return array_merge($params, [
                "attribute_prices" => $attributesPrices,
                "attributes_unit_prices" => $attributesUnitPrices,
            ]);
        }

        if ($this->context->controller instanceof MyAccountControllerCore) {

            return array_merge($params, [
                "customerActiveCartRulesCount" => (int)Db::getInstance()->getValue(
                    "select count(*)
                    from " . _DB_PREFIX_ . "cart_rule
                    where id_customer = {$this->context->customer->id} 
                        and NOW() between date_from and date_to
                        and active = 1
                        and quantity > 0
                        and highlight = 1"
                ),
            ]);
        }

        return $params;
    }

    private function getAttributesInfo(int $id_product): array
    {
        return Db::getInstance()->executeS(
            "select pac.id_attribute, pa.id_product_attribute, pa.weight+p.weight as weight, 
                if(pack.id_product_pack is null, 'no', 'yes') as is_pack
            from " . _DB_PREFIX_ . "product_attribute pa
            inner join " . _DB_PREFIX_ . "product p 
                on p.id_product = pa.id_product
            inner join " . _DB_PREFIX_ . "product_attribute_combination pac 
                on pac.id_product_attribute = pa.id_product_attribute
            left join " . _DB_PREFIX_ . "kpy_packs pack
                on pack.id_product_pack = CONCAT_WS('-', p.id_product, pa.id_product_attribute)
            where pa.id_product = {$id_product} 
                and not exists (SELECT 1 
                    FROM " . _DB_PREFIX_ . "kpy_product_attribute kpa 
                    WHERE kpa.id_product_attribute = pa.id_product_attribute 
                        and kpa.active = 0)",
        );
    }

}
