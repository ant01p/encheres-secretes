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
        $items = $itemRepository->findAll();
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

    #[Route('/admin/item/{id}/toggle', name: 'app_admin_item_toggle')]
    public function toggle(Item $item, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // si unpublished -> publier
        if ($item->getStatus() === Item::STATUS_UNPUBLISHED) {
            $item->setStatus(Item::STATUS_PUBLISHED);
        }

        // si published
        elseif ($item->getStatus() === Item::STATUS_PUBLISHED) {

            // et s'il y a des offres -> clotuere et choisir gagnant
            if (count($item->getOffers()) > 0) {

                $bestOffer = null;

                foreach ($item->getOffers() as $offer) {
                    if ($bestOffer === null || $offer->getAmount() > $bestOffer->getAmount()) {
                        $bestOffer = $offer;
                    }
                }

                $item->setStatus(Item::STATUS_CLOSED);
                $item->setWinner($bestOffer->getUser());
                $item->setFinalPrice($bestOffer->getAmount());

            } else {
                // sinon repasser en unpublished
                $item->setStatus(Item::STATUS_UNPUBLISHED);
            }
        }

        $em->flush();

        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }

    #[Route('/published', name: 'app_item_published')]
    public function published(ItemRepository $itemRepository, CategoryRepository $categoryRepository): Response
    {
        $items = $itemRepository->findBy([
            'status' => 'published'
        ]);

        $categories = $categoryRepository->findAll();

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/item/new', name: 'app_admin_item_new')]
    public function new(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {

            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $price = $request->request->get('startingPrice');
            $categoryIds = $request->request->all('categories');

            // vérif
            if (!$title || !$description || !$price) {
                $this->addFlash('error', 'Tous les champs sont obligatoires');
                return $this->redirectToRoute('app_admin_item_new');
            }

            $item = new Item();
            $item->setTitle($title);
            $item->setDescription($description);
            $item->setStartingPrice((int)$price);
            $item->setStatus('unpublished');

            foreach ($categoryIds as $categoryId) {
            $category = $categoryRepository->find($categoryId);

            if ($category) {
                $item->addCategory($category);
            }
        }
            $em->persist($item);
            $em->flush();

            return $this->redirectToRoute('app_item');
        }

        return $this->render('item/item_form.html.twig', [
            'title' => 'Ajouter un objet',
            'item' => null,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/admin/item/{id}/edit', name: 'app_admin_item_edit')]
    public function edit(Item $item, Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $item->setTitle($request->request->get('title'));
            $item->setDescription($request->request->get('description'));
            $item->setStartingPrice((int) $request->request->get('startingPrice'));

            foreach ($item->getCategories() as $category) {
            $item->removeCategory($category);
        }

            $categoryIds = $request->request->all('categories');

            foreach ($categoryIds as $categoryId) {
                $category = $categoryRepository->find($categoryId);

                if ($category) {
                    $item->addCategory($category);
                }
            }

                $em->flush();

                return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
            }

        return $this->render('item/item_form.html.twig', [
            'title' => 'Modifier un objet',
            'item' => $item,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/admin/item/{id}/delete', name: 'app_admin_item_delete')]
    public function delete(Item $item, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($item);
        $em->flush();

        return $this->redirectToRoute('app_item');
    }
}
