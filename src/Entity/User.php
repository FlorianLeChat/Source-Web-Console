<?php

//
// Entité pour les utilisateurs.
//
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\IpUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 30, unique: true)]
	#[Assert\Length(min: 10, max: 30)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NoSuspiciousCharacters]
	private ?string $username = null;

	#[ORM\Column]
	#[Assert\Length(min: 10, max: 100)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NotCompromisedPassword]
	private ?string $password = null;

	#[ORM\Column(length: 45)]
	private ?string $address = null;

	#[ORM\Column]
	private array $roles = [];

	#[ORM\OneToMany(mappedBy: "client", targetEntity: Server::class, orphanRemoval: true)]
	private Collection $servers;

	public function __construct()
	{
		$this->servers = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUsername(): ?string
	{
		return $this->username;
	}

	public function setUsername(?string $username): self
	{
		$this->username = $username;

		return $this;
	}

	public function getUserIdentifier(): string
	{
		return (string) $this->username;
	}

	public function getPassword(): ?string
	{
		return $this->password;
	}

	public function setPassword(?string $password): self
	{
		$this->password = $password;

		return $this;
	}

	public function getAddress(): ?string
	{
		return $this->address;
	}

	public function setAddress(string $address): self
	{
		$this->address = IpUtils::anonymize($address);

		return $this;
	}

	public function getRoles(): array
	{
		return array_unique($this->roles);
	}

	public function setRoles(array $roles): self
	{
		$this->roles = $roles;

		return $this;
	}

	public function eraseCredentials()
	{
		// If you store any temporary, sensitive data on the user, clear it here
		// $this->plainPassword = null;
	}

	public function getServers(): Collection
	{
		return $this->servers;
	}

	public function addServer(Server $server): self
	{
		if (!$this->servers->contains($server))
		{
			$this->servers->add($server);
			$server->setClient($this);
		}

		return $this;
	}

	public function removeServer(Server $server): self
	{
		if ($this->servers->removeElement($server))
		{
			if ($server->getClient() === $this)
			{
				$server->setClient(null);
			}
		}

		return $this;
	}
}