<?php
/**
 * gerar_vosviewer_subcategorias.php
 * 
 * Este script gera um mapa de rede de coocorrência no VOSviewer contendo
 * os nós das subcategorias da pesquisa (31 no dicionário), a partir dos registros
 * classificados: cada artigo liga entre si as subcategorias em que pontuou.
 * As cores dos nós (clusters) correspondem exatamente às suas 10 Categorias Principais.
 * Caminhos adaptados para a estrutura de pastas organizada.
 */

ini_set('memory_limit', '1024M');

require_once __DIR__ . '/config_categorias.php';

// Mapeia cada subcategoria a um cluster (1 a 10) baseado em sua categoria mãe
$subCatParaCluster = [];
$subCatLista = [];
$catIndex = 1;

foreach ($categoriasNovas as $mainCat => $subs) {
    foreach ($subs as $subCat => $keywords) {
        $subCatParaCluster[$subCat] = $catIndex;
        $subCatLista[] = $subCat;
    }
    $catIndex++;
}

$totalSubCats = count($subCatLista);
echo "Total de subcategorias monitoradas: $totalSubCats\n";

// Inicializa contadores de ocorrência e coocorrência de subcategorias
$ocorrencias = array_fill_keys($subCatLista, 0);
$coocorrencias = [];
foreach ($subCatLista as $s) {
    $coocorrencias[$s] = array_fill_keys($subCatLista, 0);
}

// Leitura dos artigos brutos
$input = __DIR__ . '/../fontes_wos/todos_arquivos_juntos.txt';
if (!file_exists($input)) {
    die("Erro: Arquivo '$input' não encontrado.\n");
}

$raw = file_get_contents($input);
$artigos = explode("\nER\n", $raw);
$totalArtigos = 0;

foreach ($artigos as $bloco) {
    if (trim($bloco) === "") continue;

    $campos = lerCamposWoS($bloco);
    if (!ehRegistroWoS($campos)) continue;

    // Mesma triagem do classifica.php: só entram na rede os registros classificados
    if (triarRegistro($campos) !== null) continue;

    // Subcategorias em que o artigo pontuou (as regras ficam em config_categorias.php)
    $subCatsAtivas = array_keys(pontuarSubcategorias($campos));
    foreach ($subCatsAtivas as $subCat) {
        $ocorrencias[$subCat]++;
    }

    // Registra as coocorrências
    $countAtivas = count($subCatsAtivas);
    if ($countAtivas > 0) {
        $totalArtigos++;
        for ($i = 0; $i < $countAtivas; $i++) {
            for ($j = $i + 1; $j < $countAtivas; $j++) {
                $s1 = $subCatsAtivas[$i];
                $s2 = $subCatsAtivas[$j];
                $coocorrencias[$s1][$s2]++;
                $coocorrencias[$s2][$s1]++;
            }
        }
    }
}

// 3. Escrita do arquivo de Mapa das Subcategorias
$mapTxt = "id\tlabel\tweight\tcluster\n";
$idIndex = 1;
$subToId = [];

foreach ($subCatLista as $s) {
    if ($ocorrencias[$s] > 0) {
        $subToId[$s] = $idIndex;
        $cluster = $subCatParaCluster[$s];
        $mapTxt .= "$idIndex\t$s\t{$ocorrencias[$s]}\t$cluster\n";
        $idIndex++;
    }
}

file_put_contents(__DIR__ . '/../redes_vosviewer/vosviewer-subcategorias-map.txt', $mapTxt);

// 4. Escrita do arquivo de Rede das Subcategorias
$networkTxt = "";
$subsAtivas = array_keys($subToId);
$countAtivas = count($subsAtivas);

for ($i = 0; $i < $countAtivas; $i++) {
    for ($j = $i + 1; $j < $countAtivas; $j++) {
        $s1 = $subsAtivas[$i];
        $s2 = $subsAtivas[$j];
        $strength = $coocorrencias[$s1][$s2];
        if ($strength > 0) {
            $networkTxt .= "{$subToId[$s1]}\t{$subToId[$s2]}\t$strength\n";
        }
    }
}

file_put_contents(__DIR__ . '/../redes_vosviewer/vosviewer-subcategorias-network.txt', $networkTxt);

echo "=======================================================\n";
echo "   REDE DE SUBCATEGORIAS GERADA COM SUCESSO! (" . count($subToId) . " nós, $totalArtigos artigos)\n";
echo "=======================================================\n";
echo "Arquivos gerados na pasta 'redes_vosviewer':\n";
echo " - vosviewer-subcategorias-map.txt\n";
echo " - vosviewer-subcategorias-network.txt\n";
echo "-------------------------------------------------------\n";
echo "COMO IMPORTAR NO VOSviewer:\n";
echo " 1. Clique em 'Create' > 'Create a map based on network data' > 'Next'.\n";
echo " 2. Selecione:\n";
echo "     - Map file: 'vosviewer-subcategorias-map.txt'\n";
echo "     - Network file: 'vosviewer-subcategorias-network.txt'\n";
echo " 3. Conclua e veja o gráfico de subcategorias estruturado e colorido pelas 10 categorias!\n";
echo "=======================================================\n";
