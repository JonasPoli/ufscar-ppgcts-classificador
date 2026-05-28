<?php
/**
 * gerar_principais_artigos.php
 * 
 * Script para ler resultados/artigos_classificados.csv, agrupar por subcategoria,
 * ordenar os artigos pelo número de citações (coluna TC - Times Cited) em ordem decrescente,
 * e extrair os 3 artigos mais citados de cada uma das 29 subcategorias.
 * 
 * Gera saídas em CSV e Markdown para fácil incorporação na dissertação.
 * Jonas Ernesto Poli - Mestrado PPGCTS / UFSCar
 */

ini_set('memory_limit', '1024M');

$inputFile = __DIR__ . '/../resultados/artigos_classificados.csv';
$outputCsv = __DIR__ . '/../resultados/principais_artigos_por_subcategoria.csv';
$outputMd = __DIR__ . '/../resultados/principais_artigos_por_subcategoria.md';

echo "Iniciando busca pelos 3 principais artigos por subcategoria...\n";

if (!file_exists($inputFile)) {
    die("Erro: Arquivo '$inputFile' não encontrado. Execute primeiro 'classifica.php'.\n");
}

$handle = fopen($inputFile, 'r');
$header = fgetcsv($handle, 0, ';', '"', "\\");

if (!$header) {
    die("Erro: Cabeçalho do arquivo inválido.\n");
}

// Identifica as posições das colunas necessárias
$utIndex = array_search('UT', $header);
$catIndex = array_search('Categoria_Principal', $header);
$subCatIndex = array_search('Subcategoria', $header);
$tiIndex = array_search('TI', $header); // Title
$auIndex = array_search('AU', $header); // Authors
$pyIndex = array_search('PY', $header); // Year
$tcIndex = array_search('TC', $header); // Times Cited
$diIndex = array_search('DI', $header); // DOI

if ($subCatIndex === false || $tiIndex === false || $tcIndex === false) {
    die("Erro: Colunas cruciais (Subcategoria, TI, TC) não encontradas no arquivo de entrada.\n");
}

$artigosPorSub = [];

// Lê todos os artigos classificados
$totalLidos = 0;
while (($row = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {
    if (count($row) <= max($subCatIndex, $tiIndex, $tcIndex)) {
        continue;
    }

    $subcat = trim($row[$subCatIndex]);
    if ($subcat === '' || $subcat === 'Subcategoria') continue;

    $ut = $utIndex !== false ? trim($row[$utIndex]) : 'Sem UT';
    $cat = $catIndex !== false ? trim($row[$catIndex]) : 'Nao Informada';
    $title = $tiIndex !== false ? trim($row[$tiIndex]) : 'Sem Titulo';
    $authors = $auIndex !== false ? trim($row[$auIndex]) : 'Sem Autores';
    $year = $pyIndex !== false ? trim($row[$pyIndex]) : 'S.D';
    $citations = $tcIndex !== false ? (int)trim($row[$tcIndex]) : 0;
    $doi = $diIndex !== false ? trim($row[$diIndex]) : '';

    if (!isset($artigosPorSub[$subcat])) {
        $artigosPorSub[$subcat] = [];
    }

    $artigosPorSub[$subcat][] = [
        'ut' => $ut,
        'categoria' => $cat,
        'titulo' => $title,
        'autores' => $authors,
        'ano' => $year,
        'citacoes' => $citations,
        'doi' => $doi
    ];
    $totalLidos++;
}
fclose($handle);

echo "Total de artigos válidos carregados: $totalLidos\n";

// Ordena cada subcategoria por citações (decrescente) e seleciona os Top 3
$topArtigos = [];
ksort($artigosPorSub); // Ordena as subcategorias alfabeticamente/numericamente (1.1, 1.2, etc.)

foreach ($artigosPorSub as $subcat => $lista) {
    // Ordena por citações (decrescente). Se empatar, ordena por ano (decrescente)
    usort($lista, function($a, $b) {
        if ($b['citacoes'] === $a['citacoes']) {
            return (int)$b['ano'] <=> (int)$a['ano'];
        }
        return $b['citacoes'] <=> $a['citacoes'];
    });

    // Pega os 3 primeiros
    $topArtigos[$subcat] = array_slice($lista, 0, 3);
}

// -----------------------------------------------------------------------------------------
// 1. GERAÇÃO DO ARQUIVO CSV
// -----------------------------------------------------------------------------------------
$fpCsv = fopen($outputCsv, 'w');
fputcsv($fpCsv, ['Categoria', 'Subcategoria', 'Posicao', 'ID_WoS_UT', 'Titulo', 'Autores', 'Ano', 'Citacoes_WoS', 'DOI'], ';', '"', "\\");

foreach ($topArtigos as $subcat => $lista) {
    $pos = 1;
    foreach ($lista as $art) {
        fputcsv($fpCsv, [
            $art['categoria'],
            $subcat,
            $pos,
            $art['ut'],
            $art['titulo'],
            $art['autores'],
            $art['ano'],
            $art['citacoes'],
            $art['doi']
        ], ';', '"', "\\");
        $pos++;
    }
}
fclose($fpCsv);
echo "Arquivo CSV gerado com sucesso: $outputCsv\n";

// -----------------------------------------------------------------------------------------
// 2. GERAÇÃO DO ARQUIVO MARKDOWN (Tabela para a Dissertação)
// -----------------------------------------------------------------------------------------
$mdContent = "# Principais Artigos Científicos por Subcategoria (Top 3 Citações)\n\n";
$mdContent .= "Este documento apresenta os 3 artigos mais citados na base Web of Science (WoS) para cada uma das subcategorias do classificador sociotécnico de IA no campo.\n\n";

$categoriaAtual = "";

foreach ($topArtigos as $subcat => $lista) {
    $categoriaPai = count($lista) > 0 ? $lista[0]['categoria'] : "Outra";
    
    if ($categoriaPai !== $categoriaAtual) {
        $categoriaAtual = $categoriaPai;
        $mdContent .= "## Categoria: $categoriaAtual\n\n";
    }

    $mdContent .= "### Subcategoria: $subcat\n\n";
    
    if (count($lista) === 0) {
        $mdContent .= "*Nenhum artigo classificado nesta subcategoria.*\n\n";
        continue;
    }

    $mdContent .= "| Pos | Citações | Ano | ID WoS (UT) | Título do Artigo | Autores | DOI |\n";
    $mdContent .= "| :---: | :---: | :---: | :---: | :--- | :--- | :--- |\n";

    $pos = 1;
    foreach ($lista as $art) {
        $tituloLimitado = $art['titulo'];
        // Limita tamanho do título na tabela Markdown para não quebrar o layout
        if (strlen($tituloLimitado) > 130) {
            $tituloLimitado = substr($tituloLimitado, 0, 127) . "...";
        }
        
        $autoresLimitados = $art['autores'];
        if (strlen($autoresLimitados) > 60) {
            $autoresLimitados = substr($autoresLimitados, 0, 57) . "...";
        }

        $doiLink = $art['doi'] !== '' ? "[{$art['doi']}](https://doi.org/{$art['doi']})" : "N/A";

        $mdContent .= "| **$pos** | {$art['citacoes']} | {$art['ano']} | `{$art['ut']}` | $tituloLimitado | $autoresLimitados | $doiLink |\n";
        $pos++;
    }
    $mdContent .= "\n";
}

file_put_contents($outputMd, $mdContent);
echo "Arquivo Markdown gerado com sucesso: $outputMd\n";
echo "Concluído!\n";
