<?php

/**
 * SCRIPT DE PROCESSAMENTO E CLASSIFICAÇÃO DE ARTIGOS CIENTÍFICOS - VERSÃO 12.0 (ESTÁVEL)
 * Autor: Jonas Ernesto Poli
 * Contexto: Projeto de Mestrado - PPGCTS / UFSCar
 * 
 * Descrição Geral:
 * Este script processa um arquivo de texto bruto contendo registros de artigos exportados 
 * da base de dados Web of Science (WoS). Ele foi desenvolvido para identificar, entre milhares 
 * de artigos, quais tratam sobre a aplicação de Inteligência Artificial (IA) no contexto rural.
 * O script valida a presença de termos de IA, atribui uma categoria (dimensão sociotécnica), 
 * extrai o país de origem da pesquisa, e gera planilhas consolidadas além de um arquivo de tesauro 
 * e uma planilha de arestas para análise de redes em softwares como VOSviewer e Gephi.
 */

// -----------------------------------------------------------------------------------------
// 1. DICIONÁRIO DE CATEGORIAS (DIMENSÕES SOCIOTÉCNICAS)
// -----------------------------------------------------------------------------------------
// Este array mapeia as dimensões de análise do projeto a termos técnicos (keywords).
// Após a validação de que o artigo trata realmente de IA, ele passará por essas listas
// para ser designado a uma destas 12 categorias com base na primeira correspondência encontrada.
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

// -----------------------------------------------------------------------------------------
// 2. DICIONÁRIO DE VALIDAÇÃO DE INTELIGÊNCIA ARTIFICIAL
// -----------------------------------------------------------------------------------------
// Lista de termos obrigatórios. Para que o artigo NÃO seja considerado um "Falso Positivo" 
// (Ruído), ele deve obrigatoriamente contar com ao menos um desses termos em seus campos de texto.
$validadoresIA = ['intelligence', 'intelligent', 'machine learning', 'language model', 'neural', 'algorithm', 'vision', 'robot', 'uav', 'drone', 'sensor', 'iot', 'llm', 'genai', 'generative ai', 'gpt', 'transformer', 'automation', 'chatbot', 'assistant', 'agentic', 'conversational', 'virtual agent', 'planning', 'rag'];

// -----------------------------------------------------------------------------------------
// 3. CAMPOS DE METADADOS DA WEB OF SCIENCE
// -----------------------------------------------------------------------------------------
// Códigos dos metadados extraídos pelo modelo da WoS, como: 
// TI (Título), AB (Abstract/Resumo), DE (Author Keywords), CR (Cited References), etc.
$tagsWoS = ["AU", "AF", "TI", "SO", "LA", "DT", "DE", "ID", "AB", "C1", "RP", "EM", "FU", "FX", "CR", "NR", "TC", "PY", "DI", "WC", "SC"];

// -----------------------------------------------------------------------------------------
// 4. LEITURA DO ARQUIVO DE ENTRADA
// -----------------------------------------------------------------------------------------
// Inicializa a leitura do banco de dados consolidado em formato txt.
$input = "todos_arquivos_juntos.txt";
if (!file_exists($input))
    die("Arquivo não encontrado.\n");

// Lê o arquivo todo para a memória (Atenção: requer aumento do memory_limit do PHP se o arquivo for grande)
$raw = file_get_contents($input);

// O padrão estrutural da Web of Science nos permite delimitar onde termina um artigo e onde começa outro 
// usando a string "ER" (End of Record). Com isso, criamos um array de artigos isolados.
$artigos = explode("\nER\n", $raw);

// -----------------------------------------------------------------------------------------
// 5. PREPARAÇÃO DOS ARQUIVOS DE SAÍDA (CABEÇALHO)
// -----------------------------------------------------------------------------------------
// Criação do cabeçalho padrão para arquivos CSV com delimitador ";"
$header = "UT;Classificacao;Pais;" . implode(";", $tagsWoS) . "\n";
$txtClassificados = $header; // Planilha final limpa com artigos perfeitamente categorizados
$txtPendentes = $header;     // Planilha de artigos válidos (são sobre IA), mas que não se encaixaram em nenhuma categoria (para revisão manual)
$txtRuidos = $header;        // Planilha de falsos positivos (não contêm validador de IA ou contêm exceções manuais)

