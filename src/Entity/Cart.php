<?php
// src/Entity/Cart.php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'carts')]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, orphanRemoval: true)]
    private Collection $cartItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): self
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems[] = $cartItem;
            $cartItem->setCart($this);
        }

        return $this;
    }
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isValidate = false;
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPaid = false;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private \DateTimeInterface $createdAt;
    public function getIsValidate(): bool
    {
        return $this->isValidate;
    }
    public function setIsValidate(bool $isValidate): self
    {
        $this->isValidate = $isValidate;
        $this->createdAt = new \DateTime();
        return $this;
    }
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    public function getIsPaid(): bool
    {
        return $this->isPaid;
    }
    public function setIsPaid(bool $isPaid): self
    {
        $this->isPaid = $isPaid;
        return $this;
    }
    public function getTotal()
    {
        $total = 0;
        foreach ($this->cartItems as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }
        return $total;
    }
    // public function removeCartItem(CartItem $cartItem): self
    // {
    //     if ($this->cartItems->removeElement($cartItem)) {

    //         if ($cartItem->getCart() === $this) {
    //             $cartItem->setCart($this);
    //         }
    //     }

    //     return $this;
    // }
}
