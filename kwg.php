<?php

mb_internal_encoding('UTF-8');

// Função principal
function generateWords($classes, $syllableFormats, $substitutions, $restrictions, $minSyllables, $maxSyllables, $wordCount) {
    
    if (!is_array($classes) || !is_array($syllableFormats) || !is_array($substitutions) || !is_array($restrictions)) {
        throw new InvalidArgumentException("Parâmetros devem ser arrays.");
    }
    if (!is_int($minSyllables) || !is_int($maxSyllables) || !is_int($wordCount)) {
        throw new InvalidArgumentException("minSyllables, maxSyllables e wordCount devem ser inteiros.");
    }
    if ($minSyllables < 1 || $maxSyllables < $minSyllables || $wordCount < 1) {
        throw new InvalidArgumentException("Valores inválidos para número de sílabas ou palavras.");
    }

    $result = [];
    $generatedWords = []; // Array para rastrear palavras geradas

    // Valida formatos de sílabas
    $isFourArrays = is_array($syllableFormats[0]) && count($syllableFormats) === 4;
    $generalFormats = $isFourArrays ? [] : $syllableFormats;
    $initialFormats = $isFourArrays ? $syllableFormats[0] : [];
    $medialFormats = $isFourArrays ? $syllableFormats[1] : [];
    $finalFormats = $isFourArrays ? $syllableFormats[2] : [];
    $monosyllableFormats = $isFourArrays ? $syllableFormats[3] : [];

    // Gera cada palavra
    for ($i = 0; $i < $wordCount; $i++) {
        $maxWordAttempts = 100; // Limite de tentativas para gerar uma palavra única
        $wordAttempts = 0;
        $word = '';

        while ($wordAttempts < $maxWordAttempts) {
            // Determina o número de sílabas
            $syllableCount = rand($minSyllables, $maxSyllables);

            // Escolhe formatos de sílabas com base no número e posição
            $word = '';
            if ($syllableCount === 1 && !empty($monosyllableFormats)) {
                // Monossílaba: usa formatos de monossílabas
                $format = selectWeightedFormat($monosyllableFormats);
                $word .= generateSyllable($format, $classes, $restrictions);
            } else {
                // Palavras com 2 ou mais sílabas
                for ($j = 0; $j < $syllableCount; $j++) {
                    $formatArray = $generalFormats; // Default: formatos gerais

                    if ($isFourArrays) {
                        if ($j === 0 && $syllableCount >= 2 && !empty($initialFormats)) {
                            $formatArray = $initialFormats; // Sílaba inicial
                        } elseif ($j === $syllableCount - 1 && $syllableCount >= 2 && !empty($finalFormats)) {
                            $formatArray = $finalFormats; // Sílaba final
                        } elseif ($j > 0 && $j < $syllableCount - 1 && $syllableCount >= 3 && !empty($medialFormats)) {
                            $formatArray = $medialFormats; // Sílaba medial
                        }
                    }

                    // Se não houver formatos específicos, usa gerais
                    if (empty($formatArray)) {
                        $formatArray = $generalFormats;
                    }

                    // Gera a sílaba
                    if (!empty($formatArray)) {
                        $format = selectWeightedFormat($formatArray);
                        $word .= generateSyllable($format, $classes, $restrictions);
                    }
                }
            }

            // Verifica restrições na palavra inteira
            $maxRestrictionAttempts = 10;
            $restrictionAttempts = 0;
            while ($restrictionAttempts < $maxRestrictionAttempts && hasRestrictedPattern($word, $restrictions)) {
                $word = '';
                for ($j = 0; $j < $syllableCount; $j++) {
                    $formatArray = $generalFormats;
                    if ($isFourArrays) {
                        if ($j === 0 && $syllableCount >= 2 && !empty($initialFormats)) {
                            $formatArray = $initialFormats;
                        } elseif ($j === $syllableCount - 1 && $syllableCount >= 2 && !empty($finalFormats)) {
                            $formatArray = $finalFormats;
                        } elseif ($j > 0 && $j < $syllableCount - 1 && $syllableCount >= 3 && !empty($medialFormats)) {
                            $formatArray = $medialFormats;
                        }
                    }
                    if (!empty($formatArray)) {
                        $format = selectWeightedFormat($formatArray);
                        $word .= generateSyllable($format, $classes, $restrictions);
                    }
                }
                $restrictionAttempts++;
            }

            // Aplica substituições
            foreach ($substitutions as $sub) {
                if (!preg_match('/^\s*(\S+)\s*=>\s*(\S+)\s*$/u', $sub, $matches)) {
                    error_log("Substituição inválida: $sub");
                    continue;
                }
                $from = $matches[1];
                $to = $matches[2];
                $word = str_replace($from, $to, $word); // str_replace é seguro para UTF-8
            }

            // Verifica se a palavra é única
            if (!isset($generatedWords[$word])) {
                $generatedWords[$word] = true;
                $result[] = $word;
                break; // Sai do loop de tentativas
            }

            $wordAttempts++;
        }

        // Se não conseguiu gerar uma palavra única, loga um aviso
        if ($wordAttempts >= $maxWordAttempts) {
            error_log("Não foi possível gerar uma palavra única após $maxWordAttempts tentativas.");
            break;
        }
    }

    return $result;
}

