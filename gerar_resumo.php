<?php

/**
 * Script para processar artigos_classificados.csv e gerar um resumo
 * com a quantidade de cada classificação.
 */

$inputFile = '/Volumes/Dados/documentos-jonas/UFSCAR/Usar no trabalho/txt/artigos_classificados.csv';
$outputFile = '/Volumes/Dados/documentos-jonas/UFSCAR/Usar no trabalho/txt/contagem_classificacoes.csv';

echo "Iniciando processamento de: $inputFile\n";

if (!file_exists($inputFile)) {
    die("Erro: Arquivo de entrada não encontrado.\n");
}

$handle = fopen($inputFile, 'r');
if (!$handle) {
    die("Erro: Não foi possível abrir o arquivo para leitura.\n");
}

// O CSV utiliza ponto e vírgula como delimitador
$header = fgetcsv($handle, 0, ';');

if (!$header) {
    die("Erro: O arquivo está vazio ou o formato do cabeçalho é inválido.\n");
}

// Localiza o índice da coluna "Classificacao"
$classificacaoIndex = array_search('Classificacao', $header);

if ($classificacaoIndex === false) {
    echo "Aviso: Coluna 'Classificacao' não encontrada pelo nome. Usando o índice 1 como padrão.\n";
    $classificacaoIndex = 1;
}

$counts = [];
$totalRows = 0;

while (($data = fgetcsv($handle, 0, ';')) !== false) {
    // Ignora linhas que não possuem dados suficientes
    if (count($data) <= $classificacaoIndex) {
        continue;
    }

    $classValue = trim($data[$classificacaoIndex]);

    // Caso o valor esteja vazio
    if ($classValue === '') {
        $classValue = '[Sem Classificação]';
    }

    if (!isset($counts[$classValue])) {
        $counts[$classValue] = 0;
    }

    $counts[$classValue]++;
    $totalRows++;
}

fclose($handle);

// Ordena o resultado por quantidade (maior para menor)
arsort($counts);

// Gera o arquivo de saída
$out = fopen($outputFile, 'w');
if (!$out) {
    die("Erro: Não foi possível criar o arquivo de saída: $outputFile\n");
}

// Escreve o cabeçalho no arquivo de saída (também usando ponto e vírgula para manter padrão)
fputcsv($out, ['Classificacao', 'Quantidade'], ';');

foreach ($counts as $class => $quantity) {
    fputcsv($out, [$class, $quantity], ';');
}

fclose($out);

echo "Processamento concluído com sucesso!\n";
echo "Total de registros processados: $totalRows\n";
echo "Total de categorias únicas: " . count($counts) . "\n";
echo "Arquivo gerado: $outputFile\n";
