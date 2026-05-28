<?php
/**
 * gerar_pivot_ano.php
 * Script para processar resultados/artigos_classificados.csv e gerar um CSV pivotado
 * com as Subcategorias nas linhas e anos de publicação (PY) nas colunas.
 * Jonas Ernesto Poli - Mestrado PPGCTS / UFSCar
 */

$inputFile = __DIR__ . '/../resultados/artigos_classificados.csv';
$outputFile = __DIR__ . '/../resultados/classificacoes_por_ano.csv';

echo "Iniciando processamento para pivot por ano: $inputFile\n";

if (!file_exists($inputFile)) {
    die("Erro: Arquivo de entrada '$inputFile' não encontrado.\n");
}

$handle = fopen($inputFile, 'r');
if (!$handle) {
    die("Erro: Não foi possível abrir o arquivo.\n");
}

$header = fgetcsv($handle, 0, ';', '"', "\\");
if (!$header) {
    die("Erro: Cabeçalho inválido.\n");
}

$classIndex = array_search('Subcategoria', $header);
$yearIndex = array_search('PY', $header);

if ($classIndex === false) {
    $classIndex = 2;
}
if ($yearIndex === false) {
    $yearIndex = 21;
}

$dataMatrix = [];
$allYears = [];
$allClasses = [];

while (($data = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {
    if (count($data) <= max($classIndex, $yearIndex)) {
        continue;
    }

    $class = trim($data[$classIndex]);
    $year = trim($data[$yearIndex]);

    if ($class === '') $class = '[Sem Subcategoria]';
    if ($year === '') $year = '[S.D]';

    if (!isset($dataMatrix[$class])) {
        $dataMatrix[$class] = [];
    }
    if (!isset($dataMatrix[$class][$year])) {
        $dataMatrix[$class][$year] = 0;
    }

    $dataMatrix[$class][$year]++;
    
    $allYears[$year] = true;
    $allClasses[$class] = true;
}

fclose($handle);

$years = array_keys($allYears);
sort($years);

$classes = array_keys($allClasses);
sort($classes);

$out = fopen($outputFile, 'w');
if (!$out) {
    die("Erro: Não foi possível criar o arquivo de saída: $outputFile\n");
}

$csvHeader = array_merge(['Ano'], $classes);
fputcsv($out, $csvHeader, ';', '"', "\\");

foreach ($years as $year) {
    $row = [$year];
    foreach ($classes as $class) {
        $row[] = isset($dataMatrix[$class][$year]) ? $dataMatrix[$class][$year] : 0;
    }
    fputcsv($out, $row, ';', '"', "\\");
}

fclose($out);

echo "Processamento concluído!\n";
echo "Subcategorias encontradas: " . count($classes) . "\n";
echo "Anos encontrados: " . count($years) . " (" . implode(', ', $years) . ")\n";
echo "Arquivo gerado: $outputFile\n";
