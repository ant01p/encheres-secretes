<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Form\OfferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
    public function show(Item $item, Request $request, OfferRepository $offerRepository, EntityManagerInterface $entityManager): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->getUser()) {
                return $this->redirectToRoute('app_login');
            }

            if ($offer->getAmount() <= $item->getStartingPrice()) {
                $this->addFlash('error', 'L\'offre doit être supérieure au prix de départ');
                return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
            }

            $offer->setUser($this->getUser());
            $offer->setItem($item);

            $entityManager->persist($offer);
            $entityManager->flush();

            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }

        return $this->render('item/show.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
            'offers' => $item->getOffers(),
        ]);
    }
}
