<?php
/**
 * gerar-gephi-categorias.php
 * 
 * Gera arquivos para o Gephi a partir do arquivo intermediário.
 * Caminhos adaptados para a estrutura de pastas organizada.
 */

$arquivoEntrada = __DIR__ . '/../redes_vosviewer/vosviewer-categorias.txt';

$arquivoNodes = __DIR__ . '/../redes_gephi/gephi-categorias-nodes.csv';
$arquivoEdges = __DIR__ . '/../redes_gephi/gephi-categorias-edges.csv';

if (!file_exists($arquivoEntrada)) {
    die("Erro: arquivo '$arquivoEntrada' não encontrado. Execute primeiro 'classifica.php'.\n");
}

$handle = fopen($arquivoEntrada, 'r');
if (!$handle) {
    die("Erro: não foi possível abrir vosviewer-categorias.txt\n");
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

$fpNodes = fopen($arquivoNodes, 'w');
if (!$fpNodes) {
    die("Erro: não foi possível criar gephi-categorias-nodes.csv\n");
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

$fpEdges = fopen($arquivoEdges, 'w');
if (!$fpEdges) {
    die("Erro: não foi possível criar gephi-categorias-edges.csv\n");
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

echo "Arquivos para Gephi gerados com sucesso:\n";
echo "- {$arquivoNodes}\n";
echo "- {$arquivoEdges}\n";
echo "\nCategorias: " . count($categorias) . "\n";
echo "Relações: " . count($coocorrencias) . "\n";

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
