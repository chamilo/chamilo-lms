<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ContactCategory;
use Chamilo\CoreBundle\Form\ContactCategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact/category')]
class ContactCategoryController extends AbstractController
{
    #[Route('/', name: 'chamilo_contact_category_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $contactCategories = $entityManager
            ->getRepository(ContactCategory::class)
            ->findAll()
        ;

        return $this->render('@ChamiloCore/ContactCategory/index.html.twig', [
            'contact_categories' => $contactCategories,
        ]);
    }

    #[Route('/new', name: 'chamilo_contact_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contactCategory = new ContactCategory();
        $form = $this->createForm(ContactCategoryType::class, $contactCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contactCategory);
            $entityManager->flush();

            return $this->redirectToRoute('chamilo_contact_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('@ChamiloCore/ContactCategory/new.html.twig', [
            'contact_category' => $contactCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'chamilo_contact_category_show', methods: ['GET'])]
    public function show(ContactCategory $contactCategory): Response
    {
        return $this->render('@ChamiloCore/ContactCategory/show.html.twig', [
            'contact_category' => $contactCategory,
        ]);
    }

    #[Route('/{id}/edit', name: 'chamilo_contact_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContactCategory $contactCategory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactCategoryType::class, $contactCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('chamilo_contact_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('@ChamiloCore/ContactCategory/edit.html.twig', [
            'contact_category' => $contactCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'chamilo_contact_category_delete', methods: ['POST'])]
    public function delete(Request $request, ContactCategory $contactCategory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contactCategory->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contactCategory);
            $entityManager->flush();
        }

        return $this->redirectToRoute('chamilo_contact_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
