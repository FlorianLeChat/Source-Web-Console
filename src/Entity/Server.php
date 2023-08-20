<?php

//
// Entité pour les serveurs distants.
//
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ServerRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(),
		new GetCollection()
	]
)]
class Server
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "servers")]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	#[ORM\Column(length: 15)]
	#[Assert\Ip]
	#[Assert\Length(min: 7, max: 15)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $address = null;

	#[ORM\Column()]
	#[Assert\Range(min: 1, max: 99999)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?int $port = null;

	#[ORM\Column(length: 255, nullable: true)]
	#[Assert\Length(max: 255)]
	private ?string $password = null;

	#[ORM\Column(nullable: true)]
	private ?int $game = null;

	#[ORM\OneToMany(mappedBy: "server", targetEntity: Task::class, orphanRemoval: true)]
	private Collection $tasks;

	#[ORM\OneToMany(mappedBy: "server", targetEntity: Event::class, orphanRemoval: true)]
	private Collection $events;

	#[ORM\OneToMany(mappedBy: "server", targetEntity: Stats::class, orphanRemoval: true)]
	private Collection $stats;

	#[ORM\OneToOne(mappedBy: "server", cascade: ["persist", "remove"])]
	private ?Storage $storage = null;

	public const ACTION_SHUTDOWN = "shutdown";
	public const ACTION_SHUTDOWN_FORCE = "force";
	public const ACTION_RESTART = "restart";
	public const ACTION_UPDATE = "update";
	public const ACTION_SERVICE = "service";

	public function __construct()
	{
		$this->tasks = new ArrayCollection();
		$this->stats = new ArrayCollection();
		$this->events = new ArrayCollection();
	}

	public function __toString(): string
	{
		return "[#{$this->id}] {$this->address}:{$this->port}";
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): self
	{
		$this->user = $user;

		return $this;
	}

	public function getAddress(): ?string
	{
		return $this->address;
	}

	public function setAddress(?string $address): self
	{
		$this->address = $address;

		return $this;
	}

	public function getPort(): ?int
	{
		return $this->port;
	}

	public function setPort(?int $port): self
	{
		$this->port = $port;

		return $this;
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

	public function getGame(): ?int
	{
		return $this->game;
	}

	public function setGame(int $game): self
	{
		$this->game = $game;

		return $this;
	}

	public function getTasks(): Collection
	{
		return $this->tasks;
	}

	public function addTask(Task $task): self
	{
		if (!$this->tasks->contains($task))
		{
			$this->tasks->add($task);
			$task->setServer($this);
		}

		return $this;
	}

	public function removeTask(Task $task): self
	{
		if ($this->tasks->removeElement($task))
		{
			if ($task->getServer() === $this)
			{
				$task->setServer(null);
			}
		}

		return $this;
	}

	public function getEvents(): Collection
	{
		return $this->events;
	}

	public function addEvent(Event $event): self
	{
		if (!$this->events->contains($event))
		{
			$this->events->add($event);
			$event->setServer($this);
		}

		return $this;
	}

	public function removeEvent(Event $event): self
	{
		if ($this->events->removeElement($event))
		{
			if ($event->getServer() === $this)
			{
				$event->setServer(null);
			}
		}

		return $this;
	}

	public function getStats(): Collection
	{
		return $this->stats;
	}

	public function addStat(Stats $stat): static
	{
		if (!$this->stats->contains($stat))
		{
			$this->stats->add($stat);
			$stat->setServer($this);
		}

		return $this;
	}

	public function removeStat(Stats $stat): static
	{
		if ($this->stats->removeElement($stat))
		{
			if ($stat->getServer() === $this)
			{
				$stat->setServer(null);
			}
		}

		return $this;
	}

	public function getStorage(): ?Storage
	{
		return $this->storage;
	}

	public function setStorage(Storage $storage): static
	{
		if ($storage->getServer() !== $this)
		{
			$storage->setServer($this);
		}

		$this->storage = $storage;

		return $this;
	}
}