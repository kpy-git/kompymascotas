<?php

namespace PrestaShop\Module\KpyPets\Entity;

class Pet
{
    private int $id;

    private int $kind;

    private string $name;

    private string $hairColor;

    private string $birthdate;

    private int $id_customer;

    private string $sex;

    private bool $neutered;

    private int $breed;

    private int $weight;

    private int $acquisition;

    private int $habitat;

    private bool $longHair;

    private bool $sleepsOut;

    private array $diseases;

    private bool $active = true;

    public function __construct()
    {
        $this->id = 0;
        $this->name = '';
        $this->id_customer = 0;
        $this->birthdate = date('d/m/Y');
        $this->hairColor = '';
        $this->kind = 0;
        $this->breed = 0;
        $this->weight = 0;
        $this->acquisition = 0;
        $this->habitat = 0;
        $this->longHair = false;
        $this->sleepsOut = false;
        $this->diseases = [];
        $this->sex = 'M';
        $this->neutered = false;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Pet
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Pet
    {
        $this->name = $name;
        return $this;
    }

    public function getIdCustomer(): int
    {
        return $this->id_customer;
    }

    public function setIdCustomer(int $id_customer): Pet
    {
        $this->id_customer = $id_customer;
        return $this;
    }

    public function getHairColor(): string
    {
        return $this->hairColor;
    }

    public function setHairColor(string $hairColor): Pet
    {
        $this->hairColor = $hairColor;
        return $this;
    }

    public function getBirthdate(): string
    {
        return $this->birthdate;
    }

    public function setBirthdate(string $birthdate): Pet
    {
        $this->birthdate = $birthdate;
        return $this;
    }

    public function getSex(): string
    {
        return $this->sex;
    }

    public function setSex(string $sex): Pet
    {
        $this->sex = $sex;
        return $this;
    }

    public function isNeutered(): bool
    {
        return $this->neutered;
    }

    public function setNeutered(bool $neutered): Pet
    {
        $this->neutered = $neutered;
        return $this;
    }

    public function getKind(): int
    {
        return $this->kind;
    }

    public function setKind(int $kind): Pet
    {
        $this->kind = $kind;
        return $this;
    }

    public function getBreed(): int
    {
        return $this->breed;
    }

    public function setBreed(int $breed): Pet
    {
        $this->breed = $breed;
        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): Pet
    {
        $this->weight = $weight;
        return $this;
    }

    public function getAcquisition(): int
    {
        return $this->acquisition;
    }

    public function setAcquisition(int $acquisition): Pet
    {
        $this->acquisition = $acquisition;
        return $this;
    }

    public function getHabitat(): int
    {
        return $this->habitat;
    }

    public function setHabitat(int $habitat): Pet
    {
        $this->habitat = $habitat;
        return $this;
    }

    public function isLongHair(): bool
    {
        return $this->longHair;
    }

    public function setLongHair(bool $longHair): Pet
    {
        $this->longHair = $longHair;
        return $this;
    }

    public function isSleepsOut(): bool
    {
        return $this->sleepsOut;
    }

    public function setSleepsOut(bool $sleepsOut): Pet
    {
        $this->sleepsOut = $sleepsOut;
        return $this;
    }

    public function getDiseases(): array
    {
        return $this->diseases;
    }

    public function setDiseases(array $diseases): Pet
    {
        $this->diseases = array_map(static fn(string $disease) => (int)$disease, $diseases);
        return $this;
    }

    public function hasDisease(int $disease): bool
    {
        return in_array($disease, $this->diseases, true);
    }

    public function addDisease(int $disease): Pet
    {
        $this->diseases[] = $disease;
        $this->diseases = array_unique($this->diseases);

        return $this;
    }

    public function hasDiseases(): bool
    {
        return !empty($this->diseases);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): Pet
    {
        $this->active = $active;
        return $this;
    }

}