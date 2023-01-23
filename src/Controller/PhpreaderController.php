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
    const ORBISKEYS = [
        'A' => [
            'Nombre' => 'Nombre',
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Direct %',
            'Total' => 'Total %'
        ],
        'P' => [
            //'Nombre' => '', // no hay etiqueta para participadas
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Direct %',
            'Total' => 'Total %'
        ],
        'class' => 'ORBIS'
    ];
    const SABIKEYS = [
        'A' => [
            'Nombre' => 'Nombre del accionista',
            'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Directo (%)',
            'Total' => 'Total (%)'
        ],
        'P' => [
            'Nombre' => 'Nombre participada',
            //'Tipo' => 'Tipo',
            'Pais' => 'País',
            'Direct' => 'Directo (%)',
            'Total' => 'Total (%)'
        ],
        'class' => 'SABI'
    ];

    const SECTALL = "0";
    const SECTMANAGERS = "M";
    const SECTHOLDERS = "A";
    const SECTOWNED = "P";
    const LASTCOLUMN = 'EZ';

    private $company;
    private $results=[];
    private $outdir;
    private $prefix; // Prefijo para guardar ficheros de datos
    private $section = "0"; // Section: "0"(all), "M"anagers, "A"ccionistas, "P"articipadas

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

    public function readValue($cell)
    {
        return $this->worksheet->getCell($cell)->getValue();
    }

    #[Route('/test', name: 'test')]
    public function test()
    {
        $inputFileNames = [
            '1953 GRUP SOLER CONSTRUCTORA SL.xls',
            'ACCENTURE SLU.xlsx',
            'AMBU A@@SLASH@@S.xlsx',
            'BAIN & COMPANY IBERICA INC SEE.xlsx',
            'BOIRON.xlsx',
            'CH BOEHRINGER SOHN AG & CO KG.xlsx',
            'COFANO FARMACEUTICA NOROESTE SC GALLEGA.xls',
            'COOPERATIVA FARMACEUTICA DE TENERIFE COFARTE SC.xls',
            'ESLINGA SANITARIA SL.xls',
            'FIATC MUTUA DE SEGUROS Y REASEGUROS A PRIMA FIJA.xlsx',
            'GRUPO PLEXUS TECH SL.xls',
            'GRUPO QUIJILIANA SL.xls',
            'LABIANA HEALTH SL.xls',
            'PRICEWATERHOUSECOOPERS LLP.xlsx',
            'REALIZACION DE CONSULTORIOS MEDICOS SL.xls',
            'RIOLACORBET SL.xls',
            'SAINTRA SL.xls',
            'SANI CONSULT SL.xls',
            'SERVICIOS SOCIO SANITARIOS GENERALES SPAIN SL.xls',
            'SIBEL HEALTHCARE SL.xls',
            'THE LAST VAN SL.xls',
            'THINK IN POSITIVE & SMILE SL.xls',
            'TNR SOCIOS INVERSORES SL.xls',
            'USLRM PARENT COMPANY SL.xls'
        ];

        /*$inputFileNames = [
            '1953 GRUP SOLER CONSTRUCTORA SL.xls',
            'ACCENTURE SLU.xlsx',
            'AMBU A@@SLASH@@S.xlsx',
            //'BARCLAYS PLC.xlsx'
        ];*/
        $companies = [];
        $NombreSearch = [',', '.'];
        $NombreReplace = [' ', ''];
        //set_time_limit(160);
        foreach ($inputFileNames as $name) {
            //$inputFileName = __DIR__ . '/../../../sanitypower/migrations/ACCENTURE SLU.xlsx';
            $inputFileName = __DIR__ . '/../../../sanitypower/migrations/' . $name;
            $e = explode('/', $inputFileName);
            $result = [];
            $_empresa = $e[count($e)-1];
            //empresa = file.replace("@@SLASH@@", "/").replace("@@QUOTE@@","’")
            $result['companyfilename'] = $_empresa;
            $_empresa = substr($_empresa, 0, strpos($_empresa, '.'));
            //$search = ['@@SLASH@@', '@@QUOTE@@'];
            //$replace = ['/', '’'];
            //$empresa = str_replace($search, $replace, $_empresa);
            $empresa = $this->stripCompanyName($_empresa);
            $result['company'] = $empresa;
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
            //$sheetData = $spreadsheet->getActiveSheet()->toArray(false, true, true, true);
            $this->worksheet = $worksheet = $spreadsheet->getActiveSheet();
            $store = false;
            foreach ($worksheet->getRowIterator(100) as $row) {
                $cellIterator = $row->getCellIterator('A', 'F');
                // This loops through all cells, even if a cell value is not set.
                // For 'TRUE', we loop through cells only when their value is set.
                $rowIndex = $row->getRowIndex();
                // If this method is not called, the default value is 'false'.
                $cellIterator->setIterateOnlyExistingCells(true); // This loops through all cells,
                //$line = [];

                foreach ($cellIterator as $cell) {
                    //$line[$cell->getColumn()] = $cell->getValue();
                    if ($cell == 'Directores y gerentes actuales') {
                        if (empty($result['M'])) {
                            $result['M'] = $rowIndex;
                        }
                    }
                    if ($cell == 'Accionistas actuales') {
                        if (empty($result['A'])) {
                            $result['A'] = $rowIndex;
                        }
                    }
                    if ($cell == 'Participadas actuales') {
                        if (empty($result['P'])) {
                            $result['P'] = $rowIndex;
                        }
                    }
                }
            }
            $result['class'] = '';
            $result['total'] = $rowIndex;
            //dump($result);
            $this->results = $result;
            //die();

            $companies[] = [
                'managers' => $this->generateManagers(),
                'shareholders' => $this->generateShareholders(),
                'subsidiaries' => $this->generateSubsidiaries(),
                'results' => $this->results,
            ];
        }

//dump($subsidiaries);
        return $this->render('phpreader/test.html.twig', [
            'controller_name' => 'PhpreaderController',
            'empresas' => $companies,
        ]);
    }

    public function generateManagers($write = false)
    {
        $managers = [];
        if (!empty($this->results['M'])) {
            foreach ($this->worksheet->getRowIterator($this->results['M'], $this->results['A']) as $row) {
                $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
                $cellIterator->setIterateOnlyExistingCells(true);
                $rowIndex = $row->getRowIndex();
                $i = ''; // Inicializamos el indice en cada fila
                //dump("A$rowIndex: " .$this->readValue('A'.$rowIndex));
                $line = [];
                /*if (!empty($this->readValue('G', $rowIndex))) {
                    $datos = $this->readValue('G', $rowIndex);
                }*/
                //if ($class=='ORBIS') {
                $end = false;
                if (!empty($this->readValue('A'. $rowIndex))) {
                    if ($this->readValue('A'. $rowIndex) == 'Leyenda') {
                        $end = true;
                    }
                }
                if (!$end) {
                    if (!empty($this->readValue('G'. $rowIndex))) {
                        $cell = $this->readValue('G'. $rowIndex);
                        $datos = explode("\n", $cell);
                        $cargo = $datos[count($datos)-1];
                        $line = [
                            'datos' => $cell,
                            'Nombre' => $datos[0]??null,
                            'Fecha' => $datos[1]??null,
                            'Cargo' => $datos[2]??null,
                            'row' => $rowIndex
                        ];
                    }
                }
                if (count($line)) {
                    $managers[] = $line;
                }
            }
        }
        //dump($managers);
        return $managers;
    }


    public function generateShareholders($write = false): array
    {
        // INICIO DE ACCIONISTAS
        $shareholders = [];
        $colTitles = [];
        $keys = $orbisKeys = self::ORBISKEYS['A'];
        $sabiKeys = self::SABIKEYS['A'];
        $viastr = 'via its funds';
        $end = false;

        foreach ($this->worksheet->getRowIterator($this->results['A'], $this->results['P']) as $row) {
            $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
            $cellIterator->setIterateOnlyExistingCells(true);
            $rowIndex = $row->getRowIndex();
            $i = ''; // Inicializamos el indice en cada fila
            //dump("A$rowIndex: " .$this->readValue('A'.$rowIndex));
            $line = [];
            $via= '';

            if ($end) {
                break;
            }

            /*if (count($colTitles)<6) {
                foreach ($cellIterator as $cell) {
                    $key = $cell->getColumn();
                    $value = $cell->getValue();
                    if ($value==$orbisKeys['Nombre'] || ($value==$sabiKeys['Nombre'])) {
                        $colTitles['Nombre'] = $key;
                        if ($value==$sabiKeys['Nombre']) {
                            $keys = $sabiKeys;
                        }
                    }
                    if ($value==$keys['Pais']) {
                        $colTitles['Pais'] = $key;
                    }
                    if ($value==$keys['Tipo']) {
                        $colTitles['Tipo'] = $key;
                    }
                    if ($value==$keys['Direct']) {
                        $colTitles['Direct'] = $key;
                    }
                    if ($value==$keys['Total']) {
                        $colTitles['Total'] = $key;
                    }
                }
                $colsfound = false;
            }*/
            //dump("A$rowIndex: " .$this->readValue('A'.$rowIndex));

            if (count($colTitles)<6) {
                //dump($keys);
                foreach ($cellIterator as $cell) {
                    $key = $cell->getColumn();
                    $value = $cell->getValue();
                    if ($value==$orbisKeys['Nombre'] || ($value==$sabiKeys['Nombre'])) {
                        $colTitles['Nombre'] = $key;
                        if ($value==$sabiKeys['Nombre']) {
                            $keys = $sabiKeys;
                            $class  = 'SABI';
                        } else {
                            $class  = 'ORBIS';
                        }
                        if ($class != $this->results['class']) {
                            $this->results['class'] = $class;
                            dump($this->results);
                        }
                    }
                    foreach ($keys as $colkey => $colvalue) {
                        //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                        if ($value==$colvalue) {
                            $colTitles[$colkey] = $key;
                        }
                    }
                }
                $colsfound = false;
            }



            if (!$colsfound) {
                if (count($colTitles)) {
                    $colTitles['empresa'] = $this->company;
                    $colsfound = true;
                    $class = $this->results['class'];

                    //dump($colTitles);
                    $index = 0;
                } else {
                    $keys = $orbisKeys; // Inicializamos por no haber accionistas
                }
            } else {
                if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                    $Nombre = $this->readValue($colTitles['Nombre'].$rowIndex);
                    $funds = stripos($Nombre, $viastr);
                    if ($funds) {
                        $via = $viastr;
                        $Nombre = trim(substr($Nombre, 0, $funds));
                    }
                }
                //dump("class: $class");
                if ($class=='ORBIS') {
                    if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                        if ($this->readValue($colTitles['Nombre'].$rowIndex)=='Leyenda') {
                            //$rowIndex = $limit;
                            $end = true;
                        } else {
                            $line = [
                                'Nombre' => $Nombre,
                                'via' => $via,
                                'Pais' => $this->readValue($colTitles['Pais'].$rowIndex),
                                'Tipo' => $this->readValue($colTitles['Tipo'].$rowIndex),
                                'Direct' => $this->readValue($colTitles['Direct'].$rowIndex),
                                'Total' => $this->readValue($colTitles['Total'].$rowIndex),
                                'row' => $rowIndex,
                            ];
                        }
                    }
                } else {
                    // ACCIONISTAS, SABI
                    if (!empty($this->readValue('A'.$rowIndex))) {
                        $i = $this->readValue('A'.$rowIndex);
                        $i = substr($i, 0, strpos($i, '.'));
                        //dump("i: $i, index: $index");
                        //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                        if (is_numeric($i) && ($i==($index+1))) {
                            if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                                $Nombre = $this->readValue($colTitles['Nombre'].$rowIndex);
                            } else {
                                $Nombre = null;
                                foreach ($cellIterator as $cell) {
                                    // ¡¡¡¡NO SACAR LA LINEA SIGUIENTE, NO FUNCIONA EL BUCLE!!!!
                                    $_pais = $this->readValue($colTitles['Pais'].$rowIndex);
                                    //dump("cell: ".$cell->getValue(). ", value: ".$this->readValue($colTitles['Pais'].$rowIndex));
                                    if ($cell->getColumn()>'A' && trim($cell->getValue()) != $_pais) {
                                    //if ($cell->getColumn()>'A' && ($cell->getColumn()<$colTitles['Pais'])) {
                                        $Nombre = $cell->getValue();
                                    } else {
                                        break;
                                    }
                                }
                                //dump("Nombre: $Nombre");
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
                                'Pais' => $this->readValue($colTitles['Pais'].$rowIndex),
                                'Tipo' => $this->readValue($colTitles['Tipo'].$rowIndex),
                                'Direct' => str_replace(',', '.', $this->readValue($colTitles['Direct'].$rowIndex)),
                                'Total' => str_replace(',', '.', $this->readValue($colTitles['Total'].$rowIndex)),
                                'row' => $rowIndex,
                            ];
                            //dump($line);
                        }
                    }
                }
            }
            if (count($line)) {
                if (!empty($Nombre)) {
                    //$line['Nombre'] = str_replace($NombreSearch, $NombreReplace, $Nombre);
                    $line['Nombre'] = $this->stripCompanyName($Nombre);
                }
                $line['S'] = 'A'; // Seccion: accionistas
                //dump($line);
                $shareholders[] = $line;
            }
        }

        return $shareholders;
        // FIN ACCIONISTAS
    }


    public function generateSubsidiaries($write = false)
    {
        $colTitles = $subsidiaries = [];
        $class = $this->results['class'];
        if ($class == 'ORBIS') {
            $keys = self::ORBISKEYS['P'];
        } else {
            $keys = self::SABIKEYS['P'];
        }
        $foundColTitles = count($keys);
        //dump($foundColTitles);
        $end = false;
        $index = 0; // Indice de participadas
        foreach ($this->worksheet->getRowIterator($this->results['P'], $this->results['total']) as $row) {
            $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
            $cellIterator->setIterateOnlyExistingCells(true);
            $rowIndex = $row->getRowIndex();
            $i = ''; // Inicializamos el indice en cada fila
            //dump("A$rowIndex: " .$worksheet->getCell('A'.$rowIndex)->getValue());
            $line = [];
            $via= '';

            if ($end) {
                break;
            }

            if (count($colTitles)<$foundColTitles) {
                //dump($keys);
                foreach ($cellIterator as $cell) {
                    $key = $cell->getColumn();
                    $value = $cell->getValue();
                    if ($value==self::SABIKEYS['P']['Nombre']) {
                        $colTitles['Nombre'] = $key;
                        if ($class != 'SABI') {
                            $keys = self::SABIKEYS['P'];
                            $foundColTitles = count($keys);
                            dump("(P) Cambio en deteccion de class a SABI para ".$this->results['company']);
                        }
                        $class = $this->results['class'] = 'SABI';
                    }
                    foreach ($keys as $colkey => $colvalue) {
                        if (empty($colTitles[$colkey])) {
                            //dump("key: $key, value: $value, colkey: $colkey, colvalue: $colvalue");
                            if ($value==$colvalue) {
                                $colTitles[$colkey] = $key;
                                //dump("(P, $class): Encontrada clave $colkey en columna $key fila $rowIndex");
                                //dump("Van ".count($colTitles)." claves encontradas de $foundColTitles.");
                            }
                        } else {
                            //dump("Ya se encontró $colkey(".$colTitles[$colkey]."). No se evalua.");
                        }
                    }
                }
                $colsfound = false;
            }
            if (!$colsfound) {
                //dump("row: $rowIndex, No colsfound, count: ".count($colTitles) .", foundColTitles: $foundColTitles");
                if (count($colTitles)>=$foundColTitles) {
                    //$colTitles['class'] = $keys['class'];
                    //$colTitles['empresa'] = $this->company;
                    //$colTitles['row'] = $rowIndex;
                    $colTitles['index']='A';
                    //dump("Halladas todas las columnas: rowIndex($rowIndex), colTitles:", $colTitles);

                    $colsfound = true;
                }
            }
            // El count es por si no hay participadas
            //if (count($colTitles)>$foundColTitles) {
            if ($colsfound) {
                if ((!empty($this->readValue('A'.$rowIndex))) && ($this->readValue('A'.$rowIndex)=='Leyenda')) {
                    $end = true;
                    break;
                }
                if (!empty($this->readValue('A'.$rowIndex))) {
                    $i = trim($this->readValue('A'.$rowIndex));
                    if (substr($i, 0, strpos($i, '.'))) {
                        // SABI
                        $i = substr($i, 0, strpos($i, '.'));
                    } else {
                        // ORBIS
                        $i = rtrim($i);
                    }
                    //dump("S: P, class: $class, row: $rowIndex, i: $i, index: $index");
                    if (is_numeric($i) && ($i==($index+1))) {
                        //dump("i; $i, index: $index");
                        if (empty($colTitles['Nombre'])) {
                            // ORBIS, no tenemos la columna del nombre
                            $xfound = false;
                            foreach ($cellIterator as $cell) {
                                $key = $cell->getColumn();
                                $value= $cell->getValue();
                                //dump("row: $rowIndex, key: $key, value: $value, xfound: $xfound");
                                if (($key > 'A') && ($key<$keys['Pais']) && (strlen($value)>3) && (!$xfound)) {
                                    $xfound = true;
                                    $colTitles['Nombre'] = $key;
                                    dump($colTitles);
                                }
                            }
                        }
                        if (!empty($colTitles['Nombre'])) {
                            //dump("row: $rowIndex, key: $key, value: $value, colTitles:", $colTitles);
                            if (!empty($this->readValue($colTitles['Nombre'].$rowIndex))) {
                                $Nombre = $this->readValue($colTitles['Nombre'].$rowIndex);
                            }
                            if (empty($colTitles['Tipo'])) {
                                $Tipo = 'C';
                            } else {
                                $Tipo = $this->readValue($colTitles['Tipo'].$rowIndex);
                            }
                        }
                        $line = [
                            'index' => ++$index,
                            //'Nombre' => $this->stripCompanyName($this->readValue($colTitles['Nombre'].$rowIndex)),
                            'Nombre' => $this->stripCompanyName($Nombre),
                            'Pais' => $this->readValue($colTitles['Pais'].$rowIndex)??'--',
                            'Tipo' => $Tipo,
                            'Direct' => str_replace(',', '.', $this->readValue($colTitles['Direct'].$rowIndex))??0,
                            'Total' => str_replace(',', '.', $this->readValue($colTitles['Total'].$rowIndex))??0,
                            'row' => $rowIndex,
                            'class' => $this->results['class'],
                            'S' => 'P',
                        ];
                        //dump($line);

                        $subsidiaries[] = $line;
                    }
                }
            }
        }

        return $subsidiaries;
    }


    private function stripCompanyName($company): string
    {
        $search = ['@@SLASH@@', '@@QUOTE@@', ',', '.'];
        $replace = ['/', '’', ' ', ''];
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = str_replace($search, $replace, strtoupper($company));

        return $empresa;
    }

    public function testOLD()
    {
        $inputFileNames = [
            '1953 GRUP SOLER CONSTRUCTORA SL.xls',
            'ACCENTURE SLU.xlsx',
            'AMBU A@@SLASH@@S.xlsx',
            'BAIN & COMPANY IBERICA INC SEE.xlsx',
            'BOIRON.xlsx',
            'CH BOEHRINGER SOHN AG & CO KG.xlsx',
            'COFANO FARMACEUTICA NOROESTE SC GALLEGA.xls',
            'COOPERATIVA FARMACEUTICA DE TENERIFE COFARTE SC.xls',
            'ESLINGA SANITARIA SL.xls',
            'FIATC MUTUA DE SEGUROS Y REASEGUROS A PRIMA FIJA.xlsx',
            'GRUPO PLEXUS TECH SL.xls',
            'GRUPO QUIJILIANA SL.xls',
            'LABIANA HEALTH SL.xls',
            'PRICEWATERHOUSECOOPERS LLP.xlsx',
            'REALIZACION DE CONSULTORIOS MEDICOS SL.xls',
            'RIOLACORBET SL.xls',
            'SAINTRA SL.xls',
            'SANI CONSULT SL.xls',
            'SERVICIOS SOCIO SANITARIOS GENERALES SPAIN SL.xls',
            'SIBEL HEALTHCARE SL.xls',
            'THE LAST VAN SL.xls',
            'THINK IN POSITIVE & SMILE SL.xls',
            'TNR SOCIOS INVERSORES SL.xls',
            'USLRM PARENT COMPANY SL.xls'
        ];

        $inputFileNames = [
            '1953 GRUP SOLER CONSTRUCTORA SL.xls',
            //'ACCENTURE SLU.xlsx',
            //'BARCLAYS PLC.xlsx'
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
            //$sheetData = $spreadsheet->getActiveSheet()->toArray(false, true, true, true);
            $this->worksheet = $worksheet = $spreadsheet->getActiveSheet();
            $result = [];
            $rowIndex = 1;
            $store = false;
            echo '<table>';
            foreach ($worksheet->getRowIterator(100) as $row) {
                $cellIterator = $row->getCellIterator('A', 'J');
                // This loops through all cells, even if a cell value is not set.
                // For 'TRUE', we loop through cells only when their value is set.
                // If this method is not called, the default value is 'false'.
                $cellIterator->setIterateOnlyExistingCells(true); // This loops through all cells,
                $line = [];

                foreach ($cellIterator as $cell) {
                    $line[$cell->getColumn()] = $cell->getValue();
                    if ($cell == 'Accionistas actuales') {
                        if (empty($result['A'])) {
                            $result['A'] = $cell->getRow();
                        }
                    }
                    if ($cell == 'Participadas actuales') {
                        if (empty($result['P'])) {
                            $result['P'] = $cell->getRow();
                        }
                    }
                    //echo '<td>' . $cell->getValue(). '</td>' . PHP_EOL;
                    //dump($cell->getCoordinate() . " = " . $cell->getValue());
                    //$result[$cell->getRow()] = $line;
                }
            }
            $total = $row->getRowIndex();
            dump($result, $cell->getRow());
            //die();

            // INICIO DE ACCIONISTAS
            $shareholders = [];
            $rowIndex = $result['A'];
            $colTitles = [];
            $keys = $orbisKeys;
            $limit = $result['P'];

            $colfound = false;
            $viastr = 'via its funds';
            foreach ($worksheet->getRowIterator($result['A'], $result['P']) as $row) {
                $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
                // This loops through all cells, even if a cell value is not set.
                // For 'TRUE', we loop through cells only when their value is set.
                // If this method is not called, the default value is 'false'.
                $cellIterator->setIterateOnlyExistingCells(true);
                $line = [];
                $rowIndex = $row->getRowIndex();
                $via= '';

                foreach ($cellIterator as $cell) {
                    $key = $cell->getColumn();
                    // Buscamos primero los encabezados
                    if (count($colTitles)<5) {
                        //dump("row: $rowIndex, cell = $cell, column: ".$key);
                        if ($cell==$orbisKeys['A']['Nombre'] || ($cell==$sabiKeys['A']['Nombre'])) {
                            $colTitles['Nombre'] = $key;
                            if ($cell==$sabiKeys['A']['Nombre']) {
                                $keys = $sabiKeys;
                            }
                        }
                        if ($cell==$keys['A']['Pais']) {
                            $colTitles['Pais'] = $key;
                        }
                        if ($cell==$keys['A']['Tipo']) {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($cell==$keys['A']['Direct']) {
                            $colTitles['Direct'] = $key;
                        }
                        if ($cell==$keys['A']['Total']) {
                            $colTitles['Total'] = $key;
                        }
                    } else {
                        // Encontramos los encabezados. Inicializamos valores para empresas
                        if (!$colfound) {
                            if (count($colTitles)) {
                                $colTitles['class'] = $keys['class'];
                                $colTitles['empresa'] = $empresa;
                            } else {
                                $keys = $orbisKeys; // Inicializamos por no haber accionistas
                            }
                            dump($colTitles);
                            $class = $keys['class'];
                            $colfound = true;
                            $index = 0;
                        }

                        // Buscamos los accionistas
                        if ($key == $colTitles['Nombre']) {
                            $Nombre = $cell;
                            $funds = stripos($Nombre, $viastr);
                            if ($funds) {
                                $via = $viastr;
                                $Nombre = substr($Nombre, 0, $funds);
                            }
                        }
                        //dump($line, count($line));
                        if ($class=='ORBIS' && (count($line)<8)) {
                            if ($cell=='Leyenda') {
                                $rowIndex = $limit;
                            } else {
                                if ($key==$colTitles['Nombre']) {
                                    $line['Nombre'] = $Nombre = $cell->getValue();
                                    $line['via'] = $via;
                                    $line['row'] = $rowIndex;
                                }
                                if ($key==$colTitles['Pais']) {
                                    $line['Pais'] = $cell->getValue();
                                }
                                if ($key==$colTitles['Tipo']) {
                                    $line['Tipo'] = $cell->getValue();
                                }
                                if ($key==$colTitles['Direct']) {
                                    $line['Direct'] = $cell->getValue();
                                }
                                if ($key==$colTitles['Total']) {
                                    $line['Total'] = $cell->getValue();
                                }
                            }
                        } else {
                            if ($key=='A') {
                                $i = $key;
                                $i = substr($i, 0, strpos($i, '.'));
                                //dump("key: $key, row: $rowIndex, i: $i, index: $index");
                                if (is_numeric($i) && ($i==($index+1))) {
                                    if ($key==$colTitles['Nombre']) {
                                        $Nombre = $key;
                                    } else {
                                        if ($key>'A' && ($key != $colTitles['Pais'])) {
                                            $Nombre = $cell;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                if ($key==$colTitles['Nombre']) {
                                    $line['Nombre'] = $Nombre = $cell->getValue();
                                    $funds = stripos($Nombre, $viastr);
                                    if ($funds) {
                                        $via = $viastr;
                                        $Nombre = substr($Nombre, 0, $funds);
                                    }
                                    $line['via'] = $via;
                                    $line['row'] = $rowIndex;
                                }
                                if ($key==$colTitles['Pais']) {
                                    $line['Pais'] = $cell->getValue();
                                }
                                if ($key==$colTitles['Tipo']) {
                                    $line['Tipo'] = $cell->getValue();
                                }
                                if ($key==$colTitles['Direct']) {
                                    $line['Direct'] = $cell->getValue();
                                }
                                if ($key==$colTitles['Total']) {
                                    $line['Total'] = $cell->getValue();
                                }
                            }
                        }
                        //dump($line);
                        if (count($line)>6) {
                            if (!empty($Nombre)) {
                                $line['Nombre'] = str_replace($NombreSearch, $NombreReplace, $Nombre);
                            }
                            $shareholders[] = $line;
                            $line = [];
                        }
                    }
                }
                //dump($colTitles);
            }

            dump($shareholders);
            // FIN ACCIONISTAS


            // INICIO PARTICIPADAS
            $colTitles = $subsidiaries = [];
            $colTitles = [
                'index' => 'A'
            ];
            $colfound = false;
            $limit = $total;
            $end = false;
            $line = [];
            foreach ($worksheet->getRowIterator($result['P'], $total) as $row) {
                $cellIterator = $row->getCellIterator('A', self::LASTCOLUMN);
                $cellIterator->setIterateOnlyExistingCells(true);
                $rowIndex = $row->getRowIndex();
                $via= '';
                $i = ''; // Inicializamos el indice en cada fila
                //dump("A$rowIndex: " .$worksheet->getCell('A'.$rowIndex)->getValue());

                if ($end) {
                    break;
                }

                while (count($colTitles)<7) {
                    foreach ($cellIterator as $cell) {
                        $key = $cell->getColumn();
                    // Buscamos primero los encabezados
                        if ($cell==$sabiKeys['P']['Nombre']) {
                            $colTitles['Nombre'] = $key;
                            $keys = $sabiKeys;
                        }
                        if ($cell==$keys['P']['Pais']) {
                            $colTitles['Pais'] = $key;
                            $colTitles['row'] = $rowIndex;
                        }
                        if ($cell==$keys['P']['Tipo']) {
                            $colTitles['Tipo'] = $key;
                        }
                        if ($cell==$keys['P']['Direct']) {
                            $colTitles['Direct'] = $key;
                        }
                        if ($cell==$keys['P']['Total']) {
                            $colTitles['Total'] = $key;
                        }
                    }

                    // Encontramos los encabezados. Inicializamos valores para empresas
                    if (!$colfound) {
                        if (count($colTitles)) {
                            $colTitles['class'] = $keys['class'];
                            $colTitles['empresa'] = $empresa;
                        }
                        $class = $keys['class'];
                        $colfound = true;
                        $index = 0;
                        //dump($colTitles);
                    }
                }


                if (count($colTitles)) { // Por si no hay participadas
                    if ($key=='A' && $cell=='Leyenda') {
                        $end = true;
                        break 2;
                    }
                    //
                    if ($key!=$colTitles['Pais']) {
                        if (empty($colTitles['Nombre']) && strlen($cell)>3) {
                            $NombreCol = $key;
                            //dump("Asigna $NombreCol ($cell)(".strlen($cell).")");
                        }
                    } else {
                        if (empty($colTitles['Nombre'])) {
                            $colTitles['Nombre'] = $NombreCol;
                            //dump($colTitles);
                        }
                    }
                    //dump($colTitles);
                    if (!empty($this->readValue('A'.$rowIndex))) {
                        $i = trim($this->readValue('A'.$rowIndex));
                        if (substr($i, 0, strpos($i, '.'))) {
                            $i = substr($i, 0, strpos($i, '.'));
                        } else {
                            $i = rtrim($i);
                        }
                        //dump("key: $key, i: $i, index: $index, row: $rowIndex");
                        //$line = [];
                    } else {
                        if (is_numeric($i) && ($i==($index+1))) {
                            //dump("key: $key, row: $rowIndex, i: $i, index: $index");
                            if ($key==$colTitles['Pais']) {
                                $line['Pais'] = $cell->getValue();
                            }
                            if ($key==$colTitles['Tipo']) {
                                $line['Tipo'] = $cell->getValue();
                            }
                            if (empty($colTitles['Nombre'])) {
                                $colTitles['Nombre'] = $NombreCol;
                            }
                            if ($key==$colTitles['Nombre']) {
                                $line['Nombre'] = $cell->getValue();
                                $line['row'] = $rowIndex;
                            }
                            if ($key==$colTitles['Direct']) {
                                $line['Direct'] = $cell->getValue();
                            }
                            if ($key==$colTitles['Total']) {
                                $line['Total'] = $cell->getValue();
                            }
                        }
                    }
                }
                $index++;
                //dump($line);
                if (count($line)) {
                    if (empty($line['Tipo'])) {
                        $line['Tipo'] = 'C';
                    }
                    $line['index'] = $index;
                    $subsidiaries[] = $line;
                }
            }
            dump($subsidiaries);

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
