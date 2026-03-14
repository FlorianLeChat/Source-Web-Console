<?php

//
// Entité pour les commandes personnalisées.
//
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommandRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandRepository::class)]
class Command
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private int $id;

	#[ORM\ManyToOne(inversedBy: "commands")]
	#[ORM\JoinColumn(nullable: false)]
	private User $user;

	#[ORM\Column(length: 255)]
	#[Assert\Length(min: 1, max: 255)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private string $title;

	#[ORM\Column(length: 255)]
	#[Assert\Length(min: 1, max: 255)]
	#[Assert\NotNull]
	#[Assert\NotBlank]
	private string $content;

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