<?php

namespace PrestaShop\Module\KpyPets\Repository;

use Db;
use PrestaShop\Module\KpyPets\Entity\Pet;
use PrestaShop\Module\KpyPets\Exception\PetException;

class PetRepository
{
    /**
     * @throws PetException
     */
    public function getPetById(int $id_pet): Pet
    {
        $sql = "SELECT p.id, p.name, hair_color, birth_date, id_customer, sex, neutered, size, id_race, 
            id_habitat, id_adquisition, long_hair, sleeps_out, id_kind, GROUP_CONCAT(pdr.id_disease) as diseases
            FROM `" . _DB_PREFIX_ . "kpy_pet` p
            LEFT JOIN `" . _DB_PREFIX_ . "kpy_pet_disease_relation` pdr 
                on pdr.id_pet = p.id
            WHERE p.active = 1 and p.id = " . pSQL($id_pet) . "
            GROUP BY p.id";

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            throw new PetException('Not existent pet with id ' . $id_pet);
        }

        return (new Pet())
            ->setId($id_pet)
            ->setIdCustomer($result['id_customer'])
            ->setName($result['name'])
            ->setHairColor($result['hair_color'])
            ->setBirthDate($result['birth_date'])
            ->setNeutered($result['neutered'] == 1)
            ->setSex($result['sex'])
            ->setBreed((int)$result['id_race'])
            ->setWeight((int)$result['size'])
            ->setKind((int)$result['id_kind'])
            ->setAcquisition((int)$result['id_adquisition'])
            ->setLongHair($result['long_hair'] == 1)
            ->setSleepsOut($result['sleeps_out'] == 1)
            ->setHabitat((int)$result['id_habitat'])
            ->setDiseases(!empty($result['diseases']) ? explode(',', $result['diseases']) : []);
    }

    public function getAvailablePetKinds(int $id_lang): array
    {
        $sql = "SELECT pk.id, pkl.name
            FROM `" . _DB_PREFIX_ . "kpy_pet_kinds` pk
            INNER JOIN `" . _DB_PREFIX_ . "kpy_pet_kinds_lang` pkl 
                ON pkl.id = pk.id
                AND pkl.id_lang = $id_lang";

        return array_reduce(Db::getInstance()->executeS($sql), static function (array $kinds, array $row) {
            $kinds[$row['id']] = $row['name'];
            return $kinds;
        }, []);
    }

    public function getAvailableBreedByKind(int $kind, int $id_lang): array
    {
        $sql = "select pr.id, prl.name
            from " . _DB_PREFIX_ . "kpy_pet_races pr
            inner join " . _DB_PREFIX_ . "kpy_pet_races_lang prl 
                on prl.id_race = pr.id and prl.id_lang = $id_lang
            where pr.id_kind = $kind
            order by FIELD(prl.name, 'Sin raza determinada', 'Mestizo', 'Sin determinar') DESC, prl.name ASC";

        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return [];
        }

        return array_reduce($results, static function (array $breeds, array $row) {
            $breeds[$row['id']] = $row['name'];
            return $breeds;
        }, []);
    }

    public function getAvailableSizes(): array
    {
        return array_reduce(range(1, 70), static function (array $sizes, int $size) {
            $sizes[$size] = $size;
            return $sizes;
        }, []);
    }

    public function getAvailableAcquisitions(int $id_lang): array
    {
        $sql = "select pa.id, pal.name
            from `" . _DB_PREFIX_ . "kpy_pet_adquisition` pa
            inner join `" . _DB_PREFIX_ . "kpy_pet_adquisition_lang` pal 
                on pal.id_adquisition = pa.id and pal.id_lang = $id_lang
            order by name";

        $results = Db::getInstance()->executeS($sql);

        return array_reduce($results, static function (array $acquisitions, array $row) {
            $acquisitions[$row['id']] = $row['name'];
            return $acquisitions;
        }, []);
    }

    public function getAvailableHabitats(int $id_lang): array
    {
        $sql = "select ph.id, phl.name
            from `" . _DB_PREFIX_ . "kpy_pet_habitats` ph
            inner join `" . _DB_PREFIX_ . "kpy_pet_habitats_lang` phl
                on phl.id_habitat = ph.id and phl.id_lang = $id_lang
            order by name";

        $results = Db::getInstance()->executeS($sql);

        return array_reduce($results, static function (array $habitats, array $row) {
            $habitats[$row['id']] = $row['name'];
            return $habitats;
        }, []);
    }

    public function getAvailableDiseases(int $id_lang): array
    {
        $sql = "select pd.id, pdl.name
            from `" . _DB_PREFIX_ . "kpy_pet_diseases` pd
            inner join `" . _DB_PREFIX_ . "kpy_pet_diseases_lang` pdl
                on pdl.id_disease = pd.id
                    and pdl.id_lang = $id_lang
            order by pdl.name";

        $results = Db::getInstance()->executeS($sql);

        return array_reduce($results, static function (array $diseases, array $row) {
            $diseases[$row['id']] = $row['name'];
            return $diseases;
        }, []);
    }

    public function savePet(Pet $pet): bool
    {
        if ($pet->getId() > 0) {
            $result = Db::getInstance()->update('kpy_pet', $this->normalize($pet), '`id` = ' . $pet->getId());
        } else {
            $result = Db::getInstance()->insert('kpy_pet', $this->normalize($pet));
            $pet->setId((int)Db::getInstance()->getValue("SELECT LAST_INSERT_ID()"));
        }

        if ($pet->hasDiseases()) {
            $result &= Db::getInstance()->delete('kpy_pet_disease_relation', 'id_pet = ' . $pet->getId());
            $diseases = array_map(fn(int $disease) => ['id_pet' => $pet->getId(), 'id_disease' => $disease], $pet->getDiseases());

            $result &= Db::getInstance()->insert('kpy_pet_disease_relation', $diseases, false, false, Db::INSERT_IGNORE);
        }

        return $result;
    }

    private function normalize(Pet $pet): array
    {
        return [
            'name' => pSQL($pet->getName()),
            'sex' => $pet->getSex(),
            'id_customer' => $pet->getIdCustomer(),
            'size' => $pet->getWeight(),
            'neutered' => $pet->isNeutered() ? 1 : 0,
            'long_hair' => $pet->isLongHair() ? 1 : 0,
            'sleeps_out' => $pet->isSleepsOut() ? 1 : 0,
            'hair_color' => pSQL($pet->getHairColor()),
            'id_kind' => $pet->getKind(),
            'id_adquisition' => $pet->getAcquisition(),
            'id_habitat' => $pet->getHabitat(),
            'id_race' => $pet->getBreed(),
            'birth_date' => $pet->getBirthDate(),
            'active' => $pet->isActive() ? 1 : 0,
        ];
    }

    public function disablePetById(int $id_pet): void
    {
        Db::getInstance()->update('kpy_pet', ['active' => 0], '`id` = ' . $id_pet);
    }
}
