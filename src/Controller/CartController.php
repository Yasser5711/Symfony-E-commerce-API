<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Product;
use App\Entity\Cart;
use App\Entity\CartItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    #[Route('/api/carts', name: 'add_product_to_cart', methods: ['POST'])]
    public function addProductToCart(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $requestData = json_decode($request->getContent(), true);
        $productId = $requestData['productId'] ?? null;
        $quantity = $requestData['quantity'] ?? 1;
        if (!is_numeric($quantity) || $quantity < 1) {
            return $this->json(['error' => 'Quantity must be a positive integer'], Response::HTTP_BAD_REQUEST);
        }
        if (!$productId) {
            return $this->json(['error' => 'Product ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $product = $doctrine->getRepository(Product::class)->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $cart = $user->getCart();
        $entityManager = $doctrine->getManager();
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
            $user->setCart($cart);
        }

        $existingCartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingCartItem = $item;
                break;
            }
        }

        if ($existingCartItem) {
            $existingCartItem->setQuantity($existingCartItem->getQuantity() + $quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setCart($cart);
            $entityManager->persist($cartItem);
        }
        $entityManager->flush();

        return $this->json([
            'message' => 'Product added to cart successfully',
            'cartId' => $cart->getId(),
            // 'itemsCount' => count($cart->getCartItems()),
            // 'products' => $cart->getCartItems()->map(function (CartItem $item) {
            //     return [
            //         'id' => $item->getProduct()->getId(),
            //         'name' => $item->getProduct()->getName(),
            //         'quantity' => $item->getQuantity(),
            //         'price' => $item->getProduct()->getPrice(),
            //         'description' => $item->getProduct()->getDescription(),
            //     ];
            // })->toArray(),
        ], Response::HTTP_CREATED);
    }
    #[Route('/api/carts', name: 'cart_list', methods: ['GET'])]
    public function list(ManagerRegistry $doctrine): JsonResponse
    {
        $cart = $this->getUser()->getCart();
        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }
        $data = [
            'id' => $cart->getId(),
            'itemsCount' => count($cart->getCartItems()),
            'products' => $cart->getCartItems()->map(function (CartItem $item) {
                return [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getProduct()->getPrice(),
                    'description' => $item->getProduct()->getDescription(),
                ];
            })->toArray(),
        ];

        return $this->json($data);
    }
    #[Route('/api/carts/{id}', name: 'cart_remove_product', methods: ['DELETE'])]
    public function removeProduct(Request $request, ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $cart = $this->getUser()->getCart();
        $productId = $id ?? null;
        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$productId) {
            return $this->json(['error' => 'Product ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $doctrine->getManager();
        $cartItem = $entityManager->getRepository(CartItem::class)->findOneBy([
            'cart' => $cart,
            'product' => $productId,
        ]);

        if (!$cartItem) {
            return $this->json(['error' => 'Product not found in cart'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($cartItem);
        $entityManager->flush();

        return $this->json(['msg' => 'Product removed from cart successfully']);
    }
    #[Route('/api/carts/validate', name: 'cart_validate', methods: ['PATCH'])]
    public function validateCart(ManagerRegistry $doctrine): JsonResponse
    {
        $cart = $this->getUser()->getCart();
        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }
        if (count($cart->getCartItems()) === 0) {
            return $this->json(['error' => 'Cart is empty'], Response::HTTP_BAD_REQUEST);
        }
        $cart->setIsValidate(true);
        $doctrine->getManager()->flush();

        return $this->json(['msg' => 'Cart validated successfully']);
    }
    #[Route('/api/carts/pay/{id}', name: 'cart_pay', methods: ['PATCH'])]
    public function payCart(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $user = $this->getUser();
        $cart = $doctrine->getRepository(Cart::class)->findOneBy(['user' => $user, 'id' => $id, 'isValidate' => true]);
        if (!$cart) {
            return $this->json(['error' => 'Cart not found or Cart is not validated'], Response::HTTP_NOT_FOUND);
        }
        if (count($cart->getCartItems()) === 0) {
            return $this->json(['error' => 'Cart is empty'], Response::HTTP_BAD_REQUEST);
        }
        if (!$cart->getIsValidate()) {
            return $this->json(['error' => 'Cart is not validated'], Response::HTTP_BAD_REQUEST);
        }
        $cart->setIsPaid(true);
        $doctrine->getManager()->flush();

        return $this->json(['msg' => 'Cart paid successfully']);
    }
    #[Route('/api/orders', name: 'order_list', methods: ['GET'])]
    public function get_all_orders(ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $carts = $doctrine->getRepository(Cart::class)->findBy(['user' => $user, 'isValidate' => true, 'isPaid' => false]);
        if (!$carts) {
            return $this->json(['error' => 'No orders found'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($carts as $cart) {
            $data[] = [
                'id' => $cart->getId(),
                'itemsCount' => count($cart->getCartItems()),
                'createdAt' => $cart->getCreatedAt()->format('Y-m-d H:i:s'),
                'totalPrice' => $cart->getTotal(),
                'products' => $cart->getCartItems()->map(function (CartItem $item) {
                    return [
                        'id' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'quantity' => $item->getQuantity(),
                        'price' => $item->getProduct()->getPrice(),
                        'description' => $item->getProduct()->getDescription(),
                    ];
                })->toArray(),
                'isValidate' => $cart->getIsValidate(),
                'isPaid' => $cart->getIsPaid(),
            ];
        }

        return $this->json($data);
    }
    #[Route('/api/orders/{id}', name: 'order', methods: ['GET'])]
    public function get_id_order(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $doctrine->getRepository(Cart::class)->findOneBy(['user' => $user, 'id' => $id, 'isValidate' => true]);
        if (!$cart) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $cart->getId(),
            'itemsCount' => count($cart->getCartItems()),
            'createdAt' => $cart->getCreatedAt()->format('Y-m-d H:i:s'),
            'totalPrice' => $cart->getTotal(),
            'products' => $cart->getCartItems()->map(function (CartItem $item) {
                return [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getProduct()->getPrice(),
                    'description' => $item->getProduct()->getDescription(),
                ];
            })->toArray(),
            'isValidate' => $cart->getIsValidate(),
            'isPaid' => $cart->getIsPaid(),
        ];

        return $this->json($data);
    }
    #[Route('/api/orders/{id}', name: 'cancel_order', methods: ['DELETE'])]
    public function cancel_order(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $cart = $doctrine->getRepository(Cart::class)->findOneBy(['user' => $user, 'id' => $id, 'isValidate' => true, 'isPaid' => false]);
        if (!$cart) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }
        //$cart->setIsPaid(false);
        //$cart->setCreatedAt();
        //$cart->setIsValidate(false);

        $doctrine->getManager()->remove($cart);
        $doctrine->getManager()->flush();

        return $this->json(['msg' => 'Order canceled successfully']);
    }
    #[Route('/api/history', name: 'history', methods: ['GET'])]
    public function get_history_orders(ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $carts = $doctrine->getRepository(Cart::class)->findBy(['user' => $user, 'isPaid' => true, 'isValidate' => true]);
        if (!$carts) {
            return $this->json(['error' => 'No history found'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($carts as $cart) {
            $data[] = [
                'id' => $cart->getId(),
                'itemsCount' => count($cart->getCartItems()),
                'createdAt' => $cart->getCreatedAt()->format('Y-m-d H:i:s'),
                'totalPrice' => $cart->getTotal(),
                'products' => $cart->getCartItems()->map(function (CartItem $item) {
                    return [
                        'id' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'quantity' => $item->getQuantity(),
                        'price' => $item->getProduct()->getPrice(),
                        'description' => $item->getProduct()->getDescription(),
                    ];
                })->toArray(),
                'isValidate' => $cart->getIsValidate(),
                'isPaid' => $cart->getIsPaid(),
            ];
        }

        return $this->json($data);
    }
}
