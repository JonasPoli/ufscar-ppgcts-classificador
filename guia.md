# Guia Técnico: Script de Classificação Sociotécnica

Este script, uma ferramenta de automação em PHP, foi criado para processar grandes volumes de dados científicos. Ele facilita a organização da sua dissertação ao separar tecnologias de inteligência artificial aplicadas à agricultura em categorias específicas.

## como foi gerado
Este arquivo todos_arquivos_juntos.txt foi gerado com base na seguinte busca na Web of Science:
TS=("Rural*" OR "Rural*" OR "Rural" OR "Agricultural" OR "Agricultural" OR "Agricultural*"  OR "Farm*" OR "Agricultural*" OR "Rural*" OR "Agro" OR "agriculture") AND TS=("A.I." OR "Artificial intelligence" OR "Chatbot*" OR "Generative AI" OR "GenAI" OR "Retrieval-Augmented Generation" OR "RAG" OR "Conversational agent*" OR "Virtual assistant*" OR "Large language model*" OR "LLM*" OR "ChatGPT" ) 

Logo você deve entender que são artigos científicos que possuem como relação serem inteligencias artificiais aplicada ao trabalho no campo/rural.

### 1. Objetivo Principal
O script lê o arquivo `todos_arquivos_juntos.txt`, exportado da Web of Science, e realiza três tarefas automáticas:
* **Validação:** Identifica se o artigo trata realmente de Inteligência Artificial ou se é apenas um ruído (como inseminação artificial ou artigos históricos).
* **Classificação:** Distribui os artigos válidos em 12 categorias sociotécnicas pré-definidas.
* **Limpeza Geográfica:** Padroniza o nome dos países, removendo códigos postais e siglas de estados.


### 2. Funcionamento das Categorias
O código utiliza um dicionário de palavras-chave para varrer o título, o resumo e as palavras-chave de cada artigo. As 12 categorias são:

1.  **Assistência Virtual e Educação:** Focada em chatbots e ferramentas de ensino.
2.  **Diagnóstico e Proteção Fitossanitaria:** Identificação de pragas, doenças e uso de herbicidas.
3.  **Visão Computacional e Fenotipagem:** Processamento de imagens e sensores espectrais.
4.  **Robótica e Mecanização:** Drones, tratores autônomos e automação de colheita.
5.  **Cadeia Produtiva e Qualidade:** Rastreabilidade, logística e segurança alimentar.
6.  **Mercado Risco e Previsão:** Análise econômica e predição de preços.
7.  **Governança, Gestão e Impacto Social:** Ética, aceitação tecnológica e políticas públicas.
8.  **Clima, Solo e Meio Ambiente:** Sustentabilidade, emissões de carbono e ecologia.
9.  **Recursos Hídricos:** Gestão de irrigação e umidade do solo.
10. **IoT e Sensores:** Redes de sensores sem fio e segurança cibernética.
11. **Agricultura de Precisão e Cultivo:** Monitoramento de safras e fertilização variável.
12. **IA: Arquiteturas e Métodos:** Modelos de linguagem (LLM), redes neurais e algoritmos básicos.

### 3. Arquivos Gerados
Ao rodar o comando `php classifica.php`, o script cria quatro arquivos na sua pasta:

* **artigos_classificados.csv:** É a sua base de dados principal. Contém todos os artigos úteis para a sua dissertação, já categorizados e com os países limpos. Pode ser aberto diretamente no Excel.
* **tesauro.txt:** Este arquivo, configurado com separação por tabulação, serve exclusivamente para o VOSviewer. Ele agrupa automaticamente milhares de termos técnicos nas suas 12 categorias dentro do mapa de redes.
* **artigos_revisar_categoria.csv:** Contém artigos que passaram no teste de IA, mas que o robô não soube em qual das 12 categorias encaixar. Exige uma rápida revisão manual.
* **artigos_descartados_ruido.csv:** Reúne tudo o que o script considerou irrelevante (falsos positivos). Isso inclui artigos sobre inseminação artificial biológica, história antiga ou química pura.

### 4. Importância para o PPGCTS
O uso deste script garante o rigor científico da sua pesquisa. Ele permite que você analise a trajetória da tecnologia sob uma ótica sociotécnica, separando o desenvolvimento puramente técnico dos impactos na governança e na sociedade rural.

### 5. Como Executar
1. Certifique-se de que o arquivo `todos_arquivos_juntos.txt` está na mesma pasta do script.
2. Abra o terminal do seu Mac.
3. Digite `php classifica.php` e aperte Enter.
4. Os resultados estarão prontos para serem importados no Excel e no VOSviewer.