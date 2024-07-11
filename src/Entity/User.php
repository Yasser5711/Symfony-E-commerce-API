<?php

namespace App\Entity;

use AllowDynamicProperties;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[AllowDynamicProperties] #[ORM\Entity]
#[ORM\Table(name: "app_user")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{


    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[A-Za-z ]*$/', message: "Name must contain only letters and spaces.")]
    private ?string $firstname;



    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[A-Za-z ]*$/', message: "Name must contain only letters and spaces.")]
    private ?string $lastname;

    #[ORM\Column(type: 'string', unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    // Getters and setters for email and password
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
    public function getFirstname(): string
    {
        return $this->firstname;
    }
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }
    public function getLastname(): string
    {
        return $this->lastname;
    }
    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // #[ORM\Column(type: "string", length: 100)]
    // #[Assert\NotBlank]
    // #[Assert\Length(min: 3, max: 100)]
    // #[Assert\Regex(pattern: '/^[A-Z][A-Za-z ]*$/', message: "Name must start with an uppercase letter and contain only letters and spaces.")]
    // private ?string $name;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_COMPANY_ADMIN', 'ROLE_SUPER_ADMIN'])]
    private ?string $role = 'ROLE_USER';



    public function getId(): ?int
    {
        return $this->id;
    }

    // public function getName(): ?string
    // {
    //     return $this->name;
    // }

    // public function setName(string $name): self
    // {
    //     $this->name = $name;

    //     return $this;
    // }
    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @inheritDoc
     */

    // public function getRoles(): array
    // {
    //     return $this->role;
    // }
    public function getRoles(): array
    {
        return [$this->role];
    }

    // Your other methods...
    public function setId(int $id): void
    {

        $this->id = $id;
    }

    private function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    private function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function setRoles(string $role): self
    {
        $this->role = $role;
        return $this;
    }



    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }
    #[ORM\OneToMany(mappedBy: "user", targetEntity: Cart::class, cascade: ["persist", "remove"])]
    private Collection $carts;

    public function __construct()
    {
        $this->carts = new ArrayCollection();
    }

    // Getters and Setters

    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->carts->contains($cart)) {
            $this->carts[] = $cart;
            $cart->setUser($this);
        }
        return $this;
    }

    // public function removeCart(Cart $cart): self
    // {
    //     if ($this->carts->removeElement($cart)) {
    //         // set the owning side to null (unless already changed)
    //         if ($cart->getUser() === $this) {
    //             $cart->setUser(null);
    //         }
    //     }
    //     return $this;
    // }


    public function getCart(): ?Cart
    {
        foreach ($this->carts as $cart) {
            if (!$cart->getIsValidate() && !$cart->getIsPaid()) {
                return $cart;
            }
        }
        return null;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;
        if ($cart !== null) {
            $cart->setUser($this);
        }
        return $this;
    }
    public function getOrders(): Collection
    {
        return $this->carts->filter(
            function (Cart $cart) {
                return $cart->getIsValidate();
            }
        );
    }
}
