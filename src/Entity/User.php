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
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(normalizationContext: ["groups" => "user"]),
		new GetCollection(normalizationContext: ["groups" => "users"])
	]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	#[Groups(["users", "user"])]
	private ?int $id = null;

	#[ORM\Column(length: 30, unique: true)]
	#[Assert\Length(min: 10, max: 30)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NoSuspiciousCharacters]
	#[Groups(["users", "user"])]
	private ?string $username = null;

	#[ORM\Column]
	#[Assert\Length(min: 10, max: 100)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Assert\NotCompromisedPassword]
	#[Groups(["users", "user"])]
	private ?string $password = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	#[Groups(["users", "user"])]
	private ?\DateTimeInterface $createdAt = null;

	#[ORM\Column(length: 45)]
	#[Groups(["users", "user"])]
	private ?string $address = null;

	#[ORM\Column]
	#[Groups(["users", "user"])]
	private array $roles = [];

	#[ORM\OneToMany(mappedBy: "user", targetEntity: Server::class, orphanRemoval: true)]
	#[Groups(["users", "user"])]
	private Collection $servers;

	#[ORM\OneToMany(mappedBy: "user", targetEntity: Command::class, orphanRemoval: true)]
	#[Groups(["users", "user"])]
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

	public function getCommands(): Collection
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