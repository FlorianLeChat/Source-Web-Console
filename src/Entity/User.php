<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 50)]
	private ?string $name = null;

	#[ORM\Column(length: 60)]
	private ?string $password = null;

	#[ORM\Column(length: 64)]
	private ?string $token = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $creation = null;

	#[ORM\Column(type: Types::SMALLINT)]
	private ?int $level = null;

	#[ORM\OneToMany(mappedBy: 'client', targetEntity: Server::class, orphanRemoval: true)]
	private Collection $servers;

	public function __construct()
	{
		$this->servers = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

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

	public function getToken(): ?string
	{
		return $this->token;
	}

	public function setToken(string $token): self
	{
		$this->token = $token;

		return $this;
	}

	public function getCreation(): ?\DateTimeInterface
	{
		return $this->creation;
	}

	public function setCreation(\DateTimeInterface $creation): self
	{
		$this->creation = $creation;

		return $this;
	}

	public function getLevel(): ?int
	{
		return $this->level;
	}

	public function setLevel(int $level): self
	{
		$this->level = $level;

		return $this;
	}

	/**
	 * @return Collection<int, Server>
	 */
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