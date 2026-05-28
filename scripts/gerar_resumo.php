<?php
/**
 * gerar_resumo.php
 * Script para processar resultados/artigos_classificados.csv e gerar um resumo
 * com a quantidade de cada classificação (Categorias Principais e Subcategorias).
 * Jonas Ernesto Poli - Mestrado PPGCTS / UFSCar
 */

$inputFile = __DIR__ . '/../resultados/artigos_classificados.csv';
$outputFile = __DIR__ . '/../resultados/contagem_classificacoes.csv';

echo "Iniciando processamento de resumo de: $inputFile\n";

if (!file_exists($inputFile)) {
    die("Erro: Arquivo de entrada '$inputFile' não encontrado.\n");
}

$handle = fopen($inputFile, 'r');
if (!$handle) {
    die("Erro: Não foi possível abrir o arquivo para leitura.\n");
}

$header = fgetcsv($handle, 0, ';', '"', "\\");

if (!$header) {
    die("Erro: O arquivo está vazio ou o formato do cabeçalho é inválido.\n");
}

$catIndex = array_search('Categoria_Principal', $header);
$subCatIndex = array_search('Subcategoria', $header);

if ($catIndex === false) {
    $catIndex = 1;
}
if ($subCatIndex === false) {
    $subCatIndex = 2;
}

$countsCat = [];
$countsSub = [];
$totalRows = 0;

while (($data = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {
    if (count($data) <= max($catIndex, $subCatIndex)) {
        continue;
    }

    $catValue = trim($data[$catIndex]);
    $subValue = trim($data[$subCatIndex]);

    if ($catValue === '') {
        $catValue = '[Sem Categoria Principal]';
    }
    if ($subValue === '') {
        $subValue = '[Sem Subcategoria]';
    }

    $countsCat[$catValue] = ($countsCat[$catValue] ?? 0) + 1;
    $countsSub[$subValue] = ($countsSub[$subValue] ?? 0) + 1;
    $totalRows++;
}

fclose($handle);

arsort($countsCat);
arsort($countsSub);

$out = fopen($outputFile, 'w');
if (!$out) {
    die("Erro: Não foi possível criar o arquivo de saída: $outputFile\n");
}

fputcsv($out, ['Tipo_Classificacao', 'Item_Classificado', 'Quantidade'], ';', '"', "\\");

foreach ($countsCat as $class => $quantity) {
    fputcsv($out, ['Categoria_Principal', $class, $quantity], ';', '"', "\\");
}

foreach ($countsSub as $class => $quantity) {
    fputcsv($out, ['Subcategoria', $class, $quantity], ';', '"', "\\");
}

fclose($out);

echo "Processamento concluído com sucesso!\n";
echo "Total de registros processados: $totalRows\n";
echo "Total de Categorias Únicas: " . count($countsCat) . "\n";
echo "Total de Subcategorias Únicas: " . count($countsSub) . "\n";
echo "Arquivo gerado: $outputFile\n";
