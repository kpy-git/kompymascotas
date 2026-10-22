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
            $attributesUnitLabels = [];

            foreach ($attributes as $attribute) {
                $salesPrice = Product::getPriceStatic(
                    $product->id,
                    true,
                    $attribute["id_product_attribute"],
                    2,
                );

                $attributesPrices[$attribute["id_attribute"]] = [
                    'price' => Context::getContext()->getCurrentLocale()->formatPrice($salesPrice, 'EUR'),
                    'pvr' => Context::getContext()->getCurrentLocale()->formatPrice($salesPrice + Product::getPriceStatic(
                            $product->id,
                            true,
                            $attribute["id_product_attribute"],
                            2,
                            null,
                            true
                        ), 'EUR'),
                ];

                $unitPrice = $this->getUnitPrice($attribute, $salesPrice, $product->id);
                if ($unitPrice !== null) {
                    $attributesUnitPrices[$attribute["id_attribute"]] = $unitPrice["price"];
                    $attributesUnitLabels[$attribute["id_attribute"]] = $unitPrice["label"];
                }
            }

            return array_merge($params, [
                "attribute_prices" => $attributesPrices,
                "attributes_unit_prices" => $attributesUnitPrices,
                "attributes_unit_labels" => $attributesUnitLabels,
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

    /**
     * Precio por unidad de venta de una combinación, y cómo se llama esa unidad.
     *
     * El pienso se compara por kilo, pero el húmedo no: lo que el cliente compara ahí es lo que le
     * cuesta cada lata, cada tarrina o cada sobre. Y en antiparasitarios o suplementos, lo que
     * cuesta cada pipeta, cada comprimido o cada unidad de la caja.
     *
     * Las unidades y el envase salen del nombre del formato («Pack 24 x Lata de 85 gr», «3 pipetas»,
     * «Caja de 12 unidades»), porque no están en ninguna tabla. Si no se entiende, se cae al precio
     * por kilo de siempre, y si tampoco, no se enseña nada: es mejor callar que dar un número raro.
     *
     * @return array{price: float, label: string}|null
     */
    private function getUnitPrice(array $attribute, ?float $price, int $idProduct): ?array
    {
        // Un formato sin precio (PrestaShop devuelve null si le falta su fila de tienda): no hay nada que dividir.
        if ($price === null) {
            return null;
        }

        $name = trim((string)($attribute['name'] ?? ''));
        $weight = (float)$attribute['weight'];

        // Envases que se venden contados: el cliente compara por pieza, no por peso.
        $containers = [
            'lata' => 'latas?', 'tarrina' => 'tarrinas?|terrinas?', 'sobre' => 'sobres?|sachets?',
            'pouch' => 'pouch(?:es)?', 'bolsita' => 'bolsitas?', 'bandeja' => 'bandejas?',
            'bote' => 'botes?', 'barrita' => 'barritas?', 'stick' => 'sticks?',
            'pipeta' => 'pipetas?', 'comprimido' => 'comprimidos?', 'cápsula' => 'c[áa]psulas?',
            'pastilla' => 'pastillas?', 'collar' => 'collares?', 'unidad' => 'unidades?',
            // En Kompy la «bolsa» del húmedo es el sobre. Como el número tiene que ir delante,
            // un «Bolsa de 1,5 Kg» de pienso no entra aquí y sigue yendo por kilo.
            'bolsa' => 'bolsas?',
        ];

        foreach ($containers as $singular => $pattern) {
            // «Pack 24 x Lata de 85 gr», «Pack 6 sobres de 85 gr», «3 pipetas», «Caja de 12 unidades»,
            // «Pipeta hasta 4 kg, 4 unidades»: en todas, el número que manda va justo antes.
            if (!preg_match('/(\d+)\s*(?:x\s*\d*\s*)?(?:' . $pattern . ')/iu', $name, $matches)) {
                continue;
            }
            $units = (int)$matches[1];
            // Con una sola unidad el precio por unidad es el precio: no aporta nada.
            if ($units < 2) {
                return null;
            }

            return ['price' => round($price / $units, 2), 'label' => '€/' . $singular];
        }

        // Lo de siempre para el pienso: por kilo, y solo si pesa lo suficiente para que tenga sentido.
        if ($weight >= 1 && Product::isPienso($idProduct)) {
            return ['price' => round($price / $weight, 2), 'label' => '€/Kg'];
        }

        return null;
    }

    private function getAttributesInfo(int $id_product): array
    {
        return Db::getInstance()->executeS(
            "select pac.id_attribute, pa.id_product_attribute, pa.weight+p.weight as weight, al.name,
                if(pack.id_product_pack is null, 'no', 'yes') as is_pack
            from " . _DB_PREFIX_ . "product_attribute pa
            inner join " . _DB_PREFIX_ . "product p 
                on p.id_product = pa.id_product
            inner join " . _DB_PREFIX_ . "product_attribute_combination pac 
                on pac.id_product_attribute = pa.id_product_attribute
            left join " . _DB_PREFIX_ . "kpy_packs pack
                on pack.id_product_pack = CONCAT_WS('-', p.id_product, pa.id_product_attribute)
            inner join " . _DB_PREFIX_ . "attribute_lang al
                on al.id_attribute = pac.id_attribute and al.id_lang = {$this->context->language->id}
            where pa.id_product = {$id_product} 
                and not exists (SELECT 1 
                    FROM " . _DB_PREFIX_ . "kpy_product_attribute kpa 
                    WHERE kpa.id_product_attribute = pa.id_product_attribute 
                        and kpa.active = 0)",
        );
    }

}
