<?php

namespace PrestaShop\Module\KpyManufacturer\LandingBuilder;

use Context;
use Module;
use PrestaShop\Module\KpyManufacturer\Exception\LandingBuilderException;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

class LandingBuilderFactory
{
    private Module $module;

    private Context $context;

    private TranslatorInterface $translator;

    /** @var AbstractLandingBuilder[] */
    private array $builders;

    public function __construct(Module $module, Context $context, TranslatorInterface $translator)
    {
        $this->module = $module;
        $this->context = $context;
        $this->translator = $translator;

        $this->builders = [];

        $this->scanBuilders();
    }

    private function scanBuilders(): void
    {
        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->in(_PS_MODULE_DIR_ . $this->module->name . '/src/LandingBuilder/Implement');

        if ($finder->hasresults()) {
            // Primero añadimos las landings personalizadas
            foreach ($finder as $file) {
                if ($file->isReadable()) {
                    $class = new \ReflectionClass("PrestaShop\\Module\\KpyManufacturer\\LandingBuilder\\Implement\\" . $file->getFilenameWithoutExtension());

                    if (!$class->issubclassOf(AbstractLandingBuilder::class)) {
                        continue;
                    }

                    /** @var AbstractLandingBuilder $instance */
                    $instance = $class->newInstance();
                    $instance
                        ->setModule($this->module)
                        ->setContext($this->context)
                        ->setTranslator($this->translator);

                    $this->builders[] = $instance;
                }
            }
        }

        // el último lugar será para las landings genéricas
        $genericBuilder = new GenericLandingBuilder();
        $genericBuilder
            ->setContext($this->context)
            ->setModule($this->module)
            ->setTranslator($this->translator);

        $this->builders[] = $genericBuilder;
    }

    /**
     * @throws LandingBuilderException
     */
    public function getBuilderByManufacturerID(int $manufacturerId): AbstractLandingBuilder
    {
        foreach ($this->builders as $builder) {
            if ($builder->isRightManufacturer($manufacturerId)) {
                if ($builder instanceof GenericLandingBuilder) {
                    $builder->setManufacturerId($manufacturerId);
                }

                return $builder;
            }
        }

        throw new LandingBuilderException('Builder not found');
    }
}