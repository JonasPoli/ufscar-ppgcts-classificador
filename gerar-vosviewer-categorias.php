<?php

/**
 * Gera arquivos de rede para o VOSviewer a partir do arquivo:
 *
 * - vosviewer-categorias.txt
 *
 * Entrada esperada:
 *
 * label
 * IA: Arquiteturas e Metodos;IoT e Sensores
 * IA: Arquiteturas e Metodos;Visao Computacional e Fenotipagem
 * Clima, Solo e Meio Ambiente;Recursos Hidricos
 *
 * Saídas:
 *
 * - vosviewer-categorias-map.txt
 * - vosviewer-categorias-network.txt
 * - vosviewer-categorias-conferencia.csv
 *
 * Como abrir no VOSviewer:
 *
 * Create > Create a map based on network data > VOSviewer
 *
 * Map file:
 * vosviewer-categorias-map.txt
 *
 * Network file:
 * vosviewer-categorias-network.txt
 */

$baseDir = __DIR__;

$arquivoEntrada = $baseDir . '/vosviewer-categorias.txt';

$arquivoMap = $baseDir . '/vosviewer-categorias-map.txt';
$arquivoNetwork = $baseDir . '/vosviewer-categorias-network.txt';
$arquivoConferencia = $baseDir . '/vosviewer-categorias-conferencia.csv';

if (!file_exists($arquivoEntrada)) {
    die("Erro: arquivo vosviewer-categorias.txt não encontrado." . PHP_EOL .
        "Rode primeiro: php gerar-vosviewer-categorias.php" . PHP_EOL);
}

$handle = fopen($arquivoEntrada, 'r');

if (!$handle) {
    die("Erro: não foi possível abrir o arquivo vosviewer-categorias.txt" . PHP_EOL);
}

$categorias = [];
$ocorrencias = [];
$coocorrencias = [];

$totalLinhas = 0;
$totalDocumentosValidos = 0;
$totalDocumentosComUmaCategoria = 0;
$totalDocumentosComMaisDeUmaCategoria = 0;

$primeiraLinha = true;

while (($linha = fgets($handle)) !== false) {
    $linha = trim($linha);

    if ($linha === '') {
        continue;
    }

    /**
     * Ignora o cabeçalho do arquivo intermediário.
     */
    if ($primeiraLinha) {
        $primeiraLinha = false;

        if (mb_strtolower($linha) === 'label') {
            continue;
        }
    }

    $totalLinhas++;

    /**
     * Cada linha representa um artigo.
     * Cada categoria está separada por ponto e vírgula.
     */
    $itens = explode(';', $linha);

    $itens = array_map('trim', $itens);

    $itens = array_filter($itens, function ($item) {
        return $item !== '';
    });

    $itens = array_values(array_unique($itens));

    if (count($itens) === 0) {
        continue;
    }

    $totalDocumentosValidos++;

    if (count($itens) === 1) {
        $totalDocumentosComUmaCategoria++;
    }

    if (count($itens) > 1) {
        $totalDocumentosComMaisDeUmaCategoria++;
    }

    /**
     * Registra as categorias e conta ocorrências.
     */
    foreach ($itens as $categoria) {
        if (!isset($categorias[$categoria])) {
            $categorias[$categoria] = count($categorias) + 1;
            $ocorrencias[$categoria] = 0;
        }

        $ocorrencias[$categoria]++;
    }

    /**
     * Gera as coocorrências.
     *
     * Se um artigo tem:
     * A;B;C
     *
     * então gera:
     * A-B
     * A-C
     * B-C
     */
    if (count($itens) > 1) {
        for ($i = 0; $i < count($itens); $i++) {
            for ($j = $i + 1; $j < count($itens); $j++) {
                $categoriaA = $itens[$i];
                $categoriaB = $itens[$j];

                if (!isset($categorias[$categoriaA]) || !isset($categorias[$categoriaB])) {
                    continue;
                }

                $idA = $categorias[$categoriaA];
                $idB = $categorias[$categoriaB];

                /**
                 * Garante que a relação 1-2 e 2-1 sejam tratadas como a mesma.
                 */
                if ($idA > $idB) {
                    [$idA, $idB] = [$idB, $idA];
                }

                $chave = $idA . '-' . $idB;

                if (!isset($coocorrencias[$chave])) {
                    $coocorrencias[$chave] = [
                        'source' => $idA,
                        'target' => $idB,
                        'weight' => 0,
                    ];
                }

                $coocorrencias[$chave]['weight']++;
            }
        }
    }
}

fclose($handle);

/**
 * Ordena categorias por ID.
 */
asort($categorias);

/**
 * Gera arquivo MAP.
 *
 * Este arquivo pode ter cabeçalho.
 */
$fpMap = fopen($arquivoMap, 'w');

