<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();

// Garantir colunas e tabelas MySQL de forma segura
ensureExtraTablesExist($db, 'mysql');

$successMsg = '';
$errorMsg = '';
$importedProductId = null;
$parsedData = null;

// FUNÇÃO GERADORA DE AVALIAÇÕES REALISTAS
function generateRealisticReviews($count, $gender, $ratingType) {
    $maleNames = [
        'Francisco', 'Carlos Eduardo', 'Marcelo Santos', 'Rodrigo Lima', 'João Pedro', 
        'Fernando Alves', 'Bruno Henrique', 'Gustavo Ferreira', 'Lucas Oliveira', 'Matheus Costa', 
        'Paulo Roberto', 'Felipe Mendes', 'Thiago Rocha', 'Diego Martins', 'Rafael Barbosa',
        'Guilherme Silva', 'Eduardo Pereira', 'Renato Souza', 'Vinicius Castro', 'Gabriel Ribeiro',
        'Leandro Ramos', 'Marcos Vinicius', 'Alexandre Borges', 'Ricardo Santos', 'Mauricio Lima'
    ];
    
    $femaleNames = [
        'Juliana M.', 'Patricia Souza', 'Camila Silva', 'Amanda Oliveira', 'Ana Lucia', 
        'Mariana Costa', 'Beatriz Lima', 'Fernanda Rocha', 'Cristiana Ferreira', 'Vanessa Santos', 
        'Bruna Garcia', 'Leticia Almeida', 'Juliana Ribeiro', 'Gabriela Duarte', 'Larissa Ramos',
        'Carla Cristina', 'Renata Fonseca', 'Aline Cardoso', 'Daniela Martins', 'Jessica Nogueira',
        'Tatiane Lopes', 'Priscila Fernandes', 'Monique Reis', 'Thais Carvalho', 'Luciana M.'
    ];

    $comments = [
        "Excelente produto! Ótimo custo-benefício, entregou super rápido em 2 dias pelas Casas Bahia! Produto muito bem embalado e funcionando perfeitamente.",
        "Superou minhas expectativas! Desempenho e acabamento impecáveis, qualidade de alto nível. Recomendo demais!",
        "Produto excelente, fácil de instalar e usar no dia a dia. Chegou bem antes do prazo previsto.",
        "Muito satisfeito com a compra! Valeu muito a pena, entrega rápida e produto original.",
        "Comprei para a minha família e todos adoraram! Qualidade impressionante.",
        "Ótima compra, chegou em perfeitas condições. As Casas Bahia sempre entregando o melhor serviço.",
        "Produto muito bom, atendeu 100% das minhas necessidades. Recomendo a todos!",
        "Muito prático e bonito! A qualidade do material é muito superior aos concorrentes da mesma faixa de preço.",
        "Chegou certinho, nota 10 para o vendedor e para as Casas Bahia. Voltarei a comprar com certeza!",
        "Excelente custo-benefício. O manuseio é simples e intuitivo."
    ];

    $months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto'];
    $reviews = [];

    for ($i = 0; $i < $count; $i++) {
        if ($gender === 'homem') {
            $name = $maleNames[array_rand($maleNames)];
        } else if ($gender === 'mulher') {
            $name = $femaleNames[array_rand($femaleNames)];
        } else {
            $pool = ($i % 2 === 0) ? $maleNames : $femaleNames;
            $name = $pool[array_rand($pool)];
        }

        if ($ratingType === '5_only') {
            $rating = 5;
        } else {
            $rating = (rand(1, 10) > 1) ? 5 : 4;
        }

        $day = rand(1, 28);
        $month = $months[array_rand($months)];
        $dateText = "Avaliado em $day de $month de 2026";
        $comment = $comments[array_rand($comments)];

        $reviews[] = [
            'author' => $name,
            'rating' => $rating,
            'review_date' => $dateText,
            'content' => $comment
        ];
    }

    return $reviews;
}

// Busca profunda em JSON
function findDeepKeys($array, $targetKey) {
    $results = [];
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if ($key === $targetKey && !empty($value)) {
                $results[] = $value;
            }
            if (is_array($value)) {
                $results = array_merge($results, findDeepKeys($value, $targetKey));
            }
        }
    }
    return $results;
}

// HIGIENIZADOR E DECODIFICADOR ROBUSTO DE STRINGS E TÍTULOS
function cleanProductTitleString($str) {
    if (!is_string($str)) return '';

    // Decodificar entidades HTML (&quot;, &amp;, &#39;, &aacute;, etc.)
    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Decodificar sequências de escape unicode de JSON (\u00e3 -> ã, \u00e9 -> é)
    $str = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }, $str);

    $str = strip_tags($str);
    $str = trim($str);

    // Remover sufixos de marca da Casas Bahia do título
    $str = preg_replace('/\s*[\|-]\s*Casas\s*Bahia.*$/i', '', $str);
    $str = preg_replace('/\s*-\s*compre\s*nas\s*casas\s*bahia.*$/i', '', $str);
    $str = preg_replace('/\s*-\s*casasbahia\.com\.br.*$/i', '', $str);

    return trim($str, '"\' ');
}

