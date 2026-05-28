<?php
/**
 * gerar_vosviewer_subcategorias.php
 * 
 * Este script gera um mapa de rede de coocorrência no VOSviewer contendo
 * exatamente os nós das 29 Subcategorias da pesquisa.
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

$validadoresIA = [
    'intelligence', 'intelligent', 'machine learning', 'language model', 'neural', 'algorithm', 
    'vision', 'robot', 'uav', 'drone', 'sensor', 'iot', 'llm', 'genai', 'generative ai', 'gpt', 
    'transformer', 'automation', 'chatbot', 'assistant', 'agentic', 'conversational', 
    'virtual agent', 'planning', 'rag', 'deep learning', 'computer vision', 'expert system', 
    'neural network', 'fuzzy', 'decision support'
];

$validadoresAgro = [
    'rural', 'farm', 'agricultur', 'agro', 'crop', 'livestock', 'cattle', 'cow', 'pasture', 
    'soil', 'irrigation', 'silo', 'pest', 'disease', 'plant', 'weed', 'harvest', 'tractor', 
    'pulveriz', 'sowing', 'seeding', 'orchard', 'grape', 'vineyard', 'sheep', 'pig', 'swine', 
    'poultry', 'chicken', 'milk', 'dairy', 'agronom', 'cultivar', 'plantation', 'forestry',
    'pesticide', 'fertilizer', 'herding', 'animal welfare', 'horticulture', 'field'
];

foreach ($artigos as $bloco) {
    if (trim($bloco) === "") continue;

    $campos = [];
    $linhas = explode("\n", $bloco);
    $tagAtual = "";
    
    foreach ($linhas as $linha) {
        if (strlen($linha) < 3) continue;
        $tag = substr($linha, 0, 2);
        $val = trim(substr($linha, 3));
        if ($tag !== "  ") {
            $tagAtual = $tag;
            $campos[$tagAtual] = $val;
        } else {
            $campos[$tagAtual] = ($campos[$tagAtual] ?? "") . " " . $val;
        }
    }

    $textoTotal = strtolower(
        ($campos["TI"] ?? "") . " " . 
        ($campos["DE"] ?? "") . " " . 
        ($campos["ID"] ?? "") . " " . 
        ($campos["AB"] ?? "")
    );

    // Validação Temporal, de IA e de Agro
    $anoPY = (int)($campos["PY"] ?? 0);
    if ($anoPY > 0 && $anoPY < 2000) continue;

    $ehIA = false;
    foreach ($validadoresIA as $v) {
        if (strpos($textoTotal, $v) !== false) {
            $ehIA = true;
            break;
        }
    }
    if (!$ehIA) continue;

    $ehAgro = false;
    foreach ($validadoresAgro as $a) {
        if (strpos($textoTotal, $a) !== false) {
            $ehAgro = true;
            break;
        }
    }
    if (!$ehAgro) continue;

    // Calcula pontuação de todas as subcategorias
    $subCatsAtivas = [];
    foreach ($categoriasNovas as $mainCat => $subs) {
        foreach ($subs as $subCat => $keywords) {
            $score = 0;
            foreach ($keywords as $w) {
                $score += substr_count(strtolower($campos["TI"] ?? ""), $w) * 3;
                $score += substr_count(strtolower($campos["DE"] ?? ""), $w) * 2;
                $score += substr_count(strtolower($campos["ID"] ?? ""), $w) * 2;
                $score += substr_count(strtolower($campos["AB"] ?? ""), $w) * 1;
            }
            if ($score > 0) {
                $subCatsAtivas[] = $subCat;
                $ocorrencias[$subCat]++;
            }
        }
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

// 3. Escrita do arquivo de Mapa das 29 Subcategorias
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

// 4. Escrita do arquivo de Rede das 29 Subcategorias
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
echo "   REDE DE 29 SUBCATEGORIAS GERADA COM SUCESSO!\n";
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
echo " 3. Conclua e veja o gráfico de 29 nós perfeitamente estruturado e colorido pelas 10 categorias!\n";
echo "=======================================================\n";
