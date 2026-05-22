<?php

/**
 * Gera arquivos para o Gephi a partir de:
 *
 * - vosviewer-categorias.txt
 *
 * Saídas:
 *
 * - gephi-categorias-nodes.csv
 * - gephi-categorias-edges.csv
 *
 * Como importar no Gephi:
 * 1. Data Laboratory
 * 2. Import Spreadsheet
 * 3. Importar primeiro nodes.csv como Nodes table
 * 4. Importar depois edges.csv como Edges table
 */

$baseDir = __DIR__;

$arquivoEntrada = $baseDir . '/vosviewer-categorias.txt';

$arquivoNodes = $baseDir . '/gephi-categorias-nodes.csv';
$arquivoEdges = $baseDir . '/gephi-categorias-edges.csv';

if (!file_exists($arquivoEntrada)) {
    die("Erro: arquivo vosviewer-categorias.txt não encontrado. Rode primeiro: php gerar-vosviewer-categorias.php" . PHP_EOL);
}

$handle = fopen($arquivoEntrada, 'r');

if (!$handle) {
    die("Erro: não foi possível abrir vosviewer-categorias.txt" . PHP_EOL);
}

$categorias = [];
$ocorrencias = [];
$coocorrencias = [];

$primeiraLinha = true;

while (($linha = fgets($handle)) !== false) {
    $linha = trim($linha);

    if ($linha === '') {
        continue;
    }

    if ($primeiraLinha) {
        $primeiraLinha = false;

        if (mb_strtolower($linha) === 'label') {
            continue;
        }
    }

    $itens = explode(';', $linha);
    $itens = array_map('trim', $itens);
    $itens = array_filter($itens, fn($item) => $item !== '');
    $itens = array_values(array_unique($itens));

    if (count($itens) === 0) {
        continue;
    }

    foreach ($itens as $categoria) {
        if (!isset($categorias[$categoria])) {
            $categorias[$categoria] = count($categorias) + 1;
            $ocorrencias[$categoria] = 0;
        }

        $ocorrencias[$categoria]++;
    }

    if (count($itens) > 1) {
        for ($i = 0; $i < count($itens); $i++) {
            for ($j = $i + 1; $j < count($itens); $j++) {
                $categoriaA = $itens[$i];
                $categoriaB = $itens[$j];

                $idA = $categorias[$categoriaA];
                $idB = $categorias[$categoriaB];

                if ($idA > $idB) {
                    [$idA, $idB] = [$idB, $idA];
                }

                $chave = $idA . '-' . $idB;

                if (!isset($coocorrencias[$chave])) {
                    $coocorrencias[$chave] = [
                        'source' => $idA,
                        'target' => $idB,
                        'weight' => 0
                    ];
                }

                $coocorrencias[$chave]['weight']++;
            }
        }
    }
}

fclose($handle);

/**
 * Gera nodes.csv
 */
$fpNodes = fopen($arquivoNodes, 'w');

if (!$fpNodes) {
    die("Erro: não foi possível criar gephi-categorias-nodes.csv" . PHP_EOL);
}

fwrite($fpNodes, "Id;Label;Weight" . PHP_EOL);

foreach ($categorias as $categoria => $id) {
    fwrite($fpNodes, csvLinha([
        $id,
        $categoria,
        $ocorrencias[$categoria] ?? 0
    ]) . PHP_EOL);
}

fclose($fpNodes);

/**
 * Gera edges.csv
 */
$fpEdges = fopen($arquivoEdges, 'w');

if (!$fpEdges) {
    die("Erro: não foi possível criar gephi-categorias-edges.csv" . PHP_EOL);
}

fwrite($fpEdges, "Source;Target;Weight;Type" . PHP_EOL);

foreach ($coocorrencias as $aresta) {
    fwrite($fpEdges, csvLinha([
        $aresta['source'],
        $aresta['target'],
        $aresta['weight'],
        'Undirected'
    ]) . PHP_EOL);
}

fclose($fpEdges);

echo "Arquivos para Gephi gerados com sucesso:" . PHP_EOL;
echo "- {$arquivoNodes}" . PHP_EOL;
echo "- {$arquivoEdges}" . PHP_EOL;
echo PHP_EOL;
echo "Categorias: " . count($categorias) . PHP_EOL;
echo "Relações: " . count($coocorrencias) . PHP_EOL;

function csvLinha(array $valores): string
{
    $tratados = [];

    foreach ($valores as $valor) {
        $valor = (string) $valor;
        $valor = str_replace('"', '""', $valor);
        $tratados[] = '"' . $valor . '"';
    }

    return implode(';', $tratados);
}