<?php
/**
 * gerar-vosviewer-categorias.php
 * 
 * Gera arquivos de rede para o VOSviewer a partir do arquivo intermediário.
 * Caminhos adaptados para a estrutura de pastas organizada.
 */

$arquivoEntrada = __DIR__ . '/../redes_vosviewer/vosviewer-categorias.txt';

$arquivoMap = __DIR__ . '/../redes_vosviewer/vosviewer-categorias-map.txt';
$arquivoNetwork = __DIR__ . '/../redes_vosviewer/vosviewer-categorias-network.txt';
$arquivoConferencia = __DIR__ . '/../redes_vosviewer/vosviewer-categorias-conferencia.csv';

if (!file_exists($arquivoEntrada)) {
    die("Erro: arquivo '$arquivoEntrada' não encontrado. Execute primeiro 'classifica.php'.\n");
}

$handle = fopen($arquivoEntrada, 'r');
if (!$handle) {
    die("Erro: não foi possível abrir o arquivo vosviewer-categorias.txt\n");
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

    if ($primeiraLinha) {
        $primeiraLinha = false;
        if (mb_strtolower($linha) === 'label') {
            continue;
        }
    }

    $totalLinhas++;

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

                if (!isset($categorias[$categoriaA]) || !isset($categorias[$categoriaB])) {
                    continue;
                }

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
                        'weight' => 0,
                    ];
                }

                $coocorrencias[$chave]['weight']++;
            }
        }
    }
}

fclose($handle);

asort($categorias);

$fpMap = fopen($arquivoMap, 'w');
if (!$fpMap) {
    die("Erro: não foi possível criar o arquivo de mapa.\n");
}

fwrite($fpMap, "id\tlabel\tweight<Occurrences>\n");

foreach ($categorias as $categoria => $id) {
    $label = limparTextoVOSviewer($categoria);
    $peso = $ocorrencias[$categoria] ?? 0;
    fwrite($fpMap, $id . "\t" . $label . "\t" . $peso . "\n");
}

fclose($fpMap);

$fpNetwork = fopen($arquivoNetwork, 'w');
if (!$fpNetwork) {
    die("Erro: não foi possível criar o arquivo de rede.\n");
}

foreach ($coocorrencias as $aresta) {
    if (!in_array($aresta['source'], $categorias, true)) {
        continue;
    }
    if (!in_array($aresta['target'], $categorias, true)) {
        continue;
    }
    fwrite($fpNetwork, $aresta['source'] . "\t" . $aresta['target'] . "\t" . $aresta['weight'] . "\n");
}

fclose($fpNetwork);

$fpConferencia = fopen($arquivoConferencia, 'w');
if (!$fpConferencia) {
    die("Erro: não foi possível criar o arquivo de conferência.\n");
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

echo "\nArquivos gerados com sucesso:\n";
echo "- {$arquivoMap}\n";
echo "- {$arquivoNetwork}\n";
echo "- {$arquivoConferencia}\n";

echo "\nResumo:\n";
echo "Linhas lidas em vosviewer-categorias.txt: {$totalLinhas}\n";
echo "Documentos válidos: {$totalDocumentosValidos}\n";
echo "Documentos com uma categoria: {$totalDocumentosComUmaCategoria}\n";
echo "Documentos com mais de uma categoria: {$totalDocumentosComMaisDeUmaCategoria}\n";
echo "Categorias encontradas: " . count($categorias) . "\n";
echo "Relações entre categorias: " . count($coocorrencias) . "\n";

if (count($idsNetworkInvalidos) > 0) {
    echo "\nAtenção: existem IDs no network que não aparecem no map:\n";
    echo implode(', ', $idsNetworkInvalidos) . "\n";
} else {
    echo "\nConsistência OK: todos os IDs do network existem no map.\n";
}

echo "\nAgora abra no VOSviewer assim:\n";
echo "Create > Create a map based on network data > VOSviewer\n";
echo "Map file: {$arquivoMap}\n";
echo "Network file: {$arquivoNetwork}\n";

function limparTextoVOSviewer(string $texto): string
{
    $texto = trim($texto);
    $texto = str_replace(["\t", "\r", "\n"], ' ', $texto);
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