// Função auxiliar para selecionar um formato ponderado
function selectWeightedFormat($formats) {
    // Normaliza formatos para suportar array simples ou com pesos
    $weightedFormats = [];
    foreach ($formats as $f) {
        if (is_string($f)) {
            $weightedFormats[] = ['format' => $f, 'weight' => 1];
        } elseif (is_array($f) && isset($f['format'], $f['weight'])) {
            $weightedFormats[] = ['format' => $f['format'], 'weight' => max(1, (int)$f['weight'])];
        } elseif (is_array($f) && isset($f[0], $f[1])) {
            // Suporta formato ['CV', 0.7]
            $weightedFormats[] = ['format' => $f[0], 'weight' => max(1, (float)$f[1])];
        }
    }

    // Calcula soma dos pesos
    $totalWeight = array_sum(array_column($weightedFormats, 'weight'));
    if ($totalWeight === 0) {
        return $weightedFormats[array_rand($weightedFormats)]['format'];
    }

    // Escolha ponderada
    $rand = mt_rand(0, $totalWeight * 100) / 100; // Maior precisão
    $current = 0;
    foreach ($weightedFormats as $f) {
        $current += $f['weight'];
        if ($rand < $current) {
            return $f['format'];
        }
    }

    // Fallback
    return $weightedFormats[0]['format'];
}

// Função auxiliar para gerar uma sílaba com base no formato
function generateSyllable($format, $classes, $restrictions) {
    $maxAttempts = 10;
    $attempts = 0;

    do {
        $syllable = '';
        for ($i = 0; $i < mb_strlen($format); $i++) {
            $char = mb_substr($format, $i, 1);
            if (isset($classes[$char])) {
                // Classe: escolhe um som ponderado
                $sound = selectWeightedSound($classes[$char]);
                $syllable .= $sound;
            } else {
                // Letra literal: adiciona diretamente
                if (isset($classes[$char]) && is_array($classes[$char])) {
                    $syllable .= selectWeightedSound($classes[$char]);
                } else {
                    $syllable .= $char;
                }
            }
        }
        $attempts++;
    } while ($attempts < $maxAttempts && hasRestrictedPattern($syllable, $restrictions));

    return $syllable;
}

// Função auxiliar para selecionar um som ponderado
function selectWeightedSound($sounds) {
    // Normaliza sons para suportar array simples ou com pesos
    $weightedSounds = [];
    foreach ($sounds as $s) {
        if (is_string($s)) {
            $weightedSounds[] = ['sound' => $s, 'key' => $s, 'weight' => 1];
        } elseif (is_array($s) && isset($s['sound'], $s['weight'])) {
            $weightedSounds[] = ['sound' => $s['sound'], 'key' => $s['key'], 'weight' => max(1, (float)$s['weight'])];
        } elseif (is_array($s) && isset($s[0], $s[1])) {
            // Suporta formato ['ʃ', 0.7]
            $weightedSounds[] = ['sound' => $s[0], 'key' => $s[0], 'weight' => max(1, (float)$s[1])];
        }
    }

    // Calcula soma dos pesos
    $totalWeight = array_sum(array_column($weightedSounds, 'weight'));
    if ($totalWeight === 0) {
        return $weightedSounds[array_rand($weightedSounds)]['key']; // sound
    }

    // Escolha ponderada
    $rand = mt_rand(0, $totalWeight * 100) / 100; // Maior precisão
    $current = 0;
    foreach ($weightedSounds as $s) {
        $current += $s['weight'];
        if ($rand < $current) {
            return $s['key']; // sound
        }
    }

    // Fallback
    return $weightedSounds[0]['key']; // sound
}

// Função auxiliar para verificar restrições
function hasRestrictedPattern($string, $restrictions) {
    foreach ($restrictions as $pattern => $isRestricted) {
        if ($isRestricted === false) {
            // Restrição proíbe o padrão
            if (mb_strpos($string, $pattern) !== false) {
                return true;
            }
        }
    }
    return false;
}

?>