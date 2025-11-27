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

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Клиент обязателен")]
    private ?Client $client = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: "Заказ должен содержать хотя бы одно блюдо")]
    private Collection $orderItems;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderFile::class, cascade: ['persist', 'remove'])]
    private Collection $orderFiles;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->orderFiles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrderFile>
     */
    public function getOrderFiles(): Collection
    {
        return $this->orderFiles;
    }

    public function addOrderFile(OrderFile $orderFile): static
    {
        if (!$this->orderFiles->contains($orderFile)) {
            $this->orderFiles->add($orderFile);
            $orderFile->setOrder($this);
        }

        return $this;
    }

    public function removeOrderFile(OrderFile $orderFile): static
    {
        if ($this->orderFiles->removeElement($orderFile)) {
            // set the owning side to null (unless already changed)
            if ($orderFile->getOrder() === $this) {
                $orderFile->setOrder(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function calculateTotal(): void
    {
        $total = '0';
        foreach ($this->orderItems as $item) {
            $total = bcadd($total, bcmul($item->getPrice(), (string)$item->getQuantity(), 2), 2);
        }
        $this->totalAmount = $total;
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
}