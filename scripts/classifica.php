<?php
/**
 * classifica.php - VERSÃO 13.2 (ESTÁVEL / CLASSIFICAÇÃO BINÁRIA)
 * Jonas Ernesto Poli - Mestrado PPGCTS / UFSCar
 * 
 * Este script realiza a classificação binária de todos os artigos da base:
 * ou eles são "Classificados" sob uma das subcategorias, ou são marcados como "Ruído".
 * 
 * Caminhos de entrada e saída adaptados para a estrutura de pastas organizada.
 */

// Aumenta o limite de memória para lidar com arquivos massivos
ini_set('memory_limit', '1024M');

// -----------------------------------------------------------------------------------------
// 1. CARREGAMENTO DAS CONFIGURAÇÕES E DICIONÁRIOS UNIFICADOS
// -----------------------------------------------------------------------------------------
require_once __DIR__ . '/config_categorias.php';

// -----------------------------------------------------------------------------------------
// 4. METADADOS E LEITURA DO ARQUIVO WoS
// -----------------------------------------------------------------------------------------
$tagsWoS = ["AU", "AF", "TI", "SO", "LA", "DT", "DE", "ID", "AB", "C1", "RP", "EM", "FU", "FX", "CR", "NR", "TC", "PY", "DI", "WC", "SC"];

$input = __DIR__ . '/../fontes_wos/todos_arquivos_juntos.txt';
if (!file_exists($input)) {
    die("Erro Crítico: Arquivo '$input' não encontrado. Execute primeiro 'unificar_fontes.php'.\n");
}

echo "Iniciando processamento e classificação sociotécnica...\n";
$raw = file_get_contents($input);
$artigos = explode("\nER\n", $raw);
$totalLidos = 0;

// Preparação dos cabeçalhos dos arquivos CSV
$headerClassificados = "UT;Categoria_Principal;Subcategoria;Pais;" . implode(";", $tagsWoS) . "\n";
$headerRuidos = "UT;Motivo_Descarte;Pais;" . implode(";", $tagsWoS) . "\n";

$txtClassificados = $headerClassificados;
$txtPendentes = $headerClassificados; // Ficará vazio (apenas cabeçalho) devido à classificação binária
$txtRuidos = $headerRuidos;

$txtArestas = "Source;Target\n"; // Gephi: Pais -> Categoria
$vosviewerLinhas = ["label"];    // VOSviewer coocorrências

// Contadores estatísticos
$estatisticas = [
    'Validos_Classificados' => 0,
    'Validos_Pendentes' => 0,
    'Ruidos' => 0,
    'Motivos_Descarte' => []
];

