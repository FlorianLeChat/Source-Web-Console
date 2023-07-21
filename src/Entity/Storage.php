<?php

//
// Entité pour les informations de stockage des serveurs.
//
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\StorageRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StorageRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(),
		new GetCollection()
	]
)]
class Storage
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private ?int $id = null;

	#[ORM\OneToOne(inversedBy: "storage", cascade: ["persist", "remove"])]
	#[ORM\JoinColumn(nullable: false)]
	private ?Server $server = null;

	#[ORM\Column(length: 15)]
	#[Assert\Ip]
	#[Assert\Length(min: 7, max: 15)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $address = null;

	#[ORM\Column(length: 5)]
	#[Assert\Length(min: 1, max: 5)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $port = null;

	#[ORM\Column(length: 4)]
	#[Assert\Choice(["ftp", "sftp"])]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $protocol = null;

	#[ORM\Column(length: 255)]
	#[Assert\Length(max: 255)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NoSuspiciousCharacters]
	private ?string $username = null;

	#[ORM\Column(length: 255)]
	#[Assert\Length(max: 255)]
	private ?string $password = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getServer(): ?Server
	{
		return $this->server;
	}

	public function setServer(Server $server): static
	{
		$this->server = $server;

		return $this;
	}

	public function getAddress(): ?string
	{
		return $this->address;
	}

	public function setAddress(?string $address): static
	{
		$this->address = $address;

		return $this;
	}

	public function getPort(): ?string
	{
		return $this->port;
	}

	public function setPort(?string $port): static
	{
		$this->port = $port;

		return $this;
	}

	public function getProtocol(): ?string
	{
		return $this->protocol;
	}

	public function setProtocol(?string $protocol): static
	{
		$this->protocol = $protocol;

		return $this;
	}

	public function getUsername(): ?string
	{
		return $this->username;
	}

	public function setUsername(?string $username): static
	{
		$this->username = $username;

		return $this;
	}

	public function getPassword(): ?string
	{
		return $this->password;
	}

	public function setPassword(?string $password): static
	{
		$this->password = $password;

		return $this;
	}
}