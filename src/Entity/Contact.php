<?php

namespace App\Entity;

//
// Entité pour les messages de contact.
//
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\ContactRepository;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $timestamp = null;

	#[ORM\Column(length: 320)]
	private ?string $email = null;

	#[ORM\Column(length: 255)]
	private ?string $subject = null;

	#[ORM\Column(length: 5000)]
	private ?string $message = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getTimestamp(): ?\DateTimeInterface
	{
		return $this->timestamp;
	}

	public function setTimestamp(\DateTimeInterface $timestamp): self
	{
		$this->timestamp = $timestamp;

		return $this;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail(?string $email): self
	{
		$this->email = $email;

		return $this;
	}

	public function getSubject(): ?string
	{
		return $this->subject;
	}

	public function setSubject(string $subject): self
	{
		$this->subject = $subject;

		return $this;
	}

	public function getMessage(): ?string
	{
		return $this->message;
	}

	public function setMessage(string $message): self
	{
		$this->message = $message;

		return $this;
	}
}