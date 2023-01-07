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
        $orbisKeys = [
            'A' => [
                'Nombre' => 'Nombre',
                'Tipo' => 'Tipo',
                'Pais' => 'País',
                'Direct' => 'Direct %',
                'Total' => 'Total %'
            ],
            'P' => [
                'Nombre' => '', // no hay etiqueta para participadas
                'Tipo' => 'Tipo',
                'Pais' => 'País',
                'Direct' => 'Direct %',
                'Total' => 'Total %'
            ],
            'class' => 'ORBIS'
        ];
        $sabiKeys = [
            'A' => [
                'Nombre' => 'Nombre del accionista',
                'Tipo' => 'Tipo',
                'Pais' => 'País',
                'Direct' => 'Directo (%)',
                'Total' => 'Total (%)'
            ],
            'P' => [
                'Nombre' => 'Nombre participada',
                'Tipo' => 'Tipo',
                'Pais' => 'País',
                'Direct' => 'Directo (%)',
                'Total' => 'Total (%)'
            ],
            'class' => 'SABI'
        ];
        $inputFileNames = [
            '1953 GRUP SOLER CONSTRUCTORA SL.xls',
            'ACCENTURE SLU.xlsx',
            'AMBU A@@SLASH@@S.xlsx',
            'BAIN & COMPANY IBERICA INC SEE.xlsx',
            'BOIRON.xlsx',
            'COFANO FARMACEUTICA NOROESTE SC GALLEGA.xls',
        ];
        $companies = [];
        $NombreSearch = [',', '.'];
        $NombreReplace = [' ', ''];
        foreach ($inputFileNames as $name) {
            //$inputFileName = __DIR__ . '/../../../sanitypower/migrations/ACCENTURE SLU.xlsx';
            $inputFileName = __DIR__ . '/../../../sanitypower/migrations/' . $name;
            $e = explode('/', $inputFileName);
            $_empresa = $e[count($e)-1];
            //empresa = file.replace("@@SLASH@@", "/").replace("@@QUOTE@@","’")
            $_empresa = substr($_empresa, 0, strpos($_empresa, '.'));
            $search = ['@@SLASH@@', '@@QUOTE@@'];
            $replace = ['/', '’'];
            $empresa = str_replace($search, $replace, $_empresa);
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
            $reader->setReadDataOnly(true)
            ->setReadEmptyCells(false);
            $spreadsheet = $reader->load($inputFileName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(false, true, true, true);
            $result = [];
            $rowIndex = 1;
            $store = false;
            //dump($sheetData);
            // Hacemos limpieza y marcamos las secciones 'A' y 'P'
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
            //dump($result);

            // INICIO DE ACCIONISTAS
            $shareholders = [];
            $rowIndex = $result['A'];
            $colTitles = [];
            $keys = $orbisKeys;
            $limit = $result['P'];
            while (count($colTitles)<5 && $rowIndex <$limit) {
                if (!empty($result[$rowIndex])) {
                    foreach ($result[$rowIndex] as $key => $value) {
                        if ($value==$orbisKeys['A']['Nombre'] || ($value==$sabiKeys['A']['Nombre'])) {
                            $colTitles['Nombre'] = $key;
                            if ($value==$sabiKeys['A']['Nombre']) {
                                $keys = $sabiKeys;
                            }
                        }
                        if ($value==$keys['A']['Pais']) {
                            $colTitles['Pais'] = $key;
                        }
                        if ($value==$keys['A']['Tipo']) {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($value==$keys['A']['Direct']) {
                            $colTitles['Direct'] = $key;
                        }
                        if ($value==$keys['A']['Total']) {
                            $colTitles['Total'] = $key;
                        }
                    }
                }
                $rowIndex++;
            }
            if (count($colTitles)) {
                $colTitles['class'] = $keys['class'];
                $colTitles['empresa'] = $empresa;
            } else {
                $keys = $orbisKeys; // Inicializamos por no haber accionistas
            }
            $class = $keys['class'];
    dump($colTitles);
            $index = 0;
            while ($rowIndex<$limit) {
                $line = [];
                $viastr = 'via its funds';
                $via= '';
                if (!empty($result[$rowIndex][$colTitles['Nombre']])) {
                    $Nombre = $result[$rowIndex][$colTitles['Nombre']];
                    $funds = stripos($Nombre, $viastr);
                    if ($funds) {
                        $via = $viastr;
                        $Nombre = substr($Nombre, 0, $funds);
                    }
                }
                if ($class=='ORBIS') {
                    if (!empty($result[$rowIndex][$colTitles['Nombre']])) {
                        if ($result[$rowIndex][$colTitles['Nombre']]=='Leyenda') {
                            $rowIndex = $limit;
                        } else {
                            $line = [
                                'Nombre' => $Nombre,
                                'via' => $via,
                                'Pais' => $result[$rowIndex][$colTitles['Pais']],
                                'Tipo' => $result[$rowIndex][$colTitles['Tipo']],
                                'Direct' => $result[$rowIndex][$colTitles['Direct']],
                                'Total' => $result[$rowIndex][$colTitles['Total']],
                                'row' => $rowIndex
                            ];
                        }
                    }
                } else {
                    if (!empty($result[$rowIndex]['A'])) {
                        $i = $result[$rowIndex]['A'];
                        $i = substr($i, 0, strpos($i, '.'));
                        //dump($i);
                        if (is_numeric($i) && ($i==($index+1))) {
                            if (!empty($result[$rowIndex][$colTitles['Nombre']])) {
                                $Nombre = $result[$rowIndex][$colTitles['Nombre']];
                            } else {
                                foreach ($result[$rowIndex] as $key => $value) {
                                    //dump($key, $value);
                                    if ($key>'A' && $value != $result[$rowIndex][$colTitles['Pais']]) {
                                        $Nombre = $value;
                                        break;
                                    }
                                }
                            }
                            $funds = stripos($Nombre, $viastr);
                            if ($funds) {
                                $via = $viastr;
                                $Nombre = substr($Nombre, 0, $funds);
                            }
                            $line = [
                                'index' => ++$index,
                                'Nombre' => $Nombre,
                                'via' => $via,
                                'Pais' => $result[$rowIndex][$colTitles['Pais']],
                                'Tipo' => $result[$rowIndex][$colTitles['Tipo']],
                                'Direct' => str_replace(',', '.', $result[$rowIndex][$colTitles['Direct']]),
                                'Total' => str_replace(',', '.', $result[$rowIndex][$colTitles['Total']]),
                                'row' => $rowIndex
                            ];
                        }
                    }
                }
                if (count($line)) {
                    if (!empty($Nombre)) {
                        $line['Nombre'] = str_replace($NombreSearch, $NombreReplace, $Nombre);
                    }
                    $shareholders[] = $line;
                }
                $rowIndex++;
            }

            // FIN ACCIONISTAS
    dump($shareholders);

            $colTitles = $subsidiaries = [];
            $colTitles = [
                'index' => 'A'
            ];
            $limit = count($sheetData);
            $rowIndex = $result['P'];
            while (count($colTitles)<5 && $rowIndex <$limit) {
                if (!empty($result[$rowIndex])) {
                    foreach ($result[$rowIndex] as $key => $value) {
                        if ($value==$sabiKeys['P']['Nombre']) {
                            $colTitles['Nombre'] = $key;
                            $keys = $sabiKeys;
                        }
                        if ($value==$keys['P']['Pais']) {
                            $colTitles['Pais'] = $key;
                        }
                        if ($value==$keys['P']['Tipo']) {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($value==$keys['P']['Direct']) {
                            $colTitles['Direct'] = $key;
                        }
                        if ($value==$keys['P']['Total']) {
                            $colTitles['Total'] = $key;
                            $colTitles['row'] = $rowIndex;
                        }
                    }
                }
                $rowIndex++;
            }
            $colTitles['class'] = $keys['class'];
            $colTitles['empresa'] = $empresa;
dump($colTitles);

            $index = 0; // Indice de participadas
            // El count es por si no hay participadas
            while ($rowIndex<$limit && count($colTitles)) {
                if (empty($colTitles['Nombre'])) {
                    // ORBIS, no tenemos la columna del nombre
                    /*foreach ($result[$rowIndex] as $key => $value) {
                        dump($key, $value);
                        if ($key>'A' && $value != $result[$rowIndex][$colTitles['Pais']]) {
                            $colTitles['Nombre'] = $value;
                            //$Nombre = $value;
                            //break;
                        } else {
                            break;
                        }
                    }*/
                    $x = 0;
                    $xfound = false;
                    foreach ($result[$rowIndex] as $key => $value) {
                        $x++;
                        if ($x>1 && (strlen($value)>2) && !$xfound) {
                            $colTitles['Nombre'] = $key;
                            //dump("key: $key, value: $value, Nombre: " . $colTitles['Nombre']);
                            $xfound = true;
                            break;
                        }
                    }
                    dump($colTitles);
                }
                if ((!empty($results[$rowIndex]['A'])) && ($result[$rowIndex]['A']=='Leyenda')) {
                    $rowIndex = $limit;
                } else {
                }
                //dump($colTitles);
                if (!empty($result[$rowIndex]['A'])) {
                    $i = $result[$rowIndex]['A'];
                    if (substr($i, 0, strpos($i, '.'))) {
                        $i = substr($i, 0, strpos($i, '.'));
                    } else {
                        $i = rtrim($i);
                    }
                    //dump("linea: $rowIndex, index: $i");
                    if (is_numeric($i) && ($i==($index+1))) {
                        if (!empty($result[$rowIndex][$colTitles['Nombre']])) {
                        }
                        if (empty($colTitles['Tipo'])) {
                            $Tipo = 'C';
                        } else {
                            $Tipo = $result[$rowIndex][$colTitles['Tipo']];
                        }
                        $subsidiaries[] = [
                            'index' => ++$index,
                            'Nombre' => str_replace($NombreSearch, $NombreReplace, $result[$rowIndex][$colTitles['Nombre']]),
                            'Pais' => $result[$rowIndex][$colTitles['Pais']]??'--',
                            'Tipo' => $Tipo,
                            'Direct' => str_replace(',', '.', $result[$rowIndex][$colTitles['Direct']])??0,
                            'Total' => str_replace(',', '.', $result[$rowIndex][$colTitles['Total']])??0,
                            'row' => $rowIndex,
                        ];
                    }
                }
                $rowIndex++;
            }
            $companies[] = [
                'name' => $empresa,
                'class' => $class,
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