// Criação do cabeçalho simples para a planilha de arestas focada em Gephi
$txtArestas = "Source;Target\n"; 

// -----------------------------------------------------------------------------------------
// 6. PROCESSAMENTO ITERATIVO DE CADA ARTIGO
// -----------------------------------------------------------------------------------------
foreach ($artigos as $bloco) {
    if (trim($bloco) === "")
        continue; // Pula blocos vazios gerados pela quebra (explode)

    // Quebra o bloco atual linha por linha para interpretar as tags de 2 letras (Ex: "TI", "AB")
    $campos = [];
    $linhas = explode("\n", $bloco);
    $tagAtual = "";
    
    foreach ($linhas as $linha) {
        if (strlen($linha) < 3)
            continue;
            
        // Na exportação da WoS, a tag está sempre nos dois primeiros caracteres.
        $tag = substr($linha, 0, 2);
        // O valor começa após a formatação de espaçamento (a partir do caractere 3)
        $val = trim(substr($linha, 3));
        
        if ($tag !== "  ") {
            // Nova tag encontrada. Atualiza a referência atual.
            $tagAtual = $tag;
            $campos[$tagAtual] = $val;
        } else {
            // Se os caracteres não formam uma tag válida e estão em branco, trata-se de uma 
            // continuação do texto da tag anterior (Comum em Abstracts e Títulos muito longos).
            $campos[$tagAtual] .= " " . $val;
        }
    }

    // -------------------------------------------------------------------------------------
    // 6.1 ANÁLISE DE TEXTO E FILTRAGEM DE RUÍDO
    // -------------------------------------------------------------------------------------
    // O texto consolidado para a análise de categorias baseia-se exclusivamente em Título,
    // Palavras-chave dos autores, Keywords Plus e Abstract.
    $textoTotal = strtolower(($campos["TI"] ?? "") . " " . ($campos["DE"] ?? "") . " " . ($campos["ID"] ?? "") . " " . ($campos["AB"] ?? ""));
    $status = "Valido"; // Todos iniciam como presumidamente válidos

    // ---------------------------------------------------------------------------------
    // a. FILTRO TEMPORAL: Ano mínimo de publicação
    // ---------------------------------------------------------------------------------
    // A IA aplicada ao contexto rural como campo científico consistente emerge ~2000.
    // Artigos anteriores a esse marco são invariavelmente falsos positivos oriundos de
    // referências citadas ou metadados herdados da base WoS, não publicações do período.
    $ANO_MINIMO = 2000;
    $anoPY = (int)($campos["PY"] ?? 0);
    if ($anoPY > 0 && $anoPY < $ANO_MINIMO) {
        $status = "Ruido";
    }

    // ---------------------------------------------------------------------------------
    // b. DICIONÁRIO DE TERMOS DE RUÍDO (Noise Dictionary)
    // ---------------------------------------------------------------------------------
    // Array categorizado de termos que identificam inequivocamente falsos positivos.
    // Cada categoria agrupa termos cujo domínio é incompatível com o escopo do projeto
    // (IA rural/agropecuária). A inclusão de um artigo é descartada ao primeiro match.
    //
    // COMO ADICIONAR NOVOS RUÍDOS: basta incluir o termo em minúsculas na categoria
    // correspondente (ou criar uma nova). O loop abaixo aplica automaticamente.
    //
    $ruidosTermos = [

        // Expressões problemáticas históricas e acrônimos ambíguos
        // (RAG = Retrieval-Augmented Generation, mas também gêneros musicais indianos)
        "ambiguidades_acronimos" => [
            'ragtime',          // gênero musical americano (falso positivo via "rag")
            'rag darbari',      // composição musical indiana
            'shukla',           // sobrenome de autor de literatura rural indiana (Rag Darbari)
            'russian researchers', // marcador de artigos com viés geopolítico anômalo
        ],

        // Medicina humana: oncologia, radiologia, cardiologia, neurologia
        // Entram via termos como 'algorithm', 'neural', 'intelligence' em contexto clínico
        "medicina_humana" => [
            'kilovoltage x-ray',        // física radiológica hospitalar
            'radiation therapy',         // radioterapia oncológica
            'mammography',               // diagnóstico por imagem humano
            'colonoscopy',               // procedimento médico
            'endoscopy',                 // procedimento médico
            'serum cholesterol',         // bioquímica clínica humana
            'cerebral infarction',       // acidente vascular cerebral
            'dietary lipids',            // nutrição humana clínica
            'diabetic retinopathy',      // oftalmo-endocrinologia humana (contexto não-rural)
            'chromosome translocation',  // genética médica/citogenética humana
            'calmodulin',                // proteína regulatória médica
            'smooth-muscle myosin',      // fisiologia muscular humana
            'anesthesia',                // medicina cirúrgica
        ],

        // Ecotoxicologia de pesticidas sem componente de IA
        // Entram via termos como 'sensor', 'algorithm' em refs., ou 'intelligent' genérico
        "pesticidas_sem_ia" => [
            'acute toxicity of',         // ensaios de toxicidade pura (sem IA)
            'ecotoxicological effects',   // efeitos ecotoxicológicos de pesticidas
            'fipronil on',               // estudos de toxicidade de fipronil em organismos
            'paraquat influenced',       // herbicida paraquat sem IA
            'herbicide evaluation',      // avaliação agronômica simples de herbicidas
            'weed control in',           // controle de ervas daninhas (sem IA)
            'insecticide formulation',   // formulação química de inseticidas (sem IA)
            'cholinesterase activity',   // biomarcador de exposição a pesticida (sem IA)
        ],

        // Hidrologia e neve sem aplicação de IA agrícola
        "hidrologia_snow" => [
            'snowpack water-equivalent', // estimativa de neve por satélite/aeronave
            'snow indices',              // índices de cobertura de neve
            'snow cover depth',          // profundidade de neve
        ],

        // Microbiologia de alimentos sem IA (segurança alimentar laboratorial)
        "microbiologia_alimentos" => [
            'listeria monocytogenes',    // patógeno alimentar — sem IA
            'haccp',                     // sistema de controle de qualidade alimentar
        ],

        // Veterinária clínica sem IA (medicina animal sem aplicação computacional)
        "veterinaria_clinica" => [
            'anemia detection in broiler to aia', // título específico de artigo ruído
        ],

        // Física pura e geofísica sem relação com agricultura
        "fisica_geofisica" => [
            'chronostratigraphic',       // estratigrafia geológica
            'longithorols',              // compostos químicos naturais específicos
        ],
    ];

    // Loop de verificação: itera sobre todas as categorias e termos do dicionário
    foreach ($ruidosTermos as $categoria => $termos) {
        foreach ($termos as $termRuido) {
            if (strpos($textoTotal, $termRuido) !== false) {
                $status = "Ruido";
                break 2; // Ao primeiro match, encerra ambos os loops
            }
        }
    }

    // ---------------------------------------------------------------------------------
    // c. VALIDAÇÃO CENTRAL DE IA
    // ---------------------------------------------------------------------------------
    // Checa se há base computacional explícita no texto. Artigos que sobreviveram ao
    // filtro de ruídos ainda precisam confirmar presença de IA para serem classificados.
    $ehIA = false;
    foreach ($validadoresIA as $v) {
        if (strpos($textoTotal, $v) !== false) {
            $ehIA = true;
            break;
        }
    }
    // Se nenhum termo da lista validadora for encontrado, reclassifica inexoravelmente como Ruído.
    if (!$ehIA)
        $status = "Ruido";

    // -------------------------------------------------------------------------------------
    // 6.2 CLASSIFICAÇÃO SEMÂNTICA NAS DIMENSÕES SOCIOTÉCNICAS
    // -------------------------------------------------------------------------------------
    $catFinal = "Nao Classificado"; // Estado padrão antes do pareamento com o dicionário
    
    // Processamos os dicionários apenas se o artigo for considerado de fato de Inteligência Artificial (Valido)
    if ($status === "Valido") {
        foreach ($categorias as $nome => $termos) {
            foreach ($termos as $t) {
                if (strpos($textoTotal, $t) !== false) {
                    $catFinal = $nome;
                    break 2; // Ao encontrar a primeira ocorrência emparelhada, paralela a busca e oficializa a categoria
                }
            }
        }
    }

    // -------------------------------------------------------------------------------------
    // 6.3 EXTRAÇÃO PADRONIZADA DO PAÍS (Country Extraction)
    // -------------------------------------------------------------------------------------
    $pais = "Nao Informado";
    // Tenta utilizar prioritariamente o endereço correspondente ("RP"), caso ausente tenta o primeiro endereço da lista ("C1")
    $end = $campos["RP"] ?? $campos["C1"] ?? "";
    if ($end !== "") {
        // Separa os blocos da string de endereço pelo separador natural de vírgula. 
        $parts = explode(",", $end);
        // Pela estrutura padrão da WOS, o último bloco tende a indicar o país (mesmo com CEP).
        $last = trim(end($parts)); 
        // Remove quaisquer numerais pontuações ou traços de código postal e chaves que poluem o país
        $clean = trim(preg_replace('/[0-9\[\]\.]/', '', $last));
        // Isola e seleciona a última palavra contida
        $pWords = explode(" ", $clean);
        $final = strtoupper(end($pWords));
        
        // Exceções para unificar potências cuja grafia frequentemente difere na base WoS
        if (strpos($final, "CHINA") !== false)
            $final = "CHINA";
        if (strpos($final, "USA") !== false)
            $final = "USA";
            
        // Atribui se for uma string válida (não um espaço de resíduo final)
        $pais = (strlen($final) > 1) ? $final : "Nao Informado";
    }

    // -------------------------------------------------------------------------------------
    // 6.4 CONSOLIDAÇÃO E ROTEAMENTO PARA AS SAÍDAS
    // -------------------------------------------------------------------------------------
    // Concatena as strings recriando o padrão CSV com os novos metadados computados (Classificação e País) 
    // mapeando cada tag do CSV para eliminar quebras de linha intrusas e ponto-e-vírgulas originais.
    $linha = ($campos["UT"] ?? "S_UT") . ";" . ($status === "Ruido" ? "Ruido" : $catFinal) . ";" . $pais . ";" . implode(";", array_map(fn($t) => str_replace([";", "\n", "\r"], " ", $campos[$t] ?? ""), $tagsWoS)) . "\n";

    // Adiciona o nó País e o nó Classificação Final para as Arestas que alimentarão processamento de grafos
    $txtArestas .= $pais . ";" . ($status === "Ruido" ? "Ruido" : $catFinal) . "\n";

    // Direciona o artigo processado para o buffer correspondente a depender de seu status
    if ($status === "Ruido")
        $txtRuidos .= $linha;
    elseif ($catFinal === "Nao Classificado")
        $txtPendentes .= $linha;
    else
        $txtClassificados .= $linha;
}