// -----------------------------------------------------------------------------------------
// 5. PROCESSAMENTO DE CADA ARTIGO
// -----------------------------------------------------------------------------------------
foreach ($artigos as $bloco) {
    if (trim($bloco) === "") continue;
    $totalLidos++;

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

    $ut = $campos["UT"] ?? "S_UT_" . $totalLidos;
    $textoTotal = strtolower(
        ($campos["TI"] ?? "") . " " . 
        ($campos["DE"] ?? "") . " " . 
        ($campos["ID"] ?? "") . " " . 
        ($campos["AB"] ?? "")
    );
    
    $status = "Valido";
    $motivoDescarte = "";

    // -------------------------------------------------------------------------------------
    // 5.1 FILTRAGEM DE RUÍDO (MULTI-ESTÁGIO)
    // -------------------------------------------------------------------------------------
    // a. Filtro Temporal (Ano >= 2000)
    $ANO_MINIMO = 2000;
    $anoPY = (int)($campos["PY"] ?? 0);
    if ($anoPY > 0 && $anoPY < $ANO_MINIMO) {
        $status = "Ruido";
        $motivoDescarte = "Ano de publicacao anterior a 2000 ($anoPY)";
    }

    // b. Filtro de Retratação
    if ($status === "Valido") {
        $title = strtolower($campos["TI"] ?? "");
        if (strpos($title, "retracted") !== false || strpos($title, "retraction") !== false) {
            $status = "Ruido";
            $motivoDescarte = "Artigo retratado (Retracted / Retraction)";
        }
    }

    // c. Filtro de Termos Nocivos (Noise Dictionary)
    if ($status === "Valido") {
        foreach ($ruidosTermos as $grupo => $termos) {
            foreach ($termos as $termRuido) {
                if (strpos($textoTotal, $termRuido) !== false) {
                    $status = "Ruido";
                    $motivoDescarte = "Termo de ruido detectado no grupo '$grupo': '$termRuido'";
                    break 2;
                }
            }
        }
    }

    // d. Validação Central de IA
    if ($status === "Valido") {
        $ehIA = false;
        foreach ($validadoresIA as $v) {
            if (strpos($textoTotal, $v) !== false) {
                $ehIA = true;
                break;
            }
        }
        if (!$ehIA) {
            $status = "Ruido";
            $motivoDescarte = "Ausencia de termos validadores de Inteligencia Artificial";
        }
    }

    // e. Validação de Domínio Agrícola
    if ($status === "Valido") {
        $ehAgro = false;
        foreach ($validadoresAgro as $a) {
            if (strpos($textoTotal, $a) !== false) {
                $ehAgro = true;
                break;
            }
        }
        if (!$ehAgro) {
            $status = "Ruido";
            $motivoDescarte = "Ausencia de termos validadores do dominio agricola/rural";
        }
    }

    // -------------------------------------------------------------------------------------
    // 5.2 EXTRAÇÃO PADRONIZADA DE PAÍSES
    // -------------------------------------------------------------------------------------
    $pais = "Nao Informado";
    $end = $campos["RP"] ?? $campos["C1"] ?? "";
    if ($end !== "") {
        $parts = explode(",", $end);
        $last = trim(end($parts)); 
        $clean = trim(preg_replace('/[0-9\[\]\.]/', '', $last));
        $pWords = explode(" ", $clean);
        $final = strtoupper(end($pWords));
        
        // Padronização geográfica
        if (strpos($final, "CHINA") !== false) $final = "CHINA";
        elseif (strpos($final, "USA") !== false || strpos($final, "UNITED STATES") !== false) $final = "USA";
        elseif (strpos($final, "BRAZIL") !== false || strpos($final, "BRASIL") !== false) $final = "BRAZIL";
        elseif (strpos($final, "ENGLAND") !== false || strpos($final, "SCOTLAND") !== false || strpos($final, "WALES") !== false || strpos($final, "UK") !== false || strpos($final, "UNITED KINGDOM") !== false) $final = "UNITED KINGDOM";
        elseif (strpos($final, "SPAIN") !== false) $final = "SPAIN";
        elseif (strpos($final, "GERMANY") !== false) $final = "GERMANY";
        elseif (strpos($final, "ITALY") !== false) $final = "ITALY";
        elseif (strpos($final, "INDIA") !== false) $final = "INDIA";
        elseif (strpos($final, "CANADA") !== false) $final = "CANADA";
        elseif (strpos($final, "FRANCE") !== false) $final = "FRANCE";
        elseif (strpos($final, "AUSTRALIA") !== false) $final = "AUSTRALIA";
        elseif (strpos($final, "PORTUGAL") !== false) $final = "PORTUGAL";
        elseif (strpos($final, "JAPAN") !== false) $final = "JAPAN";
        elseif (strpos($final, "NETHERLANDS") !== false) $final = "NETHERLANDS";
            
        $pais = (strlen($final) > 1) ? $final : "Nao Informado";
    }

    // -------------------------------------------------------------------------------------
    // 5.3 MOTOR DE PONTUAÇÃO PONDERADA DE SUBCATEGORIAS
    // -------------------------------------------------------------------------------------
    $subScores = [];
    $catDominante = "Nao Classificado";
    $subCatDominante = "Nao Classificado";

    if ($status === "Valido") {
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
                    $subScores[$subCat] = [
                        'score' => $score,
                        'main' => $mainCat
                    ];
                }
            }
        }

        // Determina a subcategoria dominante
        if (count($subScores) > 0) {
            uasort($subScores, fn($a, $b) => $b['score'] <=> $a['score']);
            
            $subCatDominante = array_key_first($subScores);
            $catDominante = $subScores[$subCatDominante]['main'];
            $estatisticas['Validos_Classificados']++;
        } else {
            // Em caso de não enquadramento nas subcategorias, marca como ruído
            $status = "Ruido";
            $motivoDescarte = "Não enquadrado em nenhuma categoria específica de IA do Campo";
            $estatisticas['Ruidos']++;
            $estatisticas['Motivos_Descarte'][$motivoDescarte] = ($estatisticas['Motivos_Descarte'][$motivoDescarte] ?? 0) + 1;
        }
    } else {
        $estatisticas['Ruidos']++;
        $estatisticas['Motivos_Descarte'][$motivoDescarte] = ($estatisticas['Motivos_Descarte'][$motivoDescarte] ?? 0) + 1;
    }

    // -------------------------------------------------------------------------------------
    // 5.4 ROTEAMENTO E CONSOLIDAÇÃO DAS SAÍDAS
    // -------------------------------------------------------------------------------------
    $wosLimpo = implode(";", array_map(fn($t) => str_replace([";", "\n", "\r"], " ", $campos[$t] ?? ""), $tagsWoS));

    if ($status === "Ruido") {
        $linha = "$ut;$motivoDescarte;$pais;$wosLimpo\n";
        $txtRuidos .= $linha;
    } else {
        $linha = "$ut;$catDominante;$subCatDominante;$pais;$wosLimpo\n";
        $txtClassificados .= $linha;
        
        $txtArestas .= "$pais;$catDominante\n";
        
        $mainCatsMatch = [];
        foreach ($subScores as $info) {
            $mainCatsMatch[$info['main']] = true;
        }
        $vosviewerLinhas[] = implode(";", array_keys($mainCatsMatch));
    }
}

