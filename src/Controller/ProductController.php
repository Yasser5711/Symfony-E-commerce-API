<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Product;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'product_list', methods: ['GET'])]
    public function list(ManagerRegistry $doctrine): JsonResponse
    {
        $repository = $doctrine->getRepository(Product::class);
        $products = $repository->findAll();

        $data = array_map(function ($product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'photo' => $product->getPhoto(),
                'price' => $product->getPrice(),
            ];
        }, $products);

        return $this->json($data);
    }
    #[Route('/api/products/{id}', name: 'product_show', methods: ['GET'])]
    public function show(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $product = $doctrine->getRepository(Product::class)->find($id);
        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'photo' => $product->getPhoto(),
            'price' => $product->getPrice(),
        ]);
    }
    #[Route('/api/products', name: 'product_add', methods: ['POST'])]
    public function add(Request $request, ManagerRegistry $doctrine, AuthorizationCheckerInterface $authChecker): JsonResponse
    {
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $product = new Product();
        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? '');
        $product->setPhoto($data['photo'] ?? '');
        $product->setPrice($data['price'] ?? 0);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Product added successfully.',
            'product' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'photo' => $product->getPhoto(),
                'price' => $product->getPrice(),
            ]
        ], Response::HTTP_CREATED);
    }
    #[Route('/api/products/{id}', name: 'product_update', methods: ['PUT'])]
    public function update(Request $request, ManagerRegistry $doctrine, AuthorizationCheckerInterface $authChecker, int $id): JsonResponse
    {
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        $product->setName($data['name'] ?? $product->getName());
        $product->setDescription($data['description'] ?? $product->getDescription());
        $product->setPhoto($data['photo'] ?? $product->getPhoto());
        $product->setPrice($data['price'] ?? $product->getPrice());

        $entityManager->flush();

        return $this->json([
            'message' => 'Product updated successfully.',
            'product' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'photo' => $product->getPhoto(),
                'price' => $product->getPrice(),
            ]
        ]);
    }
    #[Route('/api/products/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function delete(ManagerRegistry $doctrine, AuthorizationCheckerInterface $authChecker, int $id): JsonResponse
    {
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['msg' => 'Product deleted successfully.']);
    }
}
