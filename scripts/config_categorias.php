<?php
/**
 * config_categorias.php
 * Arquivo de configuração compartilhado contendo a definição unificada das
 * categorias, subcategorias, validadores de IA/Agro e filtros de ruído.
 */

// -----------------------------------------------------------------------------------------
// 1. DICIONÁRIO DE 10 CATEGORIAS E 29 SUBCATEGORIAS SOCIOTÉCNICAS
// -----------------------------------------------------------------------------------------
$categoriasNovas = [
    "1. IA para agricultura de precisão" => [
        "1.1. Monitoramento da lavoura" => ['crop monitoring', 'crop growth', 'ndvi', 'vegetation index', 'vegetation indices', 'vigor', 'canopy', 'gap detection', 'crop status', 'plant status', 'field monitoring', 'crop canopy', 'smart farm', 'smart farming', 'precision farming', 'agriculture 4.0', 'farming 4.0', 'agrifood 4.0', 'ai in agriculture', 'artificial intelligence in agriculture', 'agricultural applications of artificial intelligence', 'smart agriculture', 'smart agricultural', 'computer vision'],
        "1.2. Aplicação localizada de insumos" => ['variable rate', 'vra', 'localized spraying', 'fertilizer application', 'spot spraying', 'site-specific fertilization', 'precision fertilization', 'input reduction', 'prescriptive map', 'prescription map', 'localized application'],
        "1.3. Detecção de plantas daninhas, pragas e doenças" => ['weed detection', 'pest detection', 'disease detection', 'pathogen', 'infestation', 'rust', 'leaf spot', 'phytosanitary', 'crop disease', 'plant disease', 'pest classification', 'weed identification', 'plant pathology', 'pest', 'pests', 'disease', 'diseases', 'weed', 'weeds']
    ],
    "2. IA para previsão e tomada de decisão" => [
        "2.1. Previsão de produtividade" => ['yield prediction', 'yield forecasting', 'production forecasting', 'yield estimation', 'biomass estimation', 'harvest prediction', 'crop yield forecasting', 'yield modelling', 'yield'],
        "2.2. Previsão climática aplicada ao manejo" => ['weather forecasting', 'climate forecasting', 'frost prediction', 'drought forecasting', 'planting window', 'sowing date', 'spraying window', 'meteorological forecasting', 'rainfall prediction'],
        "2.3. Análise de risco" => ['risk analysis', 'risk assessment', 'crop failure risk', 'market risk', 'predictive modeling', 'financial risk', 'insurance risk', 'yield risk']
    ],
    "3. IA para irrigação e uso da água" => [
        "3.1. Irrigação inteligente" => ['smart irrigation', 'automated irrigation', 'irrigation scheduling', 'water saving', 'irrigation control', 'irrigation management', 'precision irrigation', 'irrigation'],
        "3.2. Detecção de estresse hídrico" => ['water stress', 'drought stress', 'crop water stress', 'soil moisture', 'evapotranspiration', 'moisture sensing', 'thermal imaging water', 'water deficit'],
        "3.3. Gestão de reservatórios e bombeamento" => ['pumping control', 'pump scheduling', 'reservoir management', 'water allocation', 'groundwater management', 'water pumping']
    ],
    "4. IA para pecuária de precisão" => [
        "4.1. Monitoramento da saúde animal" => ['livestock health', 'cattle monitoring', 'cow behavior', 'accelerometer', 'ruminant', 'lameness', 'mastitis', 'ear tag', 'smart collar', 'body temperature animal', 'animal health', 'livestock behavior', 'pig health', 'livestock'],
        "4.2. Manejo alimentar" => ['feed intake', 'feed efficiency', 'feeding behavior', 'trough monitoring', 'feed optimization', 'diet formulation', 'weight gain prediction', 'pasture intake'],
        "4.3. Reprodução e bem-estar" => ['estrus detection', 'heat detection', 'calving prediction', 'mounting behavior', 'animal welfare', 'thermal comfort animal', 'animal stress', 'cattle welfare'],
        "4.4. Manejo de pastagens" => ['pasture management', 'pasture biomass', 'grazing behavior', 'overgrazing', 'rotational grazing', 'cattle herding', 'swagbot', 'stocking rate', 'herding robot', 'agro-sylvo-pastoral', 'agro-silvo-pastoral', 'giahs']
    ],
    "5. IA embarcada em máquinas, drones e robôs" => [
        "5.1. Máquinas agrícolas inteligentes" => ['smart tractor', 'autonomous tractor', 'harvester automation', 'auto-guidance', 'autopilot', 'row detection', 'obstacle avoidance machinery', 'intelligent machinery', 'combine harvester'],
        "5.2. Drones com IA" => ['drone', 'uav', 'unmanned aerial vehicle', 'drone imagery', 'aerial mapping', 'fence inspection', 'plant counting drone', 'quadcopter agricultural', 'drones', 'uavs'],
        "5.3. Robôs agrícolas" => ['robot', 'agribot', 'autonomous weeding', 'robotic weeding', 'selective harvesting', 'robotic harvesting', 'robotic picking', 'autonomous monitoring robot', 'field robot', 'robots', 'robotic', 'robotics']
    ],
    "6. IA para gestão da propriedade rural" => [
        "6.1. Gestão financeira e custos de produção" => ['farm management information system', 'fmis', 'cost analysis farm', 'production cost', 'profitability farm', 'margin forecasting', 'expense tracking', 'financial planning farm'],
        "6.2. Controle de estoque e insumos" => ['inventory control', 'stock management', 'seed inventory', 'fertilizer stock', 'fuel tracking', 'demand forecasting input', 'supply inventory'],
        "6.3. Planejamento operacional" => ['operational planning', 'scheduling tasks', 'task assignment', 'labor planning', 'machinery scheduling', 'operational efficiency farm', 'workforce scheduling', 'agricultural industrial complex', 'agro-industrial complex', 'digital transformation of agriculture']
    ],
    "7. IA para assistência técnica e extensão rural" => [
        "7.1. Assistentes virtuais agrícolas" => ['chatbot', 'virtual assistant', 'conversational agent', 'llm', 'large language model', 'chatgpt', 'rag', 'nlp', 'extension service', 'advisory system', 'voice assistant', 'agricultural chatbot', 'question answering agricultural', 'generative ai', 'genai'],
        "7.2. Diagnóstico por imagem" => ['image diagnosis', 'leaf diagnosis', 'photo diagnosis', 'mobile app diagnosis', 'disease identification image', 'pest identification image', 'leaf symptom diagnosis'],
        "7.3. Recomendação técnica personalizada" => ['expert system', 'decision support system', 'recommendation engine', 'personalized advice', 'soil analysis recommendation', 'fertilization recommendation', 'technology adoption among farmers', 'adoption of artificial intelligence']
    ],
    "8. IA para comercialização e mercado" => [
        "8.1. Previsão de preços e demanda" => ['price forecasting', 'market demand', 'price trend', 'market analysis', 'commodity price', 'sales forecasting', 'agricultural price forecasting'],
        "8.2. Classificação de produtos" => ['sorting', 'grading', 'quality classification', 'fruit grading', 'grain classification', 'meat quality', 'defect detection', 'color sorting', 'product classification', 'grain sorting'],
        "8.3. Rastreabilidade e cadeia produtiva" => ['traceability', 'supply chain', 'blockchain', 'logistics', 'cold chain', 'provenance', 'origin verification', 'food supply chain']
    ],
    "9. IA para pós-colheita, armazenamento e agroindústria" => [
        "9.1. Monitoramento de armazenamento" => ['silo monitoring', 'grain storage', 'temperature control silo', 'humidity control silo', 'spoilage detection', 'fungi risk', 'mycotoxin prevention', 'aeration control silo', 'silo temperature'],
        "9.2. Controle de qualidade" => ['quality control', 'shelf life prediction', 'ripeness estimation', 'contamination detection', 'impurity detection', 'postharvest quality'],
        "9.3. Previsão de perdas" => ['loss prediction', 'waste reduction', 'post-harvest loss', 'spoilage prediction', 'decay forecasting', 'post-harvest waste']
    ],
    "10. IA para sustentabilidade e conformidade ambiental" => [
        "10.1. Monitoramento ambiental" => ['deforestation', 'land cover change', 'soil erosion', 'preservation area', 'biodiversity monitoring', 'habitat mapping', 'degradation map', 'forest monitoring'],
        "10.2. Uso eficiente de insumos" => ['eco-efficiency', 'input conservation', 'resource efficiency', 'precision conservation', 'input reduction sustainability', 'efficient resource use', 'agricultural energy internet', 'aei'],
        "10.3. Carbono e sustentabilidade" => ['carbon sequestration', 'carbon footprint', 'ghg emissions', 'soil organic carbon', 'sustainable agriculture', 'regenerative agriculture', 'agroecology', 'soil carbon', 'climate smart agriculture', 'csa']
    ]
];

