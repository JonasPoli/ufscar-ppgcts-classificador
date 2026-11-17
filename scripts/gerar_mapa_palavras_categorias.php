<?php
/**
 * gerar_mapa_palavras_categorias.php
 * 
 * Script para gerar rede de coocorrência de termos pulverizados
 * onde a cor de cada palavra-chave corresponde à sua Categoria Sociotécnica (1 a 10).
 * Caminhos adaptados para a estrutura de pastas organizada.
 */

ini_set('memory_limit', '1024M');

// 1. Definição das Categorias e Palavras-chave via Config Unificado
require_once __DIR__ . '/config_categorias.php';

// Mapeamento inverso: palavra-chave => ID numérico do cluster (1 a 10) e categoria correspondente
$termoParaCluster = [];
$termoParaNomeCat = [];
$catIndex = 1;
$todosTermos = [];

foreach ($categoriasNovas as $mainCat => $subs) {
    foreach ($subs as $subCat => $keywords) {
        foreach ($keywords as $w) {
            $termoParaCluster[$w] = $catIndex;
            $termoParaNomeCat[$w] = $mainCat;
            $todosTermos[$w] = true;
        }
    }
    $catIndex++;
}

$termosUnicos = array_keys($todosTermos);
$totalTermos = count($termosUnicos);
echo "Total de palavras-chave monitoradas: $totalTermos\n";

// 2. Leitura dos artigos classificados
$csvFile = __DIR__ . '/../resultados/artigos_classificados.csv';
if (!file_exists($csvFile)) {
    die("Erro: Execute primeiro 'classifica.php' para gerar o arquivo '$csvFile'.\n");
}

$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle, 0, ';', '"', "\\");

$tiIndex = array_search('TI', $header);
$deIndex = array_search('DE', $header);
$idIndex = array_search('ID', $header);
$abIndex = array_search('AB', $header);

// Inicializa contadores de ocorrência e coocorrência
$ocorrencias = array_fill_keys($termosUnicos, 0);
$coocorrencias = [];

for ($i = 0; $i < $totalTermos; $i++) {
    $coocorrencias[$termosUnicos[$i]] = array_fill_keys($termosUnicos, 0);
}

$totalArtigos = 0;
while (($row = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {
    $totalArtigos++;
    $textoTotal = strtolower(
        ($row[$tiIndex] ?? '') . ' ' .
        ($row[$deIndex] ?? '') . ' ' .
        ($row[$idIndex] ?? '') . ' ' .
        ($row[$abIndex] ?? '')
    );

    // Identifica quais termos únicos aparecem neste artigo
    $termosPresentes = [];
    foreach ($termosUnicos as $w) {
        if (contemTermo($textoTotal, $w)) {
            $termosPresentes[] = $w;
            $ocorrencias[$w]++;
        }
    }

    // Registra as coocorrências
    $countPresentes = count($termosPresentes);
    for ($i = 0; $i < $countPresentes; $i++) {
        for ($j = $i + 1; $j < $countPresentes; $j++) {
            $t1 = $termosPresentes[$i];
            $t2 = $termosPresentes[$j];
            $coocorrencias[$t1][$t2]++;
            $coocorrencias[$t2][$t1]++;
        }
    }
}
fclose($handle);

echo "Total de artigos válidos lidos: $totalArtigos\n";

// 3. Escrita do arquivo de Mapa (vosviewer-keywords-map.txt)
$mapTxt = "id\tlabel\tweight\tcluster\n";
$idIndexMap = 1;
$termToId = [];

foreach ($termosUnicos as $w) {
    if ($ocorrencias[$w] > 0) {
        $termToId[$w] = $idIndexMap;
        $cluster = $termoParaCluster[$w];
        $mapTxt .= "$idIndexMap\t$w\t{$ocorrencias[$w]}\t$cluster\n";
        $idIndexMap++;
    }
}

file_put_contents(__DIR__ . '/../redes_vosviewer/vosviewer-keywords-map.txt', $mapTxt);

// 4. Escrita do arquivo de Rede (vosviewer-keywords-network.txt)
$networkTxt = "";
$termosAtivos = array_keys($termToId);
$countAtivos = count($termosAtivos);

for ($i = 0; $i < $countAtivos; $i++) {
    for ($j = $i + 1; $j < $countAtivos; $j++) {
        $t1 = $termosAtivos[$i];
        $t2 = $termosAtivos[$j];
        $strength = $coocorrencias[$t1][$t2];
        if ($strength > 0) {
            $networkTxt .= "{$termToId[$t1]}\t{$termToId[$t2]}\t$strength\n";
        }
    }
}

file_put_contents(__DIR__ . '/../redes_vosviewer/vosviewer-keywords-network.txt', $networkTxt);

echo "=======================================================\n";
echo "   REDE DE PALAVRAS-CHAVE POR CATEGORIAS GERADA!\n";
echo "=======================================================\n";
echo "Arquivos gerados com sucesso na pasta 'redes_vosviewer':\n";
echo " - vosviewer-keywords-map.txt\n";
echo " - vosviewer-keywords-network.txt\n";
echo "\nImporte no VOSviewer via:\n";
echo "Create > Create a map based on network data > VOSviewer tab\n";
echo "E use o arquivo de Mapa e Rede de palavras-chave gerados acima.\n";
echo "=======================================================\n";
