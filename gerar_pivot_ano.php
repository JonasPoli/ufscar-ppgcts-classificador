<?php

/**
 * Script para processar artigos_classificados.csv e gerar um CSV pivotado
 * com classificações nas linhas e anos nas colunas, ideal para gráficos
 * de barras empilhadas no Google Planilhas.
 */

$inputFile = '/Volumes/Dados/documentos-jonas/UFSCAR/Usar no trabalho/txt/artigos_classificados.csv';
$outputFile = '/Volumes/Dados/documentos-jonas/UFSCAR/Usar no trabalho/txt/classificacoes_por_ano.csv';

echo "Iniciando processamento para pivot por ano: $inputFile\n";

if (!file_exists($inputFile)) {
    die("Erro: Arquivo de entrada não encontrado.\n");
}

$handle = fopen($inputFile, 'r');
if (!$handle) {
    die("Erro: Não foi possível abrir o arquivo.\n");
}

$header = fgetcsv($handle, 0, ';');
if (!$header) {
    die("Erro: Cabeçalho inválido.\n");
}

$classIndex = array_search('Classificacao', $header);
$yearIndex = array_search('PY', $header);

if ($classIndex === false) $classIndex = 1;
if ($yearIndex === false) $yearIndex = 20;

$dataMatrix = []; // [classificacao][ano] = count
$allYears = [];
$allClasses = [];

while (($data = fgetcsv($handle, 0, ';')) !== false) {
    if (count($data) <= max($classIndex, $yearIndex)) {
        continue;
    }

    $class = trim($data[$classIndex]);
    $year = trim($data[$yearIndex]);

    if ($class === '') $class = '[Sem Classificação]';
    if ($year === '') $year = '[S.D]'; // Sem Data

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

// Ordena anos numericamente
$years = array_keys($allYears);
sort($years);

// Ordena categorias alfabeticamente
$classes = array_keys($allClasses);
sort($classes);

// Gera o arquivo de saída
$out = fopen($outputFile, 'w');
if (!$out) {
    die("Erro: Não foi possível criar o arquivo de saída.\n");
}

// Cabeçalho: Ano; Class1; Class2; ...
$csvHeader = array_merge(['Ano'], $classes);
fputcsv($out, $csvHeader, ';');

foreach ($years as $year) {
    $row = [$year];
    foreach ($classes as $class) {
        $row[] = isset($dataMatrix[$class][$year]) ? $dataMatrix[$class][$year] : 0;
    }
    fputcsv($out, $row, ';');
}

fclose($out);

echo "Processamento concluído!\n";
echo "Categorias encontradas: " . count($classes) . "\n";
echo "Anos encontrados: " . count($years) . " (" . implode(', ', $years) . ")\n";
echo "Arquivo gerado: $outputFile\n";
