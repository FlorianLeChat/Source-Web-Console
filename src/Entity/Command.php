<?php

//
// Entité pour les commandes personnalisées.
//
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommandRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandRepository::class)]
#[ApiResource(
	security: "is_granted(\"ROLE_ADMIN\")",
	operations: [
		new Get(normalizationContext: ["groups" => "command"]),
		new GetCollection(normalizationContext: ["groups" => "commands"])
	]
)]
class Command
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	#[Groups(["commands", "command"])]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: "commands")]
	#[ORM\JoinColumn(nullable: false)]
	#[Groups(["commands", "command"])]
	private ?User $user = null;

	#[ORM\Column(length: 255)]
	#[Assert\Length(min: 1, max: 255)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Groups(["commands", "command"])]
	private ?string $title = null;

	#[ORM\Column(length: 255)]
	#[Assert\Length(min: 1, max: 255)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	#[Groups(["commands", "command"])]
	private ?string $content = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): static
	{
		$this->user = $user;

		return $this;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(string $title): static
	{
		$this->title = $title;

		return $this;
	}

	public function getContent(): ?string
	{
		return $this->content;
	}

	public function setContent(string $content): static
	{
		$this->content = $content;

		return $this;
	}
}