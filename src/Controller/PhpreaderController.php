<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Util\PhpreaderHelper;

#[Route('/phpreader', name: 'app_phpreader_')]
class PhpreaderController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('phpreader/index.html.twig', [
            'controller_name' => 'PhpreaderController',
        ]);
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        // Read rows 1 to 7 and columns A to E only
        if ($row >= $this->minRow && $row <= $this->maxRow) {
            if (in_array($columnAddress, range($this->minCol, $this->maxCol))) {
                return true;
            }
        }

        return false;
    }

    #[Route('/test', name: 'test')]
    public function test()
    {
        $inputFileNames = [
            'ACCENTURE SLU.xlsx',
            'COFANO FARMACEUTICA NOROESTE SC GALLEGA.xls',
        ];
        $companies = [];
        foreach ($inputFileNames as $name) {
            //$inputFileName = __DIR__ . '/../../../sanitypower/migrations/ACCENTURE SLU.xlsx';
            $inputFileName = __DIR__ . '/../../../sanitypower/migrations/' . $name;
            $e = explode('/', $inputFileName);
            $empresa = $e[count($e)-1];
            $empresa = substr($empresa, 0, strpos($empresa, '.'));
            $inputFileType = IOFactory::identify(
                $inputFileName,
                [
                    IOFactory::READER_XLS,
                    IOFactory::READER_XLSX,
                ]
            );
            $reader = IOFactory::createReader($inputFileType);
            $worksheetNames = $reader->listWorksheetNames($inputFileName);
            //$reader->setLoadSheetsOnly($worksheetNames[0]);
            //$helper->log('Loading file ' . /** @scrutinizer ignore-type */ pathinfo($inputFileName, PATHINFO_BASENAME)
            //    . ' using IOFactory to identify the format');
            //$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
            $filter = new PhpreaderHelper();
            //$reader->setReadFilter($filter);
            /**  Advise the Reader that we only want to load cell data  **/
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($inputFileName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $result = [];
            $rowIndex = 1;
            $store = false;
            foreach ($sheetData as $row) {
                $line = [];
                foreach ($row as $col => $value) {
                    if (null!=$value) {
                        $line[$col] = $value;
                        if ($value == 'Accionistas actuales') {
                            if (empty($result['A'])) {
                                $result['A'] = $rowIndex;
                                $store = true;
                            }
                        }
                        if ($value == 'Participadas actuales') {
                            if (empty($result['P'])) {
                                $result['P'] = $rowIndex;
                            }
                        }
                    }
                }
                if (count($line) && $store) {
                    $result[$rowIndex] = $line;
                }
                $rowIndex++;
            }
            $shareholders = [];
            $rowIndex = $result['A'];
            //dump($result);
            $colTitles = [];
            $limit = $result['P'];
            while (count($colTitles)<5 && $rowIndex <$limit) {
                if (!empty($result[$rowIndex])) {
                    foreach ($result[$rowIndex] as $key => $value) {
                        if ($value=='Nombre') {
                            $colTitles['Nombre'] = $key;
                        }
                        if ($value=='País') {
                            $colTitles['Pais'] = $key;
                        }
                        if ($value=='Tipo') {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($value=='Direct %') {
                            $colTitles['Direct'] = $key;
                        }
                        if ($value=='Total %') {
                            $colTitles['Total'] = $key;
                        }
                    }
                }
                $rowIndex++;
            }
            while ($rowIndex<$limit) {
                if (!empty($result[$rowIndex][$colTitles['Nombre']])) {
                    if ($result[$rowIndex][$colTitles['Nombre']]=='Leyenda') {
                        $rowIndex = $limit;
                    } else {
                        $shareholders[] = [
                            'Nombre' => $result[$rowIndex][$colTitles['Nombre']],
                            'Pais' => $result[$rowIndex][$colTitles['Pais']],
                            'Tipo' => $result[$rowIndex][$colTitles['Tipo']],
                            'Direct' => $result[$rowIndex][$colTitles['Direct']],
                            'Total' => $result[$rowIndex][$colTitles['Total']],
                            'row' => $rowIndex
                        ];
                    }
                }
                $rowIndex++;
            }

            // FIN ACCIONISTAS
    //dump($colTitles, $shareholders);

            $colTitles = $subsidiaries = [];
            $colTitles = [
                'index' => 'A'
            ];
            $limit = count($sheetData);
            $rowIndex = $result['P'];
            while (count($colTitles)<5 && $rowIndex <$limit) {
                if (!empty($result[$rowIndex])) {
                    foreach ($result[$rowIndex] as $key => $value) {
                        if ($value=='Nombre') {
                            $colTitles['Nombre'] = $key;
                        }
                        if ($value=='País') {
                            $colTitles['Pais'] = $key;
                        }
                        if ($value=='Tipo') {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($value=='Direct %') {
                            $colTitles['Direct'] = $key;
                        }
                        if ($value=='Total %') {
                            $colTitles['Total'] = $key;
                            $colTitles['row'] = $rowIndex;
                        }
                    }
                }
                $rowIndex++;
            }

            $i = 0; // Indice de participadas
            // El count es por si no hay participadas
            while ($rowIndex<$limit && count($colTitles)) {
                if ($result[$rowIndex]['A']=='Leyenda') {
                    $rowIndex = $limit;
                } else {
                    if (empty($colTitles['Nombre'])) {
                        $x = 0;
                        foreach ($result[$rowIndex] as $key => $value) {
                            $x++;
                            if ($x==2) {
                                $colTitles['Nombre'] = $key;
                            }
                        }
                    }
                    //dump($colTitles);
                    if (!empty($result[$rowIndex][$colTitles['Nombre']])) {
                        $subsidiaries[] = [
                            'Nombre' => $result[$rowIndex][$colTitles['Nombre']],
                            'Pais' => $result[$rowIndex][$colTitles['Pais']]??'--',
                            'Tipo' => $result[$rowIndex][$colTitles['Tipo']]??'C',
                            'Direct' => $result[$rowIndex][$colTitles['Direct']]??0,
                            'Total' => $result[$rowIndex][$colTitles['Total']]??0,
                            'row' => $rowIndex
                        ];
                    }
                }
                $rowIndex++;
            }
            $companies[] = [
                'name' => $empresa,
                'shareholders' => $shareholders,
                'subsidiaries' => $subsidiaries,
            ];
        }

//dump($subsidiaries);
        return $this->render('phpreader/test.html.twig', [
            'controller_name' => 'PhpreaderController',
            'empresas' => $companies,
        ]);
    }
}
