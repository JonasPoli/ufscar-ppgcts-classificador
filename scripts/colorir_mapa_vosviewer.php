<?php
/**
 * colorir_mapa_vosviewer.php
 * 
 * Este script lê o arquivo de mapa original salvo pelo VOSviewer (que contém
 * centenas de palavras-chave pulverizadas) e colore cada nó de acordo com as
 * 10 Categorias Sociotécnicas do config_categorias.php.
 * 
 * Caminhos de entrada e saída adaptados para a estrutura de pastas organizada.
 */

require_once __DIR__ . '/config_categorias.php';

$mapInput = __DIR__ . '/../redes_vosviewer/mapa_original.txt';
$mapOutput = __DIR__ . '/../redes_vosviewer/mapa_original_colorido.txt';

if (!file_exists($mapInput)) {
    echo "=======================================================\n";
    echo "   ATENÇÃO: ARQUIVO '$mapInput' NÃO ENCONTRADO!\n";
    echo "=======================================================\n";
    echo "Para fazer este fluxo funcionar:\n";
    echo " 1. No VOSviewer, crie o mapa pulverizado original a partir de todos_arquivos_juntos.txt.\n";
    echo " 2. Na aba lateral esquerda (aba File), clique no botão 'Save'.\n";
    echo " 3. Salve o arquivo de mapa com o nome 'mapa_original.txt' na pasta 'redes_vosviewer'.\n";
    echo " 4. Execute este script novamente: php scripts/colorir_mapa_vosviewer.php\n";
    echo "=======================================================\n";
    exit(1);
}

echo "Lendo e colorindo '$mapInput'...\n";

$lines = file($mapInput, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$header = explode("\t", $lines[0]);

$labelIndex = array_search('label', $header);
$clusterIndex = array_search('cluster', $header);

if ($labelIndex === false || $clusterIndex === false) {
    die("Erro: O arquivo de mapa '$mapInput' não possui as colunas 'label' ou 'cluster' no cabeçalho.\n");
}

$outputLines = [];
$outputLines[] = implode("\t", $header);

$termosMapeadosCount = 0;
$termosNeutrosCount = 0;

$blacklistCurta = ['ai', 'iot', 'uav', 'csa', 'aei', 'fmis', 'vra', 'rag'];

// Função semântica inteligente para associar uma palavra-chave do VOSviewer a uma categoria
function obterClusterParaTermo($termo, $categoriasNovas, $blacklistCurta) {
    $termo = trim(strtolower($termo));
    
    $catIndex = 1;
    foreach ($categoriasNovas as $mainCat => $subs) {
        foreach ($subs as $subCat => $keywords) {
            foreach ($keywords as $w) {
                $w = trim(strtolower($w));
                
                // Se for um acrônimo curto, exige correspondência exata
                if (in_array($w, $blacklistCurta)) {
                    if ($termo === $w) return $catIndex;
                    continue;
                }
                
                // Correspondência de substring bidirecional inteligente
                if (strpos($termo, $w) !== false || strpos($w, $termo) !== false) {
                    if (strlen($termo) >= 4 && strlen($w) >= 4) {
                        return $catIndex;
                    }
                }
            }
        }
        $catIndex++;
    }
    return 11; // Cluster 11: Neutro/Geral
}

for ($i = 1; $i < count($lines); $i++) {
    $row = explode("\t", $lines[$i]);
    $label = $row[$labelIndex];
    
    $cluster = obterClusterParaTermo($label, $categoriasNovas, $blacklistCurta);
    
    if ($cluster !== 11) {
        $termosMapeadosCount++;
    } else {
        $termosNeutrosCount++;
    }
    
    $row[$clusterIndex] = $cluster;
    $outputLines[] = implode("\t", $row);
}

file_put_contents($mapOutput, implode("\n", $outputLines) . "\n");

echo "=======================================================\n";
echo "       PROCESSAMENTO CONCLUÍDO COM SUCESSO!\n";
echo "=======================================================\n";
echo "Arquivo gerado: '$mapOutput'\n";
echo "Termos mapeados nas 10 categorias: $termosMapeadosCount\n";
echo "Termos gerais/neutros (Cluster 11): $termosNeutrosCount\n";
echo "-------------------------------------------------------\n";
echo "COMO ABRIR AGORA NO VOSviewer:\n";
echo " 1. Clique em 'Create' > 'Create a map based on network data' > 'Next'.\n";
echo " 2. Selecione:\n";
echo "     - Map file: '$mapOutput'\n";
echo "     - Network file: o arquivo de rede original salvo pelo VOSviewer (ex: rede_original.txt)\n";
echo " 3. Conclua e veja o gráfico pulverizado incrivelmente colorido pelas categorias!\n";
echo "=======================================================\n";
