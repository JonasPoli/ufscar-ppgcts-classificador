# UFSCar PPGCTS - Classificador Sociotécnico de IA no Campo (v14.0)

Este repositório contém a infraestrutura e os scripts em PHP desenvolvidos para a dissertação de mestrado (PPGCTS / UFSCar) de **Jonas Ernesto Poli**. O sistema realiza a classificação automatizada e a análise sociotécnica de grandes volumes de artigos científicos extraídos da base **Web of Science (WoS)**.

O classificador foi projetado seguindo preceitos da sociologia da tecnologia, organizando a pesquisa em **10 Categorias Sociotécnicas Principais** e **31 Subcategorias Específicas** (29 delas atribuídas como subcategoria dominante a pelo menos um registro), permitindo visualizar graficamente a coocorrência e a dinâmica geográfica e temporal da Inteligência Artificial aplicada ao trabalho rural.

---

## 📂 Estrutura de Pastas e Arquivos

O projeto está estritamente organizado em uma estrutura modular:

```text
ufscar-ppgcts-classificador/
├── fontes_wos/              # Arquivos TXT originais exportados do Web of Science
│   ├── rural_and_AI_01.txt a rural_and_AI_17_8500.txt (17 arquivos, 10.461 registros)
│   └── todos_arquivos_juntos.txt  # Base de dados unificada (gerada automaticamente)
│
├── scripts/                 # Scripts PHP de processamento e análise
│   ├── config_categorias.php           # Dicionários (categorias, IA, Agro, Ruídos) e regras comuns de correspondência, triagem e pontuação
│   ├── unificar_fontes.php            # Une os arquivos TXT individuais da WoS
│   ├── classifica.php                  # Classificador principal binário (Válidos vs Ruído)
│   ├── gerar_resumo.php                # Consolida as contagens de classificações
│   ├── gerar_pivot_ano.php             # Cria a matriz histórico-temporal (Subcategoria x Ano)
│   ├── gerar-vosviewer-categorias.php  # Gera rede de coocorrência de 10 categorias para VOSviewer
│   ├── gerar-gephi-categorias.php     # Gera nós/arestas de coocorrência para Gephi
│   ├── gerar_mapa_palavras_categorias.php # Gera rede pulverizada das palavras-chave mapeadas
│   ├── gerar_vosviewer_subcategorias.php # Gera a rede de coocorrência das subcategorias
│   └── colorir_mapa_vosviewer.php     # Pós-processador inteligente que colore mapas exportados do VOSviewer
│
├── resultados/              # Planilhas CSV geradas para análise quantitativa
│   ├── artigos_classificados.csv      # Base limpa de artigos classificados (gerada localmente pelo classifica.php; não versionada)
│   ├── artigos_descartados_ruido.csv  # Artigos descartados com justificativa detalhada
│   ├── contagem_classificacoes.csv    # Resumo quantitativo de categorias e subcategorias
│   └── classificacoes_por_ano.csv     # Matriz pivotada temporal
│
├── redes_vosviewer/         # Arquivos de entrada e saída para geração de gráficos no VOSviewer
│   ├── vosviewer-categorias-map.txt & vosviewer-categorias-network.txt
│   ├── vosviewer-subcategorias-map.txt & vosviewer-subcategorias-network.txt
│   ├── vosviewer-keywords-map.txt & vosviewer-keywords-network.txt
│   ├── mapa_original.txt              # Exportado do VOSviewer (opcional, para coloração pulverizada)
│   ├── mapa_original_colorido.txt     # Gerado pelo pós-processador
│   └── tesauro.txt                    # Dicionário de sinônimos/substituições estruturado para VOSviewer
│
├── redes_gephi/             # Arquivos CSV estruturados para análise no Gephi
│   ├── gephi-categorias-nodes.csv & gephi-categorias-edges.csv
│   └── arestas_gephi.csv              # Rede Geográfica (País -> Categoria)
│
└── README.md                # Este manual de instruções
```

---

## 🛠️ Como Funciona o Classificador (Fluxo Científico)

O classificador utiliza um método de **pontuação ponderada e filtragem multi-estágio**. Todas as regras ficam em `scripts/config_categorias.php`, de modo que todos os programas aplicam os mesmos critérios.

**Regra de correspondência dos termos (válida em todas as etapas):** os termos são procurados a partir do início das palavras (`crop` encontra `crops` e `cropland`, mas não `outcrop`). As siglas curtas, de até três caracteres (como `rag`, `llm` e `uav`), valem apenas como palavra inteira, admitido o plural em "s" (`llms`, `uavs`), para evitar correspondências espúrias, como a de `rag` dentro de `average` ou `storage`.