// -----------------------------------------------------------------------------------------
// 2. DICIONÁRIOS DE VALIDAÇÃO (IA & DOMÍNIO AGRÍCOLA)
// -----------------------------------------------------------------------------------------
// Termos de validação de IA para garantir presença de componente computacional
$validadoresIA = [
    'intelligence', 'intelligent', 'machine learning', 'language model', 'neural', 'algorithm', 
    'vision', 'robot', 'uav', 'drone', 'sensor', 'iot', 'llm', 'genai', 'generative ai', 'gpt', 
    'transformer', 'automation', 'chatbot', 'assistant', 'agentic', 'conversational', 
    'virtual agent', 'planning', 'rag', 'deep learning', 'computer vision', 'expert system', 
    'neural network', 'fuzzy', 'decision support'
];

// Termos do domínio agrícola para filtrar papers teóricos de computação puramente urbana/industrial
$validadoresAgro = [
    'rural', 'farm', 'agricultur', 'agro', 'crop', 'livestock', 'cattle', 'cow', 'pasture', 
    'soil', 'irrigation', 'silo', 'pest', 'disease', 'plant', 'weed', 'harvest', 'tractor', 
    'pulveriz', 'sowing', 'seeding', 'orchard', 'grape', 'vineyard', 'sheep', 'pig', 'swine', 
    'poultry', 'chicken', 'milk', 'dairy', 'agronom', 'cultivar', 'plantation', 'forestry',
    'pesticide', 'fertilizer', 'herding', 'animal welfare', 'horticulture', 'field'
];

