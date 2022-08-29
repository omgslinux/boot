<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/examples/flash", name="flash_")
 */
class FlashController extends AbstractController
{
    const PREFIX = 'flash_';

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        $this->addFlash('info', "info");
        $this->addFlash('success', "success");
        $this->addFlash('warning', "warning");
      return $this->render('flash/index.html.twig', [
        ]);
    }
}