1. **Filtro Temporal:** Apenas artigos publicados do ano **2000** em diante são considerados.
2. **Filtro de Retratações:** Artigos contendo `"retracted"` ou `"retraction"` no título são automaticamente descartados.
3. **Filtro Semântico de Ruído:** O script busca os 78 termos do dicionário de ruídos, agrupados em doze frentes de exclusão em `config_categorias.php` (como medicina humana, energia eólica e solar, macroeconomia sem IA no campo), para remover falsos positivos.
4. **Validação de IA e Domínio Agrícola:** Exige a co-presença de pelo menos um validador de IA (ex: `machine learning`, `neural`, `robot`, `sensor`) e um validador de domínio agrícola (ex: `crop`, `soil`, `pasture`, `livestock`).
5. **Score de Subcategoria:** O título (peso 3), palavras-chave do autor (peso 2), palavras-chave do sistema (peso 2) e resumo (peso 1) são escaneados. O artigo é classificado na **Subcategoria** com a maior pontuação acumulada, que herda sua **Categoria Principal**. Caso nenhuma pontuação seja maior que zero, o artigo é descartado como ruído.
6. **Padronização de Países:** Extrai o país de afiliação do autor e padroniza geograficamente (ex: unifica "United States", "USA", "U.S.A." em "USA", unifica grafias de "China", "Brazil", "Germany", etc.).

---

## 📊 Resultado da versão final (coleta na WoS em 17 de abril de 2026)

Estes são os números relatados na dissertação e reproduzidos pela execução dos scripts deste repositório:

| Etapa | Registros |
|---|---:|
| Registros exportados da WoS | 10.461 |
| Descartados: anteriores a 2000 | 252 |
| Descartados: retratados | 37 |
| Descartados: dicionário de ruídos (10 das 12 frentes com ocorrência; energia eólica e solar 401, medicina humana 234) | 723 |
| Descartados: sem termos de IA | 916 |
| Descartados: sem termos do domínio agrícola | 0 |
| Descartados: sem pontuação em nenhuma subcategoria | 2.136 |
| **Classificados** | **6.397 (61,2%)** |

As contagens por categoria e subcategoria estão em `resultados/contagem_classificacoes.csv`. Das 31 subcategorias do dicionário, 29 aparecem como subcategoria dominante; *6.2. Controle de estoque e insumos* e *7.2. Diagnóstico por imagem* pontuam em alguns artigos (e por isso aparecem como nós na rede de coocorrência), mas em nenhum deles obtêm a maior pontuação.

### Mudança em relação à v13.2

A v13.2 procurava os termos como simples trechos de texto (`strpos`), o que gerava correspondências espúrias (por exemplo, `rag` dentro de `average` e `storage`) e resultava em 7.122 registros classificados. A v14.0 aplica a regra de correspondência descrita acima, ignora o marcador `EF` de fim de arquivo na contagem de registros e faz a rede de subcategorias usar exatamente a mesma triagem do classificador.

---

## 📈 Passo a Passo para Execução Completa

Abra o terminal do seu Mac, navegue até a pasta do projeto e execute os scripts na ordem lógica abaixo:

### Passo 1: Unificar as fontes brutas
Caso tenha baixado novos arquivos exportados em formato de texto delimitado por tabulação (Plain Text) do Web of Science, salve-os na pasta `fontes_wos/` nomeando-os como `rural_and_AI_xx.txt`. Então, execute:
```bash
php scripts/unificar_fontes.php
```
*Isso criará o arquivo unificado `fontes_wos/todos_arquivos_juntos.txt` contendo todos os registros agrupados.*

### Passo 2: Executar a classificação sociotécnica
Para rodar o classificador binário sobre a base de dados unificada:
```bash
php scripts/classifica.php
```
*Isso gerará os artigos válidos e classificados, os ruídos descartados, o tesauro estruturado e os logs completos de processamento.*

### Passo 3: Gerar relatórios e planilhas estatísticas
Para calcular as distribuições quantitativas absolutas e as tendências históricas por ano:
```bash
php scripts/gerar_resumo.php
php scripts/gerar_pivot_ano.php
```
*Gera arquivos CSV prontos na pasta `resultados/`, ideais para abrir no Excel ou gerar gráficos de barras e tabelas dinâmicas para a dissertação.*

### Passo 4: Gerar os arquivos de rede e visualizações gráficas
O repositório oferece três tipos diferentes de rede para justificar e ilustrar a classificação no **VOSviewer** e **Gephi**:

#### A. Rede de 10 Categorias Principais (VOSviewer & Gephi)
Para analisar a coocorrência agregada das 10 categorias sociotécnicas:
```bash
php scripts/gerar-vosviewer-categorias.php
php scripts/gerar-gephi-categorias.php
```

