<?php

//
// Entité pour les utilisateurs.
//
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\IpUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(),
		new GetCollection()
	]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private ?int $id = null;

	#[ORM\Column(length: 30, unique: true)]
	#[Assert\Length(min: 10, max: 30)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NoSuspiciousCharacters]
	private ?string $username = null;

	#[ORM\Column]
	#[Assert\Length(min: 10, max: 50)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NotCompromisedPassword]
	private ?string $password = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $createdAt = null;

	#[ORM\Column(length: 45)]
	private ?string $address = null;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $token = null;

	/** @var array<mixed> */
	#[ORM\Column]
	private array $roles = [];

	/** @var Collection<int, Server> */
	#[ORM\OneToMany(mappedBy: "user", targetEntity: Server::class, orphanRemoval: true)]
	private Collection $servers;

	/** @var Collection<int, Command> */
	#[ORM\OneToMany(mappedBy: "user", targetEntity: Command::class, orphanRemoval: true)]
	private Collection $commands;

	public function __construct()
	{
		$this->servers = new ArrayCollection();
		$this->commands = new ArrayCollection();
	}

	public function __toString(): string
	{
		return "[#{$this->id}] {$this->username}";
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

	public function getCreatedAt(): ?\DateTimeInterface
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeInterface $createdAt): static
	{
		$this->createdAt = $createdAt;

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

	public function getToken(): ?string
	{
		return $this->token;
	}

	public function setToken(?string $token): static
	{
		$this->token = $token;

		return $this;
	}

	public function getRoles(): array
	{
		return array_unique($this->roles);
	}

	public function setRoles(mixed $roles): self
	{
		$this->roles = $roles;

		return $this;
	}

	public function eraseCredentials(): void
	{
		// $this->plainPassword = null;
	}

	/** @return Collection<int, Server> */
	public function getServers()
	{
		return $this->servers;
	}

	public function addServer(Server $server): self
	{
		if (!$this->servers->contains($server))
		{
			$this->servers->add($server);
			$server->setUser($this);
		}

		return $this;
	}

	public function removeServer(Server $server): self
	{
		if ($this->servers->removeElement($server))
		{
			if ($server->getUser() === $this)
			{
				$server->setUser(null);
			}
		}

		return $this;
	}

	/** @return Collection<int, Command> */
	public function getCommands()
	{
		return $this->commands;
	}

	public function addCommand(Command $command): static
	{
		if (!$this->commands->contains($command))
		{
			$this->commands->add($command);
			$command->setUser($this);
		}

		return $this;
	}

	public function removeCommand(Command $command): static
	{
		if ($this->commands->removeElement($command))
		{
			if ($command->getUser() === $this)
			{
				$command->setUser(null);
			}
		}

		return $this;
	}
}