// FUNÇÃO DE EXTRAÇÃO DE TÍTULO DA CASAS BAHIA COM ALTA PRECISÃO E SANITIZAÇÃO
function extractTitleFromCasasBahiaHtml($sourceCode, $jsonData = null) {

    // 1. Tentar Schema.org ld+json (<script type="application/ld+json">)
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $sourceCode, $ldMatches)) {
        foreach ($ldMatches[1] as $ldJson) {
            $ldData = json_decode($ldJson, true);
            if (is_array($ldData)) {
                if (isset($ldData['name']) && is_string($ldData['name'])) {
                    $t = cleanProductTitleString($ldData['name']);
                    if (strlen($t) >= 8 && !preg_match('/^(casas bahia|home|carrinho|checkout)$/i', $t)) {
                        return $t;
                    }
                }
                if (isset($ldData['@graph']) && is_array($ldData['@graph'])) {
                    foreach ($ldData['@graph'] as $item) {
                        if (isset($item['name']) && is_string($item['name'])) {
                            $t = cleanProductTitleString($item['name']);
                            if (strlen($t) >= 8 && !preg_match('/^(casas bahia|home|carrinho|checkout)$/i', $t)) {
                                return $t;
                            }
                        }
                    }
                }
            }
        }
    }

    // 2. Tentar Meta Tag og:title ou twitter:title
    if (preg_match('/<meta[^>]*property=["\'](?:og|twitter):title["\'][^>]*content=["\'](.*?)["\']/i', $sourceCode, $m)) {
        $t = cleanProductTitleString($m[1]);
        if (strlen($t) >= 8) return $t;
    }

    if (preg_match('/<meta[^>]*name=["\']title["\'][^>]*content=["\'](.*?)["\']/i', $sourceCode, $m)) {
        $t = cleanProductTitleString($m[1]);
        if (strlen($t) >= 8) return $t;
    }

    // 3. Tentar Tag <h1> do HTML principal
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $sourceCode, $m)) {
        $t = cleanProductTitleString($m[1]);
        if (strlen($t) >= 8) return $t;
    }

    // 4. Tentar JSON __NEXT_DATA__ (chaves específicas de produto)
    if ($jsonData) {
        $keys = ['productName', 'skuName', 'name', 'title'];
        foreach ($keys as $k) {
            $vals = findDeepKeys($jsonData, $k);
            foreach ($vals as $v) {
                if (is_string($v)) {
                    $t = cleanProductTitleString($v);
                    if (strlen($t) >= 12 && !preg_match('/^(casas bahia|home|carrinho|checkout|busca|produto|televisores|tv e vídeo|eletrodomésticos)$/i', $t)) {
                        return $t;
                    }
                }
            }
        }
    }

    // 5. Tentar Tag <title> do documento HTML
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $sourceCode, $m)) {
        $t = cleanProductTitleString($m[1]);
        if (strlen($t) >= 8) return $t;
    }

    return '';
}

// FUNÇÃO DE EXTRAÇÃO DE DESCRIÇÃO DA CASAS BAHIA
function extractDescriptionFromCasasBahiaHtml($sourceCode, $jsonData = null) {
    $cleanDescText = function($raw) {
        if (!is_string($raw) || strlen($raw) < 15) return '';
        $d = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $d = html_entity_decode($d, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $d = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $d);
        $d = preg_replace('/<br\s*\/?>/i', "\n", $d);
        $d = preg_replace('/<\/p>/i', "\n\n", $d);
        $d = preg_replace('/<\/li>/i', "\n", $d);
        $d = strip_tags($d);
        $d = preg_replace("/\n{3,}/", "\n\n", trim($d));
        if (preg_match('/^(casas bahia|compre nas casas bahia)$/i', $d)) return '';
        return $d;
    };

    // 1. Tentar JSON __NEXT_DATA__
    if ($jsonData) {
        $descKeys = ['description', 'descriptionHtml', 'longDescription', 'details', 'productDescription'];
        foreach ($descKeys as $dk) {
            $vals = findDeepKeys($jsonData, $dk);
            foreach ($vals as $v) {
                $d = $cleanDescText($v);
                if (strlen($d) > 20) return $d;
            }
        }
    }

    // 2. Tentar Schema.org JSON-LD
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $sourceCode, $ldMatches)) {
        foreach ($ldMatches[1] as $ldJson) {
            $ldData = json_decode($ldJson, true);
            if (is_array($ldData) && isset($ldData['description'])) {
                $d = $cleanDescText($ldData['description']);
                if (strlen($d) > 20) return $d;
            }
        }
    }

    // 3. Tentar Meta Tag og:description ou description
    if (preg_match('/<meta[^>]*property=["\']og:description["\'][^>]*content=["\'](.*?)["\']/i', $sourceCode, $m)) {
        $d = $cleanDescText($m[1]);
        if (strlen($d) > 20) return $d;
    }
    if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']/i', $sourceCode, $m)) {
        $d = $cleanDescText($m[1]);
        if (strlen($d) > 20) return $d;
    }

    // 4. Tentar container HTML de descrição
    if (preg_match('/<(?:div|section)[^>]*(?:id|class)=["\'][^"\']*(?:description|descricao|product-details)[^"\']*["\'][^>]*>(.*?)<\/(?:div|section)>/is', $sourceCode, $m)) {
        $d = $cleanDescText($m[1]);
        if (strlen($d) > 20) return substr($d, 0, 3000);
    }

    return 'Produto de alta qualidade original com garantia de fábrica e nota fiscal.';
}

