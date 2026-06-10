<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Install;

class OrderStateInstaller
{
    public function __construct(private readonly \Module $module)
    {
    }

    public function install(string $key, string $name, string $color): void
    {
        if (!\Configuration::get($key)) {
            $newOrderState = new \OrderState();

            $newOrderState->name = [];
            foreach (\Language::getLanguages() as $language) {
                $newOrderState->name[$language['id_lang']] = pSQL($name);
            }

            $newOrderState->color = $color;
            $newOrderState->logable = true;
            $newOrderState->module_name = $this->module->name;

            if ($newOrderState->add()) {
                \Configuration::updateValue($key, $newOrderState->id);
            }
        }
    }
}