// -----------------------------------------------------------------------------------------
// 3. DICIONÁRIO DE EXCLUSÃO DE RUÍDOS (Noise Terms)
// -----------------------------------------------------------------------------------------
$ruidosTermos = [
    "ambiguidades_acronimos" => [
        'ragtime',          // Gênero musical
        'rag darbari',      // Composição musical indiana
        'shukla',           // Referência literária
        'russian researchers', // Viés geopolítico anômalo
        'co-housing', 'housing diversity', 'speculative design', 'co-housing communities' // Habitação social, não campo
    ],
    "medicina_humana" => [
        'kilovoltage x-ray', 'radiation therapy', 'mammography', 'colonoscopy', 'endoscopy', 
        'serum cholesterol', 'cerebral infarction', 'dietary lipids', 'diabetic retinopathy', 
        'chromosome translocation', 'calmodulin', 'smooth-muscle myosin', 'anesthesia', 
        'oncology', 'cancer patient', 'clinical trials', 'human disease', 'cardiology', 
        'stroke risk', 'brain tumor', 'chemotherapy', 'pediatric', 'hospital', 'myocardial', 
        'pulmonary', 'alzheimer', 'parkinson', 'diabetic control', 'retina image'
    ],
    "pesticidas_sem_ia" => [
        'acute toxicity of', 'ecotoxicological effects', 'fipronil on', 'paraquat influenced', 
        'herbicide evaluation', 'cholinesterase activity', 'toxicity test', 'lab rat'
    ],
    "hidrologia_neve_geral" => [
        'snowpack water-equivalent', 'snow indices', 'snow cover depth'
    ],
    "microbiologia_alimentos_sem_ia" => [
        'listeria monocytogenes', 'haccp'
    ],
    "veterinaria_clinica_sem_ia" => [
        'anemia detection in broiler to aia'
    ],
    "fisica_e_geofisica" => [
        'chronostratigraphic', 'longithorols'
    ],
    "macroeconomia_politica" => [
        'panel-corrected standard errors', 'quantile regression', 'investments in the agricultural sector', 
        'agricultural employment', 'labor reallocation' // Macroeconomia rural teórica e estatística, sem IA no campo
    ],
    "redes_telecomunicacoes" => [
        'semantic communication', 'semcom', '6g mobile communication', '6g network', 'cellular communication' // Redes móveis genéricas que só citam smart agriculture
    ],
    "educacao_escolar" => [
        'K-12', 'school teachers', 'teachers\' preparedness', 'classroom teaching' // Educação K-12 escolar
    ],
    "corporativo_negocios" => [
        'non-traditional sectors', 'transforming global industries' // Negócios e análises corporativas globais
    ],
    "energia_eolica_solar" => [
        'wind turbine', 'wind turbines', 'wind energy', 'wind power', 'wind speed', 
        'photovoltaic systems', 'solar photovoltaic', 'solar wind hybrid', 'vertical-axis wind' // Engenharia de energia limpa não ligada a agro
    ]
];
