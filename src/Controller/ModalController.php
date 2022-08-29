<?php

namespace App\Controller;

use App\Entity\BookOld;
use App\Form\BookOldType;
use App\Repository\BookOldRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/examples/modal", name="modal_")
 */
class ModalController extends AbstractController
{
    const PREFIX = 'modal_';

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('modal/index.html.twig', [
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function new(Request $request, BookOldRepository $bookOldRepository): Response
    {
        $bookOld = new BookOld();
        $form = $this->createForm(BookOldType::class, $bookOld);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookOld->setUpdatedAt($bookOld->getCreatedAt());
            $bookOldRepository->add($bookOld, true);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('book_old/new.html.twig', [
            'book_old' => $bookOld,
            'form' => $form,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(BookOld $bookOld): Response
    {
        return $this->render('book_old/show.html.twig', [
            'book_old' => $bookOld,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, BookOld $bookOld, BookOldRepository $bookOldRepository): Response
    {
        $form = $this->createForm(BookOldType::class, $bookOld);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookOld->setUpdatedAt(new \DateTime());
            $bookOldRepository->add($bookOld, true);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('book_old/edit.html.twig', [
            'book_old' => $bookOld,
            'form' => $form,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, BookOld $bookOld, BookOldRepository $bookOldRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bookOld->getId(), $request->request->get('_token'))) {
            $bookOldRepository->remove($bookOld, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
    }
}