#### B. Rede Acadêmica das Subcategorias (A Mais Recomendada para Validação)
Esta visualização é a mais robusta academicamente, exibindo as subcategorias como nós (31, pois todas pontuam em ao menos um artigo classificado) interconectados pela coocorrência de artigos, coloridos automaticamente em conformidade com suas 10 categorias mãe:
```bash
php scripts/gerar_vosviewer_subcategorias.php
```
* **No VOSviewer:** Clique em `Create` > `Create a map based on network data` > Selecione os arquivos `redes_vosviewer/vosviewer-subcategorias-map.txt` e `redes_vosviewer/vosviewer-subcategorias-network.txt` > Conclua.

#### C. Rede Semântica de Palavras-Chave Ponderadas
Para gerar um mapa contendo todas as palavras-chave do dicionário sociotécnico colorido por categoria:
```bash
php scripts/gerar_mapa_palavras_categorias.php
```
* **No VOSviewer:** Selecione os arquivos `redes_vosviewer/vosviewer-keywords-map.txt` e `redes_vosviewer/vosviewer-keywords-network.txt`.

#### D. Pós-Processador e Colorizador Semântico (Abordagem Premium)
Se você gerou um mapa natural e pulverizado diretamente no VOSviewer contendo milhares de termos livres a partir do arquivo `todos_arquivos_juntos.txt` e deseja que esses termos sejam coloridos automaticamente pelas 10 categorias sociotécnicas (agrupando termos irrelevantes em uma cor de fundo cinza/neutra - Cluster 11):
1. No VOSviewer, gere seu mapa pulverizado de palavras-chave.
2. Na aba lateral esquerda (**File**), clique em **Save**.
3. Salve o arquivo de mapa com o nome `mapa_original.txt` dentro da pasta `redes_vosviewer/`.
4. Salve o arquivo de rede correspondente como `rede_original.txt` na mesma pasta.
5. No terminal, execute o colorizador:
   ```bash
   php scripts/colorir_mapa_vosviewer.php
   ```
6. O script gerará o arquivo `redes_vosviewer/mapa_original_colorido.txt`.
7. **No VOSviewer:** Abra esse arquivo colorido (`mapa_original_colorido.txt`) em conjunto com a sua rede original (`rede_original.txt`). O gráfico ficará perfeito, destacando semanticamente os termos que compõem sua metodologia!

---

## 📚 Dicionário de Categorias Sociotécnicas (Referência Rápida)

1. **IA para agricultura de precisão**
   - 1.1. Monitoramento da lavoura
   - 1.2. Aplicação localizada de insumos
   - 1.3. Detecção de plantas daninhas, pragas e doenças
2. **IA para previsão e tomada de decisão**
   - 2.1. Previsão de produtividade
   - 2.2. Previsão climática aplicada ao manejo
   - 2.3. Análise de risco
3. **IA para irrigação e uso da água**
   - 3.1. Irrigação inteligente
   - 3.2. Detecção de estresse hídrico
   - 3.3. Gestão de reservatórios e bombeamento
4. **IA para pecuária de precisão**
   - 4.1. Monitoramento da saúde animal
   - 4.2. Manejo alimentar
   - 4.3. Reprodução e bem-estar
   - 4.4. Manejo de pastagens
5. **IA embarcada em máquinas, drones e robôs**
   - 5.1. Máquinas agrícolas inteligentes
   - 5.2. Drones com IA
   - 5.3. Robôs agrícolas
6. **IA para gestão da propriedade rural**
   - 6.1. Gestão financeira e custos de produção
   - 6.2. Controle de estoque e insumos
   - 6.3. Planejamento operacional
7. **IA para assistência técnica e extensão rural**
   - 7.1. Assistentes virtuais agrícolas
   - 7.2. Diagnóstico por imagem
   - 7.3. Recomendação técnica personalizada
8. **IA para comercialização e mercado**
   - 8.1. Previsão de preços e demanda
   - 8.2. Classificação de produtos
   - 8.3. Rastreabilidade e cadeia produtiva
9. **IA para pós-colheita, armazenamento e agroindústria**
   - 9.1. Monitoramento de armazenamento
   - 9.2. Controle de qualidade
   - 9.3. Previsão de perdas
10. **IA para sustentabilidade e conformidade ambiental**
    - 10.1. Monitoramento ambiental
    - 10.2. Uso eficiente de insumos
    - 10.3. Carbono e sustentabilidade

---

## 📖 Trabalho de origem

POLI, J. E. **O chatbot como mediador sociotécnico**: inteligência artificial e comunicação dialógica na Assistência Técnica e Extensão Rural (Ater). 2026. Dissertação (Mestrado em Ciência, Tecnologia e Sociedade) - Universidade Federal de São Carlos, São Carlos, 2026.

---

*Este sistema foi desenvolvido e validado garantindo total conformidade científica e reprodutibilidade para fins de defesa acadêmica.*