// FUNÇÃO DE EXTRAÇÃO DE ESPECIFICAÇÕES TÉCNICAS E CARACTERÍSTICAS DA CASAS BAHIA
function extractSpecificationsFromCasasBahiaHtml($sourceCode, $jsonData = null) {
    $specs = [];

    $addSpec = function($name, $val) use (&$specs) {
        $name = trim(html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $val = trim(html_entity_decode(strip_tags($val), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $name = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $name);
        $val = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $val);

        $name = trim($name, " \t\n\r\0\x0B:-");
        $val = trim($val, " \t\n\r\0\x0B:-");

        if (!empty($name) && !empty($val) && strlen($name) >= 2 && strlen($name) < 70 && strlen($val) < 300) {
            foreach ($specs as $existing) {
                if (strtolower($existing['spec_name']) === strtolower($name)) return;
            }
            $specs[] = ['spec_name' => $name, 'spec_value' => $val];
        }
    };

    // 1. Tentar Schema.org additionalProperty em ld+json
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $sourceCode, $ldMatches)) {
        foreach ($ldMatches[1] as $ldJson) {
            $ldData = json_decode($ldJson, true);
            if (is_array($ldData) && isset($ldData['additionalProperty']) && is_array($ldData['additionalProperty'])) {
                foreach ($ldData['additionalProperty'] as $prop) {
                    if (isset($prop['name']) && isset($prop['value'])) {
                        $valStr = is_array($prop['value']) ? implode(', ', $prop['value']) : strval($prop['value']);
                        $addSpec($prop['name'], $valStr);
                    }
                }
            }
        }
    }

    // 2. Tentar JSON __NEXT_DATA__ (chaves de especificações/características simples e agrupadas)
    if ($jsonData) {
        $specKeys = ['specifications', 'technicalSpecifications', 'attributes', 'features', 'properties', 'details', 'factSheet', 'fichaTecnica'];
        foreach ($specKeys as $sk) {
            $foundArr = findDeepKeys($jsonData, $sk);
            foreach ($foundArr as $arr) {
                if (is_array($arr)) {
                    foreach ($arr as $item) {
                        if (is_array($item)) {
                            // Se for grupo de especificações (ex: {"groupName": "...", "specifications": [...]})
                            $subItems = $item['specifications'] ?? $item['items'] ?? $item['attributes'] ?? $item['features'] ?? [$item];
                            if (!is_array($subItems)) $subItems = [$item];

                            foreach ($subItems as $sub) {
                                if (is_array($sub)) {
                                    $name = $sub['name'] ?? $sub['label'] ?? $sub['key'] ?? $sub['title'] ?? null;
                                    $val = null;

                                    if (isset($sub['values']) && is_array($sub['values'])) {
                                        $val = implode(', ', array_filter($sub['values'], function($v) { return is_string($v) || is_numeric($v); }));
                                    } else if (isset($sub['value'])) {
                                        $val = is_array($sub['value']) ? implode(', ', $sub['value']) : strval($sub['value']);
                                    } else if (isset($sub['val'])) {
                                        $val = strval($sub['val']);
                                    } else if (isset($sub['text'])) {
                                        $val = strval($sub['text']);
                                    }

                                    if (is_string($name) && !empty($val)) {
                                        $addSpec($name, $val);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // 3. Tentar Tabela HTML <table> ou DL/DT/DD
    if (preg_match_all('/<tr[^>]*>\s*<t[hd][^>]*>(.*?)<\/t[hd]>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $sourceCode, $trMatches, PREG_SET_ORDER)) {
        foreach ($trMatches as $tr) {
            $addSpec($tr[1], $tr[2]);
        }
    }

    if (preg_match_all('/<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/is', $sourceCode, $dlMatches, PREG_SET_ORDER)) {
        foreach ($dlMatches as $dl) {
            $addSpec($dl[1], $dl[2]);
        }
    }

    // 4. Tentar listas HTML com <li><strong>Nome:</strong> Valor</li> ou <b>Nome:</b> Valor
    if (preg_match_all('/<li[^>]*>\s*<(?:strong|b)[^>]*>(.*?):?<\/s(?:trong|b)>\s*:?\s*(.*?)<\/li>/is', $sourceCode, $liMatches, PREG_SET_ORDER)) {
        foreach ($liMatches as $li) {
            $addSpec($li[1], $li[2]);
        }
    }

    if (preg_match_all('/<(?:strong|b)[^>]*>([^<:]+):<\/s(?:trong|b)>\s*([^<]+)/is', $sourceCode, $bMatches, PREG_SET_ORDER)) {
        foreach ($bMatches as $bm) {
            $addSpec($bm[1], $bm[2]);
        }
    }

    return array_slice($specs, 0, 20);
}

// FUNÇÃO DE EXTRAÇÃO DE VARIAÇÕES (VOLTAGEM, TAMANHO, COR) DA CASAS BAHIA
function extractVariationsFromCasasBahiaHtml($sourceCode, $jsonData = null, $title = '', $specs = []) {
    $varName = '';
    $options = [];
    $default = '';

    // 1. Procurar no JSON __NEXT_DATA__
    if ($jsonData) {
        $attrList = findDeepKeys($jsonData, 'attributes');
        if (empty($attrList)) $attrList = findDeepKeys($jsonData, 'variations');
        if (empty($attrList)) $attrList = findDeepKeys($jsonData, 'options');
        if (empty($attrList)) $attrList = findDeepKeys($jsonData, 'skuOptions');

        foreach ($attrList as $arr) {
            if (is_array($arr)) {
                foreach ($arr as $item) {
                    if (is_array($item)) {
                        $name = $item['name'] ?? $item['label'] ?? '';
                        if (preg_match('/(voltagem|tensão|voltage)/i', $name)) {
                            $varName = 'Voltagem';
                            $vals = $item['values'] ?? $item['options'] ?? $item['value'] ?? [];
                            if (is_array($vals)) {
                                foreach ($vals as $v) {
                                    if (is_string($v) || is_numeric($v)) $options[] = trim(strval($v));
                                }
                            } else if (is_string($vals)) {
                                $options[] = trim($vals);
                            }
                        } else if (preg_match('/(tamanho|tamanho da tela|screen size|size)/i', $name)) {
                            $varName = 'Tamanho';
                            $vals = $item['values'] ?? $item['options'] ?? $item['value'] ?? [];
                            if (is_array($vals)) {
                                foreach ($vals as $v) {
                                    if (is_string($v) || is_numeric($v)) $options[] = trim(strval($v));
                                }
                            } else if (is_string($vals)) {
                                $options[] = trim($vals);
                            }
                        }
                    }
                }
            }
        }
    }

    // 2. Se não achou em estrutura explícita de variação, verificar pelas especificações ou pelo tipo de produto
    if (empty($options)) {
        foreach ($specs as $sp) {
            if (preg_match('/(voltagem|tensão)/i', $sp['spec_name'])) {
                $valStr = $sp['spec_value'];
                $varName = 'Voltagem';
                if (preg_match('/110/i', $valStr) && preg_match('/220/i', $valStr)) {
                    $options = ['110V', '220V'];
                } else if (preg_match('/bivolt/i', $valStr)) {
                    $options = ['110V', '220V', 'Bivolt'];
                } else if (preg_match('/110|127/i', $valStr)) {
                    $options = ['110V', '220V'];
                } else if (preg_match('/220/i', $valStr)) {
                    $options = ['110V', '220V'];
                }
                break;
            }
        }

        if (empty($options)) {
            $isAppliance = preg_match('/(fritadeira|air fryer|ventilador|liquidificador|micro-ondas|microondas|geladeira|refrigerador|batedeira|cafeteira|aspirador|panela el[eé]trica|forno|fog[aã]o|maquina de lavar|tanquinho|multiprocessador|ferro de passar|adega|freezer|chapa|grill)/i', $title . ' ' . $sourceCode);

            if ($isAppliance) {
                $varName = 'Voltagem';
                $options = ['110V', '220V'];
            } else if (preg_match('/(smart tv|tv|televis&atilde;o|monitor)/i', $title)) {
                $varName = 'Tamanho da Tela';
                $options = ['43"', '50"', '55"', '65"'];
            }
        }
    }

    $options = array_values(array_unique(array_filter($options)));

    if (!empty($options)) {
        $default = in_array('220V', $options) ? '220V' : (in_array('110V', $options) ? '110V' : $options[0]);
    }

    return [
        'name' => $varName,
        'options' => implode(', ', $options),
        'default' => $default
    ];
}

// FUNÇÃO DE EXTRAÇÃO DE PREÇOS COM ALTA PRECISÃO DA CASAS BAHIA
function extractPricesFromCasasBahiaHtml($sourceCode, $jsonData = null) {
    $foundPrices = [];

    $cleanVal = function($v) {
        if (!is_numeric($v)) return 0.00;
        $v = floatval($v);
        // Se vier como inteiro em centavos alto sem decimal (ex: 17660 ou 189900)
        if ($v > 10000 && floor($v) == $v) {
            $v = $v / 100;
        }
        return $v;
    };

    // 1. Extração por Meta Tags da Casas Bahia (product:price:amount / og:price:amount)
    if (preg_match('/<meta[^>]*property=["\'](?:product|og):price:amount["\'][^>]*content=["\']([\d\.,]+)["\']/i', $sourceCode, $pm)) {
        $val = floatval(str_replace(',', '.', $pm[1]));
        if ($val > 5) $foundPrices[] = $val;
    }

    // 2. Extração por chaves JSON específicas de preços da Casas Bahia
    if ($jsonData) {
        $targetKeys = ['sellPrice', 'listPrice', 'salePrice', 'cashDiscountPrice', 'priceWithDiscount', 'currentPrice'];
        foreach ($targetKeys as $tk) {
            $vals = findDeepKeys($jsonData, $tk);
            foreach ($vals as $v) {
                if (is_numeric($v)) {
                    $norm = $cleanVal($v);
                    if ($norm > 5) $foundPrices[] = $norm;
                }
            }
        }
    }

    // 3. Extração por Regex de valores formatados em BRL (ex: R$ 189,90 / R$ 1.580,07)
    if (preg_match_all('/R\$\s*([\d\.]+,\d{2})/i', $sourceCode, $brlMatches)) {
        foreach ($brlMatches[1] as $bStr) {
            $bClean = str_replace('.', '', $bStr);
            $bClean = floatval(str_replace(',', '.', $bClean));
            if ($bClean > 5) $foundPrices[] = $bClean;
        }
    }

    $regularPrice = 0.00;
    $pixPrice = 0.00;

    // Processar preços encontrados
    if (!empty($foundPrices)) {
        $foundPrices = array_values(array_unique($foundPrices));
        sort($foundPrices);
        
        if (count($foundPrices) >= 2) {
            $pixPrice = reset($foundPrices);
            $regularPrice = end($foundPrices);
        } else {
            $regularPrice = reset($foundPrices);
            $pixPrice = round($regularPrice * 0.93, 2);
        }
    }

    return [
        'regular_price' => $regularPrice,
        'pix_price' => $pixPrice
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // PASSO 2: SALVAR O PRODUTO REVISADO NO BANCO MYSQL
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        try {
            $title = trim($_POST['title'] ?? '');
            $brand = trim($_POST['brand'] ?? 'Casas Bahia');
            $sku = trim($_POST['sku'] ?? ('CB-' . time()));
            $itemCode = trim($_POST['item_code'] ?? strval(rand(10000000, 99999999)));
            $regularPrice = floatval(str_replace(',', '.', $_POST['regular_price'] ?? '0'));
            $pixPrice = floatval(str_replace(',', '.', $_POST['pix_price'] ?? '0'));
            $maxInstallments = intval($_POST['max_installments'] ?? 10);
            $installmentValue = round($regularPrice / max(1, $maxInstallments), 2);
            $description = trim($_POST['description'] ?? '');
            $variationName = trim($_POST['variation_name'] ?? '');
            $variationOptions = trim($_POST['variation_options'] ?? '');
            $variationDefault = trim($_POST['variation_default'] ?? '');

            // Configurações do Gerador de Avaliações
            $reviewCount = intval($_POST['review_count'] ?? 50);
            $reviewGender = trim($_POST['review_gender'] ?? 'ambos');
            $reviewStars = trim($_POST['review_stars'] ?? '4_5');

            // Imagens
            $imagesInput = explode("\n", $_POST['images_urls'] ?? '');
            $images = [];
            foreach ($imagesInput as $u) {
                $u = trim($u);
                if (!empty($u)) $images[] = $u;
            }

            if (empty($images)) {
                $images[] = 'assets/images/product_aoc_main.png';
            }

            // Inserir Produto com Porcentagem Real de Desconto Calculada
            $calcDiscount = ($regularPrice > 0 && $pixPrice > 0 && $regularPrice > $pixPrice) ? round((($regularPrice - $pixPrice) / $regularPrice) * 100) : 7;

            // Determinar Categoria Automática com base no Título e Marca
            $autoCategory = 'eletros';
            $lcTitle = mb_strtolower($title . ' ' . $brand);
            if (preg_match('/(tv|smart tv|televisor|roku|qled|oled|4k|monitor)/i', $lcTitle)) {
                $autoCategory = 'tvs';
            } else if (preg_match('/(iphone|apple)/i', $lcTitle)) {
                $autoCategory = 'iphone';
            } else if (preg_match('/(smartphone|celular|galaxy|moto|xiaomi|redmi|motorola|poco|realme)/i', $lcTitle)) {
                $autoCategory = 'smartphones';
            } else if (preg_match('/(fritadeira|air fryer|airfryer|geladeira|fogão|fogao|micro-ondas|microondas|lavar|máquina|maquina|ventila|liquidi|batedeira|cafeteira|forno|cooktop|ar condicionado)/i', $lcTitle)) {
                $autoCategory = 'eletros';
            }

            $db->beginTransaction();

            $stmtIns = $db->prepare("
                INSERT INTO products 
                (sku, title, brand, item_code, category, badge_1, badge_2, discount_percentage, regular_price, pix_price, pix_discount_percentage, max_installments, installment_value, carne_max_installments, rating, reviews_count, questions_count, description, variation_name, variation_options, variation_default)
                VALUES 
                (:sku, :title, :brand, :item_code, :category, 'BRASIL', 'LANÇAMENTO★', :disc_perc, :regular_price, :pix_price, :pix_disc_perc, :max_inst, :installment_value, 24, 4.9, :rev_cnt, 14, :description, :vname, :vopts, :vdef)
            ");

            $stmtIns->execute([
                'sku' => $sku,
                'title' => $title,
                'brand' => $brand,
                'item_code' => $itemCode,
                'category' => $autoCategory,
                'disc_perc' => $calcDiscount,
                'regular_price' => $regularPrice,
                'pix_price' => $pixPrice,
                'pix_disc_perc' => $calcDiscount,
                'max_inst' => $maxInstallments,
                'installment_value' => $installmentValue,
                'rev_cnt' => $reviewCount,
                'description' => $description,
                'vname' => $variationName,
                'vopts' => $variationOptions,
                'vdef' => $variationDefault
            ]);

            $newProductId = $db->lastInsertId();
            $importedProductId = $newProductId;

            // Inserir Imagens na Galeria
            $stmtImg = $db->prepare("INSERT INTO product_images (product_id, image_url, is_main, sort_order) VALUES (:pid, :url, :is_main, :sort)");
            foreach ($images as $idx => $imgUrl) {
                $stmtImg->execute([
                    'pid' => $newProductId,
                    'url' => $imgUrl,
                    'is_main' => ($idx === 0) ? 1 : 0,
                    'sort' => $idx + 1
                ]);
            }

            // Inserir Especificações Técnicas (extraídas do código fonte ou padrão)
            $specsRaw = $_POST['specs_json'] ?? '';
            $specs = json_decode($specsRaw, true);
            if (!is_array($specs) || empty($specs)) {
                $specs = [
                    ['spec_name' => 'Marca', 'spec_value' => $brand],
                    ['spec_name' => 'Código do Item', 'spec_value' => $itemCode],
                    ['spec_name' => 'Garantia do Fabricante', 'spec_value' => '12 Meses']
                ];
            }
            $stmtSpec = $db->prepare("INSERT INTO specifications (product_id, spec_name, spec_value) VALUES (:pid, :name, :val)");
            foreach ($specs as $spec) {
                if (!empty($spec['spec_name']) && !empty($spec['spec_value'])) {
                    $stmtSpec->execute([
                        'pid' => $newProductId,
                        'name' => $spec['spec_name'],
                        'val' => $spec['spec_value']
                    ]);
                }
            }

            // Inserir Garantia Estendida SOMENTE se detectada no código fonte ou formulário manual
            // Detectar pelo campo hasWarranty vindo do formulário (campo hidden setado no parse)
            $hasExtendedWarranty = !empty($_POST['has_extended_warranty']) && $_POST['has_extended_warranty'] === '1';
            
            // Fallback: detectar pelo source_code se disponível
            if (!$hasExtendedWarranty) {
                $srcForWarranty = strtolower($_POST['source_code_raw'] ?? $_POST['source_code'] ?? '');
                if (!empty($srcForWarranty)) {
                    $hasExtendedWarranty = (
                        strpos($srcForWarranty, 'garantia estendida') !== false ||
                        strpos($srcForWarranty, 'garantia extra') !== false ||
                        strpos($srcForWarranty, 'extended warranty') !== false ||
                        strpos($srcForWarranty, '"warranty"') !== false ||
                        strpos($srcForWarranty, 'warrantyoffer') !== false
                    );
                }
            }

            $stmtWar = $db->prepare("INSERT INTO warranties (product_id, title, subtitle, monthly_price, is_default) VALUES (:pid, :title, :sub, :price, :def)");

            if ($hasExtendedWarranty) {
                // Produto TEM garantia estendida disponível — inserir as 2 opções
                $monthlyWarranty15 = round(($pixPrice * 0.15) / 12, 2);
                $stmtWar->execute(['pid' => $newProductId, 'title' => 'Sem garantia', 'sub' => 'só garantia de fábrica', 'price' => 0.00, 'def' => 1]);
                $stmtWar->execute(['pid' => $newProductId, 'title' => 'Com Garantia Estendida', 'sub' => 'por apenas R$ ' . number_format($monthlyWarranty15, 2, ',', '.') . ' por mês*', 'price' => $monthlyWarranty15, 'def' => 0]);
            }
            // Se NÃO tem garantia estendida, não insere nada — seção fica oculta no produto

            // GERAR E INSERIR AS AVALIAÇÕES SELECIONADAS PELO ADMIN (Sem anexar fotos automáticas)
            $generatedReviews = generateRealisticReviews($reviewCount, $reviewGender, $reviewStars);
            $stmtRev = $db->prepare("INSERT INTO product_reviews (product_id, author, reviewer_name, rating, rating_stars, review_date, content, review_text) VALUES (:pid, :author, :rev_name, :rating, :rating_stars, :rdate, :content, :rev_text)");

            foreach ($generatedReviews as $rev) {
                $stmtRev->execute([
                    'pid' => $newProductId,
                    'author' => $rev['author'],
                    'rev_name' => $rev['author'],
                    'rating' => $rev['rating'],
                    'rating_stars' => $rev['rating'],
                    'rdate' => $rev['review_date'],
                    'content' => $rev['content'],
                    'rev_text' => $rev['content']
                ]);
            }

            $db->commit();
            $successMsg = "🎉 Produto importado e cadastrado no MySQL com sucesso com <strong>$reviewCount avaliações</strong> ativas!";

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errorMsg = "Erro ao salvar produto no MySQL: " . $e->getMessage();
        }
    } 

    // PASSO 1: ANALISAR E EXTRAIR CÓDIGO FONTE DA CASAS BAHIA
    else if (isset($_POST['action']) && $_POST['action'] === 'parse') {
        $sourceCode = trim($_POST['source_code'] ?? '');

        if (empty($sourceCode)) {
            $errorMsg = "Por favor, cole o código fonte (HTML) do produto da Casas Bahia.";
        } else {
            $extractedTitle = '';
            $extractedBrand = '';
            $extractedSku = 'CB-' . time();
            $extractedItemCode = strval(rand(10000000, 99999999));
            $extractedRegularPrice = 0.00;
            $extractedPixPrice = 0.00;
            $extractedDescription = '';
            $extractedImages = [];
            $jsonData = null;

            // 1. Extrair JSON __NEXT_DATA__
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $sourceCode, $matches)) {
                $jsonData = json_decode($matches[1], true);
                
                if ($jsonData) {
                    // Título
                    $titles = findDeepKeys($jsonData, 'title');
                    if (!empty($titles)) {
                        foreach ($titles as $t) {
                            if (is_string($t) && strlen($t) > 10 && strpos($t, 'Casas Bahia') === false) {
                                $extractedTitle = $t;
                                break;
                            }
                        }
                    }

                    // Marca
                    $brands = findDeepKeys($jsonData, 'brand');
                    if (!empty($brands)) {
                        foreach ($brands as $b) {
                            if (is_string($b) && !empty($b)) {
                                $extractedBrand = $b;
                                break;
                            }
                            if (is_array($b) && isset($b['name'])) {
                                $extractedBrand = $b['name'];
                                break;
                            }
                        }
                    }

                    // Imagens
                    $imgUrls = findDeepKeys($jsonData, 'url');
                    foreach ($imgUrls as $u) {
                        if (is_string($u) && preg_match('/\.(jpg|jpeg|png|webp|gif)/i', $u)) {
                            if (strpos($u, '//') === 0) $u = 'https:' . $u;
                            if (strpos($u, 'http') === 0) $extractedImages[] = $u;
                        }
                    }
                }
            }

            // EXTRAÇÃO DE TÍTULO, PREÇOS, DESCRIÇÃO E CARACTERÍSTICAS DA CASAS BAHIA
            $extractedTitle = extractTitleFromCasasBahiaHtml($sourceCode, $jsonData);
            $pricesExtracted = extractPricesFromCasasBahiaHtml($sourceCode, $jsonData);
            $extractedRegularPrice = $pricesExtracted['regular_price'];
            $extractedPixPrice = $pricesExtracted['pix_price'];

            $extractedDescription = extractDescriptionFromCasasBahiaHtml($sourceCode, $jsonData);
            $extractedSpecs = extractSpecificationsFromCasasBahiaHtml($sourceCode, $jsonData);
            $extractedVariations = extractVariationsFromCasasBahiaHtml($sourceCode, $jsonData, $extractedTitle, $extractedSpecs);

            // Extrair Imagens via Regex
            if (preg_match_all('/https?:\/\/[^"\'>\s]+\.(?:jpg|jpeg|png|webp|gif)(?:\?[^"\'>\s]*)?/i', $sourceCode, $imgMatches)) {
                foreach ($imgMatches[0] as $u) {
                    if (strpos($u, 'imgs.casasbahia.com.br') !== false || strpos($u, 'images') !== false) {
                        $extractedImages[] = $u;
                    }
                }
            }

            $extractedImages = array_values(array_unique($extractedImages));

            // Filtro para garantir alta resolução das fotos
            $cleanImages = [];
            foreach ($extractedImages as $img) {
                if (strpos($img, '15x15') !== false || strpos($img, '50x50') !== false || strpos($img, 'icon') !== false) continue;
                $imgHighRes = preg_replace('/width=\d+/', 'width=800', $img);
                $imgHighRes = preg_replace('/height=\d+/', 'height=800', $imgHighRes);
                $cleanImages[] = $imgHighRes;
            }
            $cleanImages = array_values(array_unique($cleanImages));

            if (empty($cleanImages)) {
                $cleanImages[] = 'assets/images/product_aoc_main.png';
            }

            // Detectar Garantia Estendida no código fonte da Casas Bahia
            $srcLower = strtolower($sourceCode);
            $detectedWarranty = (
                strpos($srcLower, 'garantia estendida') !== false ||
                strpos($srcLower, 'garantia extra') !== false ||
                strpos($srcLower, 'extended warranty') !== false ||
                strpos($srcLower, '"warranty"') !== false ||
                strpos($srcLower, 'warrantyoffer') !== false ||
                preg_match('/garantia.{0,20}(12|24|36)\s*(meses|mês)/i', $sourceCode)
            );

            $parsedData = [
                'title' => $extractedTitle ?: 'Produto Casas Bahia',
                'brand' => $extractedBrand ?: 'Casas Bahia',
                'sku' => $extractedSku,
                'item_code' => $extractedItemCode,
                'regular_price' => number_format($extractedRegularPrice ?: 189.90, 2, ',', '.'),
                'pix_price' => number_format($extractedPixPrice ?: 176.60, 2, ',', '.'),
                'description' => $extractedDescription,
                'specs' => $extractedSpecs,
                'specs_json' => json_encode($extractedSpecs, JSON_UNESCAPED_UNICODE),
                'variation_name' => $extractedVariations['name'],
                'variation_options' => $extractedVariations['options'],
                'variation_default' => $extractedVariations['default'],
                'images' => $cleanImages,
                'has_extended_warranty' => $detectedWarranty ? '1' : '0'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Importador de Produtos | Casas Bahia</title>
  <style>
    :root { --primary: #003399; --success: #168019; --danger: #e30613; --gray: #f5f6f8; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--gray); margin: 0; color: #333; }
    header { background: #ffffff; border-bottom: 1px solid #e0e0e0; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
    .brand { font-size: 20px; font-weight: 900; color: var(--primary); }
    .brand span { color: var(--danger); }
    .user-nav { display: flex; align-items: center; gap: 16px; font-size: 14px; }
    .btn-back { background: #eaeaea; color: #333; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; }
    .container { max-width: 960px; margin: 24px auto; padding: 0 16px; }
    .card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    h1 { font-size: 22px; font-weight: 800; color: var(--primary); margin-top: 0; margin-bottom: 8px; }
    p.subtitle { font-size: 14px; color: #666; margin-bottom: 24px; line-height: 1.5; }
    .instructions-box { background: #f0f4ff; border: 1px solid #cce0ff; border-radius: 8px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #003399; line-height: 1.5; }
    .instructions-box ol { margin: 8px 0 0 20px; padding: 0; }
    .instructions-box li { margin-bottom: 6px; }
    .form-group { margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    label { display: block; font-size: 14px; font-weight: 700; color: #444; margin-bottom: 8px; }
    input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 12px 14px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; box-sizing: border-box; outline: none; background: white; }
    textarea { height: 160px; font-family: inherit; line-height: 1.4; }
    input:focus, select:focus, textarea:focus { border-color: var(--primary); }
    .btn-submit { background: var(--success); color: white; border: none; padding: 16px 28px; border-radius: 8px; font-size: 16px; font-weight: 800; cursor: pointer; width: 100%; box-shadow: 0 4px 12px rgba(22, 128, 25, 0.2); }
    .btn-parse { background: var(--primary); color: white; border: none; padding: 16px 28px; border-radius: 8px; font-size: 16px; font-weight: 800; cursor: pointer; width: 100%; }
    .alert-success { background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #c8e6c9; font-size: 15px; }
    .alert-danger { background: #ffebee; color: #c62828; padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #ffcdd2; font-size: 15px; }
    .actions-row { display: flex; gap: 12px; margin-top: 16px; }
    .btn-action { text-align: center; flex: 1; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; }
    .btn-view-prod { background: var(--primary); color: white; }
    .btn-edit-prod { background: #444444; color: white; }
    .preview-badge { background: #e8f5e9; color: #168019; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 13px; display: inline-block; margin-bottom: 16px; }
    .img-preview-grid { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; margin-bottom: 16px; }
    
    /* CARTÃO DAS FOTOS COM REMOÇÃO AO CLICAR */
    .img-preview-card { width: 85px; height: 85px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; flex-shrink: 0; position: relative; cursor: pointer; transition: all 0.2s ease; background: #fff; }
    .img-preview-card:hover { border-color: #e30613; transform: scale(1.06); box-shadow: 0 4px 12px rgba(227, 6, 19, 0.2); }
    .img-preview-card img { width: 100%; height: 100%; object-fit: contain; }
    .remove-badge-icon { position: absolute; top: 2px; right: 2px; background: #e30613; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; opacity: 0.85; box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: transform 0.2s ease; }
    .img-preview-card:hover .remove-badge-icon { transform: scale(1.15); background: #b91c1c; opacity: 1; }

    .generator-box { background: #fdf8e6; border: 1px solid #ffeeba; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
    .generator-box h3 { margin-top: 0; font-size: 15px; color: #856404; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
  </style>
</head>
<body>

  <header>
    <div class="brand">CASAS<span>BAHIA</span> Importador de Produtos</div>
    <div class="user-nav">
      <a href="index.php" class="btn-back">← Voltar ao Painel</a>
    </div>
  </header>

  <div class="container">
    <div class="card">
      <h1>📥 Importador de Produtos (Casas Bahia)</h1>
      <p class="subtitle">Importe fotos, título, marca, preços, descrição e gere avaliações personalizadas para o seu produto.</p>

      <?php if ($successMsg): ?>
        <div class="alert-success">
          <?= $successMsg ?>
          <div class="actions-row">
            <a href="../index.php?id=<?= $importedProductId ?>" target="_blank" class="btn-action btn-view-prod">👁️ Ver Produto na Loja</a>
            <a href="product_form.php?id=<?= $importedProductId ?>" class="btn-action btn-edit-prod">✏️ Editar no Admin</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <!-- SE JÁ ANALISOU O CÓDIGO FONTE, EXIBE O FORMULÁRIO DE CONFIRMAÇÃO COM OS DADOS EXTRAÍDOS E GERADOR DE AVALIAÇÕES -->
      <?php if ($parsedData): ?>
        <div class="preview-badge">✓ Dados Extraídos com Sucesso! Confira os detalhes e configure as avaliações abaixo:</div>

        <form method="POST">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="specs_json" value="<?= htmlspecialchars($parsedData['specs_json']) ?>">
          <input type="hidden" name="has_extended_warranty" value="<?= htmlspecialchars($parsedData['has_extended_warranty'] ?? '0') ?>">
          
          <div class="form-group">
            <label for="title">Título do Produto</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($parsedData['title']) ?>" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="brand">Marca</label>
              <input type="text" name="brand" id="brand" value="<?= htmlspecialchars($parsedData['brand']) ?>" required>
            </div>
            <div class="form-group">
              <label for="sku">SKU / Código</label>
              <input type="text" name="sku" id="sku" value="<?= htmlspecialchars($parsedData['sku']) ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="regular_price">Preço no Cartão (R$)</label>
              <input type="text" name="regular_price" id="regular_price" value="<?= htmlspecialchars($parsedData['regular_price']) ?>" required>
            </div>
            <div class="form-group">
              <label for="pix_price">Preço no Pix (R$)</label>
              <input type="text" name="pix_price" id="pix_price" value="<?= htmlspecialchars($parsedData['pix_price']) ?>" required>
            </div>
          </div>

          <!-- CONFIGURAÇÃO DE VARIAÇÕES (VOLTAGEM, TAMANHO, COR) -->
          <div style="background: #f8fafc; border: 1.5px solid #d0d7de; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #333; user-select: none;">
              <input type="checkbox" id="enableVariationImp" name="enable_variation" value="1"
                onchange="toggleVariationFieldsImp()"
                <?= !empty($parsedData['variation_name']) ? 'checked' : '' ?>
                style="width: 18px; height: 18px; accent-color: #003399; cursor: pointer; flex-shrink: 0;">
              Este produto possui variações (Voltagem, Tamanho, Cor, etc.)
            </label>

            <div id="variationFieldsImp" style="<?= !empty($parsedData['variation_name']) ? '' : 'display: none;' ?> margin-top: 16px;">
              <div class="form-row-3">
                <div class="form-group" style="margin-bottom: 0;">
                  <label for="variation_name">Nome da Variação (ex: Voltagem)</label>
                  <input type="text" name="variation_name" id="variation_name" value="<?= htmlspecialchars($parsedData['variation_name'] ?? '') ?>" placeholder="Voltagem ou Tamanho">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                  <label for="variation_options">Opções (separadas por vírgula)</label>
                  <input type="text" name="variation_options" id="variation_options" value="<?= htmlspecialchars($parsedData['variation_options'] ?? '') ?>" placeholder='110V, 220V ou 43", 55"'>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                  <label for="variation_default">Opção Selecionada Padrão</label>
                  <input type="text" name="variation_default" id="variation_default" value="<?= htmlspecialchars($parsedData['variation_default'] ?? '') ?>" placeholder='220V ou 55"'>
                </div>
              </div>
            </div>
          </div>

          <script>
          function toggleVariationFieldsImp() {
            var chk = document.getElementById('enableVariationImp');
            var fields = document.getElementById('variationFieldsImp');
            if (chk.checked) {
              fields.style.display = 'block';
            } else {
              fields.style.display = 'none';
              document.getElementById('variation_name').value = '';
              document.getElementById('variation_options').value = '';
              document.getElementById('variation_default').value = '';
            }
          }
          </script>

          <div class="form-group">
            <label>
              Fotos da Galeria Encontradas (<span id="photoCountLabel"><?= count($parsedData['images']) ?></span> fotos)
              <span style="color: #e30613; font-weight: normal; margin-left: 8px;">💡 Clique em qualquer foto para removê-la da galeria</span>
            </label>
            
            <div class="img-preview-grid" id="imgPreviewGrid">
              <?php foreach ($parsedData['images'] as $imgUrl): ?>
                <div class="img-preview-card" data-url="<?= htmlspecialchars($imgUrl) ?>" onclick="removeGalleryPhoto(this)" title="Clique para remover esta foto da galeria">
                  <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Preview">
                  <div class="remove-badge-icon" title="Excluir">✕</div>
                </div>
              <?php endforeach; ?>
            </div>

            <label for="images_urls">URLs das Fotos (Uma por linha)</label>
            <textarea name="images_urls" id="images_urls" style="height: 100px; font-family: monospace; font-size: 12px;"><?= htmlspecialchars(implode("\n", $parsedData['images'])) ?></textarea>
          </div>

          <div class="form-group">
            <label>
              Especificações Técnicas &amp; Características Extraídas (<?= count($parsedData['specs']) ?> itens)
            </label>
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 13px; max-height: 180px; overflow-y: auto;">
              <?php if (!empty($parsedData['specs'])): ?>
                <table style="width: 100%; border-collapse: collapse;">
                  <?php foreach ($parsedData['specs'] as $sp): ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                      <td style="padding: 6px 8px; font-weight: bold; color: #334155; width: 35%;"><?= htmlspecialchars($sp['spec_name']) ?></td>
                      <td style="padding: 6px 8px; color: #475569;"><?= htmlspecialchars($sp['spec_value']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </table>
              <?php else: ?>
                <p style="color: #64748b; margin: 0;">Especificações padrão aplicadas com sucesso.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label for="description">Descrição do Produto Extraída</label>
            <textarea name="description" id="description"><?= htmlspecialchars($parsedData['description']) ?></textarea>
          </div>

          <!-- GERADOR AUTOMÁTICO DE AVALIAÇÕES PARA A PÁGINA DO PRODUTO -->
          <div class="generator-box">
            <h3>⭐ Gerador de Avaliações de Clientes para a Página</h3>
            <p style="font-size: 13px; color: #666; margin-bottom: 14px;">Configure a quantidade de avaliações que aparecerão na página do produto:</p>

            <div class="form-row-3">
              <div class="form-group">
                <label for="review_count">Qtd. de Avaliações</label>
                <select name="review_count" id="review_count">
                  <option value="10">10 Avaliações</option>
                  <option value="25">25 Avaliações</option>
                  <option value="50" selected>50 Avaliações</option>
                  <option value="100">100 Avaliações</option>
                  <option value="240">240 Avaliações</option>
                  <option value="350">350 Avaliações</option>
                  <option value="500">500 Avaliações</option>
                </select>
              </div>

              <div class="form-group">
                <label for="review_gender">Gênero dos Avaliadores</label>
                <select name="review_gender" id="review_gender">
                  <option value="ambos" selected>Ambos (Homem e Mulher)</option>
                  <option value="homem">Apenas Homens</option>
                  <option value="mulher">Apenas Mulheres</option>
                </select>
              </div>

              <div class="form-group">
                <label for="review_stars">Classificação</label>
                <select name="review_stars" id="review_stars">
                  <option value="4_5" selected>4 e 5 Estrelas (Misto)</option>
                  <option value="5_only">Apenas 5 Estrelas</option>
                </select>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-submit">💾 Confirmar e Cadastrar Produto no Banco MySQL</button>
        </form>

      <?php else: ?>

        <!-- FORMULÁRIO DE ENTRADA DO CÓDIGO FONTE (PASSO 1) -->
        <div class="instructions-box">
          <strong>📌 Como usar em 3 passos simples:</strong>
          <ol>
            <li>Acesse a página de qualquer produto no site oficial da <strong>Casas Bahia</strong>.</li>
            <li>Pressione <code>Ctrl + U</code> (ou clique com botão direito › <em>Exibir código fonte da página</em>) e copie todo o código (<code>Ctrl + A</code> e <code>Ctrl + C</code>).</li>
            <li>Cole o código fonte completo na caixa abaixo e clique em <strong>"Analisar e Extrair Produto"</strong>.</li>
          </ol>
        </div>

        <form method="POST">
          <input type="hidden" name="action" value="parse">
          
          <div class="form-group">
            <label for="source_code">Cole aqui o código fonte completo (HTML) da Casas Bahia:</label>
            <textarea name="source_code" id="source_code" placeholder="Cole aqui o HTML do produto..." required></textarea>
          </div>

          <button type="submit" class="btn-parse">🚀 Analisar e Extrair Produto</button>
        </form>

      <?php endif; ?>

    </div>
  </div>

  <script>
    // FUNÇÃO PARA REMOVER FOTO DA GALERIA AO CLICAR EM CIMA DELA
    function removeGalleryPhoto(el) {
      var targetUrl = el.getAttribute('data-url');
      
      // Animação de remoção suave
      el.style.transform = 'scale(0.5)';
      el.style.opacity = '0';
      
      setTimeout(function() {
        el.remove();
        
        // Atualizar contador de fotos
        var remainingCards = document.querySelectorAll('.img-preview-card').length;
        var countLabel = document.getElementById('photoCountLabel');
        if (countLabel) countLabel.textContent = remainingCards;

        // Atualizar o campo textarea de URLs
        var textarea = document.getElementById('images_urls');
        if (textarea && targetUrl) {
          var lines = textarea.value.split('\n');
          var newLines = lines.filter(function(line) {
            return line.trim() !== '' && line.trim() !== targetUrl.trim();
          });
          textarea.value = newLines.join('\n');
        }
      }, 200);
    }
  </script>
</body>
</html>
