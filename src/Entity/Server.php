<?php

//
// Entité pour les serveurs distants.
//
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ServerRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
class Server
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "servers")]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $client = null;

	#[ORM\Column(length: 15)]
	#[Assert\Ip]
	#[Assert\Length(min: 5, max: 15)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $address = null;

	#[ORM\Column(length: 5)]
	#[Assert\Length(min: 1, max: 5)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private ?string $port = null;

	#[ORM\Column(length: 255, nullable: true)]
	#[Assert\Length(max: 255)]
	private ?string $password = null;

	#[ORM\Column(nullable: true)]
	private ?int $game = null;

	#[ORM\OneToMany(mappedBy: "server", targetEntity: Task::class, orphanRemoval: true)]
	private Collection $tasks;

	#[ORM\OneToMany(mappedBy: "server", targetEntity: Event::class, orphanRemoval: true)]
	private Collection $events;

	public function __construct()
	{
		$this->tasks = new ArrayCollection();
		$this->events = new ArrayCollection();
	}

	public const ACTION_SHUTDOWN = "shutdown";
	public const ACTION_SHUTDOWN_FORCE = "force";
	public const ACTION_RESTART = "restart";
	public const ACTION_UPDATE = "update";
	public const ACTION_SERVICE = "service";

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getClient(): ?User
	{
		return $this->client;
	}

	public function setClient(?User $client): self
	{
		$this->client = $client;

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

	public function getPort(): ?string
	{
		return $this->port;
	}

	public function setPort(?string $port): self
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
}