// -----------------------------------------------------------------------------------------
// 6. ESCRITA DOS ARQUIVOS DE SAÍDA (Com caminhos estruturados)
// -----------------------------------------------------------------------------------------
file_put_contents(__DIR__ . '/../resultados/artigos_classificados.csv', $txtClassificados);
file_put_contents(__DIR__ . '/../resultados/artigos_revisar_categoria.csv', $txtPendentes);
file_put_contents(__DIR__ . '/../resultados/artigos_descartados_ruido.csv', $txtRuidos);
file_put_contents(__DIR__ . '/../redes_gephi/arestas_gephi.csv', $txtArestas);

file_put_contents(__DIR__ . '/../redes_vosviewer/vosviewer-categorias.txt', implode("\n", $vosviewerLinhas) . "\n");

// -----------------------------------------------------------------------------------------
// 7. GERAÇÃO AUTOMÁTICA DO TESAURO DO VOSviewer
// -----------------------------------------------------------------------------------------
$tes = "Label\tReplace by\n";
foreach ($categoriasNovas as $c => $subs) {
    foreach ($subs as $sub => $words) {
        foreach ($words as $w) {
            $tes .= $w . "\t" . $c . "\n";
        }
    }
}
file_put_contents(__DIR__ . '/../redes_vosviewer/tesauro.txt', $tes);

// -----------------------------------------------------------------------------------------
// 8. RELATÓRIO DO PROCESSAMENTO
// -----------------------------------------------------------------------------------------
echo "\n=======================================================\n";
echo "       RELATÓRIO DE CLASSIFICAÇÃO - RURAL IA v13.2\n";
echo "=======================================================\n";
echo "Total de blocos processados: " . $totalLidos . "\n";
echo "Artigos Válidos Classificados: " . $estatisticas['Validos_Classificados'] . "\n";
echo "Artigos Válidos Pendentes de Revisão: " . $estatisticas['Validos_Pendentes'] . "\n";
echo "Artigos Descartados (Ruído): " . $estatisticas['Ruidos'] . "\n";
echo "-------------------------------------------------------\n";
echo "Principais Motivos de Descarte:\n";
arsort($estatisticas['Motivos_Descarte']);
foreach (array_slice($estatisticas['Motivos_Descarte'], 0, 5) as $motivo => $qtd) {
    echo " - $motivo: $qtd\n";
}
echo "=======================================================\n";
echo "Arquivos salvos com sucesso nas respectivas pastas!\n";
echo "Utilize as pastas 'redes_vosviewer' e 'redes_gephi' para gerar os gráficos.\n";
