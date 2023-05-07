<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ServerRepository;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
class Server
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: 'servers')]
	#[ORM\JoinColumn(nullable: false)]
	private ?user $client = null;

	#[ORM\Column(length: 15)]
	private ?string $address = null;

	#[ORM\Column(length: 5)]
	private ?string $port = null;

	#[ORM\Column(length: 255)]
	private ?string $password = null;

	#[ORM\Column]
	private ?int $game = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getClient(): ?user
	{
		return $this->client;
	}

	public function setClient(?user $client): self
	{
		$this->client = $client;

		return $this;
	}

	public function getAddress(): ?string
	{
		return $this->address;
	}

	public function setAddress(string $address): self
	{
		$this->address = $address;

		return $this;
	}

	public function getPort(): ?string
	{
		return $this->port;
	}

	public function setPort(string $port): self
	{
		$this->port = $port;

		return $this;
	}

	public function getPassword(): ?string
	{
		return $this->password;
	}

	public function setPassword(string $password): self
	{
		$this->password = $password;

		return $this;
	}

	public function getGame(): ?int
	{
		return $this->game;
	}

	public function setGame(int $game): self
	{
		$this->game = $game;

		return $this;
	}
}