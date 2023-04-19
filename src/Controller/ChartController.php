<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * @Route("/examples/chart", name="chart_")
 */
class ChartController extends AbstractController
{
    const PREFIX = 'chart_';

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        $array = [];
        //[
            $array[]=['Year', 'Ventas', 'Gastos'];
            $array[]=['2004', 1000, 400];
            $array[]=['2005', 1170, 460];
            $array[]=['2006', 660, 1120];
            $array[]=['2007', 1030, 540];
        //];
        ;
        $data = new JsonResponse($array, 200, []);
        $data = json_encode($array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $data = $array;
        dump($array, $data);

        return $this->render('chart/index.html.twig', [
            'data' => $data //json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
        ]);
    }
}
