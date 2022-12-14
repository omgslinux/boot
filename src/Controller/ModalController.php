<?php

namespace App\Controller;

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
}
