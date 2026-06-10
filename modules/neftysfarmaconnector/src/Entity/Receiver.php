<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Entity;

class Receiver
{
	private $name;

	private $address;

	private $phoneNumber;

	private $postCode;

	private $state;

	private $city;

	private $email;

	private $notes = '';

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setAddress($address)
	{
		$this->address = $address;
		return $this;
	}

	public function getAddress()
	{
		return $this->address;
	}

	public function setPhoneNumber($phoneNumber)
	{
		$this->phoneNumber = substr(preg_replace('/[\D]/', '', $phoneNumber), -9, 9);
		return $this;
	}

	public function getPhoneNumber()
	{
		return $this->phoneNumber;
	}

	public function setPostCode($postCode)
	{
		$this->postCode = $postCode;
		return $this;
	}

	public function getPostCode()
	{
		return $this->postCode;
	}

	public function setState($state)
	{
		$this->state = $state;
		return $this;
	}

	public function getState()
	{
		return $this->state;
	}

	public function setCity($city)
	{
		$this->city = $city;
		return $this;
	}

	public function getCity()
	{
		return $this->city;
	}

	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setNotes($notes)
	{
		$this->notes = $notes;
		return $this;
	}

	public function getNotes()
	{
		return $this->notes;
	}

	public function toArray(): array
	{
		return [
			'NOMBRE' => $this->name,
			'DIRECCION' => $this->address,
			'CODIGOPOSTAL' => $this->postCode,
			'POBLACION' => $this->city,
			'PROVINCIA' => $this->state,
			'TELEFONO' => $this->phoneNumber,
			'EMAIL' => $this->email,
			'observaciones' => $this->notes,
		];
	}
}