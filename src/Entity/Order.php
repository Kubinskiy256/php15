<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    /**
     * @var Collection<int, Dish>
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'Нужен хотя бы один заказ',
    )]
    #[ORM\ManyToMany(targetEntity: Dish::class)]
    private Collection $dish;

    #[ORM\ManyToOne]
    private ?Client $client = null;

     #[ORM\OneToMany(targetEntity: OrderFile::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $files;

    public function __construct()
    {
        $this->dish = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    /**
     * @return Collection<int, OrderFile>
     */

    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(OrderFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setOrder($this);
        }

        return $this;
    }

    public function removeFile(OrderFile $file): static
    {
        if ($this->files->removeElement($file)) {
            if ($file->getOrder() === $this) {
                $file->setOrder(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Dish>
     */
    public function getDish(): Collection
    {
        return $this->dish;
    }

    public function addDish(Dish $dish): static
    {
        if (!$this->dish->contains($dish)) {
            $this->dish->add($dish);
        }

        return $this;
    }

    public function removeDish(Dish $dish): static
    {
        $this->dish->removeElement($dish);

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}