if (!$fpMap) {
    die("Erro: não foi possível criar o arquivo de mapa." . PHP_EOL);
}

fwrite($fpMap, "id\tlabel\tweight<Occurrences>\n");

foreach ($categorias as $categoria => $id) {
    $label = limparTextoVOSviewer($categoria);
    $peso = $ocorrencias[$categoria] ?? 0;

    fwrite($fpMap, $id . "\t" . $label . "\t" . $peso . "\n");
}

fclose($fpMap);

/**
 * Gera arquivo NETWORK.
 *
 * IMPORTANTE:
 * Para esta forma de importação no VOSviewer, o arquivo network NÃO deve ter cabeçalho.
 *
 * Correto:
 * 1    2    50
 * 1    3    20
 *
 * Errado:
 * source    target    weight
 * 1         2         50
 */
$fpNetwork = fopen($arquivoNetwork, 'w');

if (!$fpNetwork) {
    die("Erro: não foi possível criar o arquivo de rede." . PHP_EOL);
}

foreach ($coocorrencias as $aresta) {
    /**
     * Segurança extra:
     * só grava aresta se os dois IDs existirem no arquivo map.
     */
    if (!in_array($aresta['source'], $categorias, true)) {
        continue;
    }

    if (!in_array($aresta['target'], $categorias, true)) {
        continue;
    }

    fwrite(
        $fpNetwork,
        $aresta['source'] . "\t" .
        $aresta['target'] . "\t" .
        $aresta['weight'] . "\n"
    );
}

fclose($fpNetwork);

/**
 * Gera arquivo de conferência para abrir no Excel/LibreOffice/Google Sheets.
 */
$fpConferencia = fopen($arquivoConferencia, 'w');

if (!$fpConferencia) {
    die("Erro: não foi possível criar o arquivo de conferência." . PHP_EOL);
}

fwrite($fpConferencia, "ID;Categoria;Ocorrencias\n");

foreach ($categorias as $categoria => $id) {
    fwrite($fpConferencia, csvLinha([
        $id,
        $categoria,
        $ocorrencias[$categoria] ?? 0,
    ]) . PHP_EOL);
}

fclose($fpConferencia);

/**
 * Conferência final de consistência:
 * verifica se todos os IDs usados no network existem no map.
 */
$idsMap = array_values($categorias);
$idsNetworkInvalidos = [];

foreach ($coocorrencias as $aresta) {
    if (!in_array($aresta['source'], $idsMap, true)) {
        $idsNetworkInvalidos[] = $aresta['source'];
    }

    if (!in_array($aresta['target'], $idsMap, true)) {
        $idsNetworkInvalidos[] = $aresta['target'];
    }
}

$idsNetworkInvalidos = array_values(array_unique($idsNetworkInvalidos));

echo PHP_EOL;
echo "Arquivos gerados com sucesso:" . PHP_EOL;
echo "- {$arquivoMap}" . PHP_EOL;
echo "- {$arquivoNetwork}" . PHP_EOL;
echo "- {$arquivoConferencia}" . PHP_EOL;

echo PHP_EOL;
echo "Resumo:" . PHP_EOL;
echo "Linhas lidas em vosviewer-categorias.txt: {$totalLinhas}" . PHP_EOL;
echo "Documentos válidos: {$totalDocumentosValidos}" . PHP_EOL;
echo "Documentos com uma categoria: {$totalDocumentosComUmaCategoria}" . PHP_EOL;
echo "Documentos com mais de uma categoria: {$totalDocumentosComMaisDeUmaCategoria}" . PHP_EOL;
echo "Categorias encontradas: " . count($categorias) . PHP_EOL;
echo "Relações entre categorias: " . count($coocorrencias) . PHP_EOL;

if (count($idsNetworkInvalidos) > 0) {
    echo PHP_EOL;
    echo "Atenção: existem IDs no network que não aparecem no map:" . PHP_EOL;
    echo implode(', ', $idsNetworkInvalidos) . PHP_EOL;
} else {
    echo PHP_EOL;
    echo "Consistência OK: todos os IDs do network existem no map." . PHP_EOL;
}

echo PHP_EOL;
echo "Agora abra no VOSviewer assim:" . PHP_EOL;
echo "Create > Create a map based on network data > VOSviewer" . PHP_EOL;
echo "Map file: {$arquivoMap}" . PHP_EOL;
echo "Network file: {$arquivoNetwork}" . PHP_EOL;

function limparTextoVOSviewer(string $texto): string
{
    $texto = trim($texto);

    /**
     * Evita que tabs ou quebras de linha quebrem o formato tabulado.
     */
    $texto = str_replace(["\t", "\r", "\n"], ' ', $texto);

    /**
     * Reduz espaços duplicados.
     */
    $texto = preg_replace('/\s+/', ' ', $texto);

    return $texto;
}

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