// -----------------------------------------------------------------------------------------
// 7. GERAÇÃO DOS ARQUIVOS ESTRUTURADOS FINAIS
// -----------------------------------------------------------------------------------------
file_put_contents("artigos_classificados.csv", $txtClassificados);       // Base definitiva com dados limpos e classificados
file_put_contents("artigos_revisar_categoria.csv", $txtPendentes); // Base de falsos negativos categóricos
file_put_contents("artigos_descartados_ruido.csv", $txtRuidos);    // Falsos positivos (Agrotóxicos genéricos, história rural sem IA, resenhas, etc.)
file_put_contents("arestas_gephi.csv", $txtArestas);               // Arestas de rede para uso no software Gephi

// -----------------------------------------------------------------------------------------
// 8. GERAÇÃO DO TESAURO PARA O SOFTWARE VOSviewer
// -----------------------------------------------------------------------------------------
// O VOSviewer processa tesauros estritamente gerados por delimitação de tabela (Tabs). 
// Esse arquivo agrupa todas as keywords menores aos seus rótulos oficiais, promovendo a unificação.
$tes = "Label\tReplace by\n"; // \t é o caractere obrigatório de Tabulação
foreach ($categorias as $c => $words) {
    foreach ($words as $w) {
        $tes .= $w . "\t" . $c . "\n";
    }
}
file_put_contents("tesauro.txt", $tes);

// Notificação visual no terminal de que a execução do script foi finalizada sem erros
echo "Finalizado! Script rodou corretamente com documentacao masterizada.\nUtilize o arquivo 'tesauro.txt' no VOSviewer e 'arestas_gephi.csv' no Gephi.\n";