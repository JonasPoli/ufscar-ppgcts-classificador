<?php
/**
 * unificar_fontes.php
 * 
 * Unifica todos os arquivos .txt individuais do Web of Science
 * localizados na pasta "../fontes_wos" em um único arquivo unificado "todos_arquivos_juntos.txt".
 */

$dir = __DIR__ . '/../fontes_wos/';
$output = $dir . 'todos_arquivos_juntos.txt';

$files = glob($dir . 'rural_and_AI_*.txt');
if (count($files) === 0) {
    die("Nenhum arquivo 'rural_and_AI_*.txt' encontrado em '$dir'.\n");
}

echo "Unificando " . count($files) . " arquivos...\n";
$outHandle = fopen($output, 'w');

$count = 0;
foreach ($files as $file) {
    $count++;
    echo " -> Processando " . basename($file) . " ($count/" . count($files) . ")\n";
    $content = file_get_contents($file);
    fwrite($outHandle, $content);
    // Garante que haja uma quebra de linha e separador no final de cada arquivo
    if (substr($content, -1) !== "\n") {
        fwrite($outHandle, "\n");
    }
}
fclose($outHandle);
echo "Unificação concluída com sucesso! Arquivo gerado em '$output'.\n";
