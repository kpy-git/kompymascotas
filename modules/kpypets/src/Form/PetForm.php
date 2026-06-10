<?php

namespace PrestaShop\Module\Kpypets\Form;

use FormField;
use Language;
use PrestaShop\Module\KpyPets\Entity\Pet;
use PrestaShop\Module\KpyPets\Repository\PetRepository;
use PrestaShopBundle\Translation\TranslatorComponent;

class PetForm
{
    private ?TranslatorComponent $translator;

    private Language $language;

    public function __construct(?TranslatorComponent $translator, Language $language)
    {
        $this->translator = $translator;
        $this->language = $language;
    }

    public function getFormFields(Pet $pet): array
    {
        $petRepository = new PetRepository();

        $formFields = [
            (new FormField())
                ->setName('id')
                ->setType('hidden')
                ->setValue($pet->getId()),
            (new FormField())
                ->setName('name')
                ->setType('text')
                ->setLabel($this->translator->trans('Name', [], 'Modules.Kpypets.Form'))
                ->setRequired(true)
                ->setValue($pet->getName()),
            (new FormField())
                ->setName('kind')
                ->setType('select')
                ->setLabel($this->translator->trans('Kind', [], 'Modules.Kpypets.Form'))
                ->setRequired(true)
                ->setAvailableValues($petRepository->getAvailablePetKinds($this->language->id))
                ->setValue($pet->getKind()),
            (new FormField())
            ->setName('breed')
                ->setType('select')
                ->setLabel($this->translator->trans('Breed', [], 'Modules.Kpypets.Form'))
                ->setRequired(true)
                ->setAvailableValues($petRepository->getAvailableBreedByKind($pet->getKind(), $this->language->id))
                ->setValue($pet->getBreed()),
            (new FormField())
                ->setName('sex')
                ->setType('radio-buttons')
                ->setAvailableValues([
                    'M' => $this->translator->trans('male', [], 'Modules.Kpypets.Form'),
                    'H' => $this->translator->trans('female', [], 'Modules.Kpypets.Form'),
                ])
                ->setValue($pet->getSex())
                ->addClass('col-md-5'),
            (new FormField())
                ->setName('neutered')
                ->setType('checkbox')
                ->setLabel($this->translator->trans('Neutered', [], 'Modules.Kpypets.Form'))
                ->setValue($pet->isNeutered())
                ->addClass('col-md-5'),
            (new FormField())
                ->setName('birthdate')
                ->setType('date')
                ->setLabel($this->translator->trans('Birthdate', [], 'Modules.Kpypets.Form'))
                ->setRequired(true)
                ->setValue($pet->getBirthdate()),
            (new FormField())
                ->setName('haircolor')
                ->setType('text')
                ->setLabel($this->translator->trans('Haircolor', [], 'Modules.Kpypets.Form'))
                ->setValue($pet->getHairColor()),
            (new FormField())
                ->setName('weight')
                ->setType('select')
                ->setLabel($this->translator->trans('Weight', [], 'Modules.Kpypets.Form'))
                ->setAvailableValues($petRepository->getAvailableSizes())
                ->setValue($pet->getWeight()),
            (new FormField())
                ->setName('acquisition')
                ->setType('select')
                ->setLabel($this->translator->trans('Acquisition', [], 'Modules.Kpypets.Form'))
                ->setAvailableValues($petRepository->getAvailableAcquisitions($this->language->id))
                ->setValue($pet->getAcquisition()),
            (new FormField())
                ->setName('habitat')
                ->setType('select')
                ->setLabel($this->translator->trans('Habitat', [], 'Modules.Kpypets.Form'))
                ->setAvailableValues($petRepository->getAvailableHabitats($this->language->id))
                ->setValue($pet->getHabitat()),
            (new FormField())
                ->setName('longhair')
                ->setType('radio-buttons')
                ->setAvailableValues([
                    '1' => $this->translator->trans('Yes', [], 'Modules.Kpypets.Form'),
                    '0' => $this->translator->trans('No', [], 'Modules.Kpypets.Form'),
                ])
                ->setValue($pet->isLongHair())
                ->setLabel($this->translator->trans('Longhair', [], 'Modules.Kpypets.Form'))
                ->addClass('col-md-5'),
            (new FormField())
                ->setName('sleeps-out')
                ->setType('radio-buttons')
                ->setAvailableValues([
                    '1' => $this->translator->trans('Yes', [], 'Modules.Kpypets.Form'),
                    '0' => $this->translator->trans('No', [], 'Modules.Kpypets.Form'),
                ])
                ->setLabel($this->translator->trans('Sleeps out', [], 'Modules.Kpypets.Form'))
                ->setValue($pet->isSleepsOut())
                ->addClass('col-md-5'),
        ];

        foreach ($petRepository->getAvailableDiseases($this->language->id) as $id => $name) {
            $formFields[] = (new FormField())
                ->setName('disease-' . (int)$id)
                ->setType('checkbox')
                ->setLabel($name)
                ->setValue($pet->hasDisease((int)$id))
                ->addClass('col-md-5');
        }

        return array_map(static function (FormField $item) {
            return $item->toArray();
        }, $formFields);
    }

}