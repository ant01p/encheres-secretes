<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ItemController extends AbstractController
{
    #[Route('/', name: 'app_item')]
    public function index(ItemRepository $itemRepository, CategoryRepository $categoryRepository): Response
    {
        $items = $itemRepository->findItemsByStatus();
        $categories = $categoryRepository->findAll();

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'categories' => $categories,
        ]);
    }

    #[Route('/category/{id}', name: 'app_item_by_category')]
    public function itemsByCategory(Category $category, ItemRepository $itemRepository, CategoryRepository $categoryRepository): Response
    {
        $items = $itemRepository->findItemsByCategory($category);
        $categories = $categoryRepository->findAll();

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'categories' => $categories,
            'currentCategory' => $category,
        ]);
    }

    #[Route('/item/{id}', name: 'app_item_show')]
    public function show(Item $item): Response
    {
        return $this->render('item/show.html.twig', [
            'item' => $item,
        ]);
    }
}
