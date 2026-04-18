<?php

/**
 * SCRIPT DE PROCESSAMENTO - VERSÃO 12.0 (ESTÁVEL)
 * Jonas Ernesto Poli - Mestrado PPGCTS / UFSCar
 * * Correção Crítica: Geração de Tesauro em formato .txt (Tab-Separated)
 * Este formato evita o erro de "duas colunas" no VOSviewer.
 */

// 1. DICIONÁRIO DE CATEGORIAS
$categorias = [
    "Assistencia Virtual e Educacao" => ['chatbot', 'conversational', 'virtual assistant', 'voice assistant', 'avatar', 'educational technology', 'students', 'learning', 'tutoring', 'knowledge graphs', 'extension service', 'advisory', 'telescreening'],
    "Diagnostico e Protecao Fitossanitaria" => ['disease', 'pest', 'pathogen', 'weed', 'herbicide', 'infestation', 'protection', 'glyphosate', 'atrazine', 'diabetes', 'retinopathy'],
    "Visao Computacional e Fenotipagem" => ['computer vision', 'image sensing', 'spectral', 'hyperspectral', 'remote sensing', 'phenotyping', 'object detection', 'segmentation'],
    "Robotica e Mecanizacao" => ['robot', 'uav', 'drone', 'autonomous', 'machinery', 'tractor', 'harvester'],
    "Cadeia Produtiva e Qualidade" => ['supply chain', 'agribusiness', 'agro-industry', 'agritech', 'traceability', 'blockchain', 'food security', 'logistics', 'food model', 'mineral intakes'],
    "Mercado Risco e Previsao" => ['forecasting', 'market', 'economic', 'financial', 'risk', 'price', 'decision making', 'business model', 'investment'],
    "Governanca, Gestao e Impacto Social" => ['ethics', 'ethical', 'policy', 'digital divide', 'digital inclusion', 'empower', 'gender', 'inequality', 'technology acceptance', 'tam', 'utaut', 'labor', 'employment'],
    "Clima, Solo e Meio Ambiente" => ['weather', 'climate', 'sustainable', 'carbon', 'pollution', 'residue', 'emissions', 'ecological', 'renewable', 'habitats'],
    "Recursos Hidricos" => ['irrigation', 'water', 'hydrological', 'moisture', 'groundwater'],
    "IoT e Sensores" => ['internet of things', 'iot', 'sensor', 'wsn', 'cybersecurity'],
    "Agricultura de Precisao e Cultivo" => ['precision agriculture', 'smart farm', 'yield', 'fertilizer', 'seeding', 'orchard'],
    "IA: Arquiteturas e Metodos" => ['large language model', 'llm', 'generative ai', 'genai', 'gpt', 'neural network', 'deep learning', 'machine learning', 'algorithm', 'transformer', 'reinforcement', 'ontology', 'simulation', 'modeling', 'artificial intelligence', 'fuzzy', 'agentic', 'planning', 'rag']
];

$validadoresIA = ['intelligence', 'machine learning', 'language model', 'neural', 'algorithm', 'vision', 'robot', 'uav', 'drone', 'sensor', 'iot', 'llm', 'genai', 'generative ai', 'gpt', 'transformer', 'automation', 'chatbot', 'assistant', 'agentic', 'planning', 'rag'];

$tagsWoS = ["AU", "AF", "TI", "SO", "LA", "DT", "DE", "ID", "AB", "C1", "RP", "EM", "FU", "FX", "CR", "NR", "TC", "PY", "DI", "WC", "SC"];

$input = "rural_and_AI_01.txt";
if (!file_exists($input))
    die("Arquivo não encontrado.\n");

$raw = file_get_contents($input);
$artigos = explode("\nER\n", $raw);

$header = "UT;Classificacao;Pais;" . implode(";", $tagsWoS) . "\n";
$txtClassificados = $header;
$txtPendentes = $header;
$txtRuidos = $header;

foreach ($artigos as $bloco) {
    if (trim($bloco) === "")
        continue;
    $campos = [];
    $linhas = explode("\n", $bloco);
    $tagAtual = "";
    foreach ($linhas as $linha) {
        if (strlen($linha) < 3)
            continue;
        $tag = substr($linha, 0, 2);
        $val = trim(substr($linha, 3));
        if ($tag !== "  ") {
            $tagAtual = $tag;
            $campos[$tagAtual] = $val;
        } else {
            $campos[$tagAtual] .= " " . $val;
        }
    }

    $textoTotal = strtolower(($campos["TI"] ?? "") . " " . ($campos["DE"] ?? "") . " " . ($campos["ID"] ?? "") . " " . ($campos["AB"] ?? ""));
    $status = "Valido";

    if (strpos($textoTotal, 'ragtime') !== false || strpos($textoTotal, 'shukla') !== false || strpos($textoTotal, 'russian researchers') !== false) {
        $status = "Ruido";
    }

    $ehIA = false;
    foreach ($validadoresIA as $v) {
        if (strpos($textoTotal, $v) !== false) {
            $ehIA = true;
            break;
        }
    }
    if (!$ehIA)
        $status = "Ruido";

    $catFinal = "Nao Classificado";
    if ($status === "Valido") {
        foreach ($categorias as $nome => $termos) {
            foreach ($termos as $t) {
                if (strpos($textoTotal, $t) !== false) {
                    $catFinal = $nome;
                    break 2;
                }
            }
        }
    }

    // Extração de País
    $pais = "Nao Informado";
    $end = $campos["RP"] ?? $campos["C1"] ?? "";
    if ($end !== "") {
        $parts = explode(",", $end);
        $last = trim(end($parts));
        $clean = trim(preg_replace('/[0-9\[\]\.]/', '', $last));
        $pWords = explode(" ", $clean);
        $final = strtoupper(end($pWords));
        if (strpos($final, "CHINA") !== false)
            $final = "CHINA";
        if (strpos($final, "USA") !== false)
            $final = "USA";
        $pais = (strlen($final) > 1) ? $final : "Nao Informado";
    }

    $linha = ($campos["UT"] ?? "S_UT") . ";" . ($status === "Ruido" ? "Ruido" : $catFinal) . ";" . $pais . ";" . implode(";", array_map(fn($t) => str_replace([";", "\n", "\r"], " ", $campos[$t] ?? ""), $tagsWoS)) . "\n";

    if ($status === "Ruido")
        $txtRuidos .= $linha;
    elseif ($catFinal === "Nao Classificado")
        $txtPendentes .= $linha;
    else
        $txtClassificados .= $linha;
}

file_put_contents("artigos_classificados.csv", $txtClassificados);
file_put_contents("artigos_revisar_categoria.csv", $txtPendentes);
file_put_contents("artigos_descartados_ruido.csv", $txtRuidos);

// --- GERAÇÃO DO TESAURO (VERSÃO TXT COM TABULAÇÃO) ---
$tes = "Label\tReplace by\n"; // \t é o caractere de Tabulação
foreach ($categorias as $c => $words) {
    foreach ($words as $w) {
        $tes .= $w . "\t" . $c . "\n";
    }
}
file_put_contents("tesauro.txt", $tes);

echo "Finalizado! Agora use o arquivo 'tesauro.txt' no VOSviewer.\n";