<?php
mb_internal_encoding('UTF-8');

$erros = array();

function findLongestClass($option, $classes, $delimiters = ['{', '(', ')', '}', '_', '#', '*', '+', ',', '1', '2', '3', '4', '5', '6', '7', '8', '9', '[', ']', '~']) {
    $len = mb_strlen($option);
    $potentialClasses = [];
    $potentialClass = '';
    $j = 0;

    while ($j < $len && !in_array(mb_substr($option, $j, 1), $delimiters)) {
        $potentialClass .= mb_substr($option, $j, 1);
        if (isset($classes[$potentialClass])) {
            $potentialClasses[$potentialClass] = $j + 1;
        }
        $j++;
    }

    if (!empty($potentialClasses)) {
        $longestClass = '';
        $nextPos = 0;
        foreach ($potentialClasses as $class => $pos) {
            if (mb_strlen($class) > mb_strlen($longestClass)) {
                $longestClass = $class;
                $nextPos = $pos;
            }
        }
        return [$longestClass, $nextPos];
    }

    return ['', 0];
}

function applySoundChanges($lines, $rules, $substitutions = [], $classes = [], $rulesPerLines = null) { 
    if (is_string($lines)) {
        $inputLines = [$lines];
        $wasString = true;
    } else {
        $inputLines = is_array($lines) ? $lines : [$lines];
        $wasString = false;
    }

    $classes = expandNestedClasses($classes);

    $ruleGroups = is_array($rules) && !isset($rules[0]) ? $rules : ['default' => $rules];

    $uniqueChars = [];
    
    foreach ($inputLines as $line) {
        $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
        $uniqueChars = array_merge($uniqueChars, $chars);
    }

    foreach ($classes as $className => $chars) {
        $uniqueChars = array_merge($uniqueChars, $chars);
    }

    foreach ($ruleGroups as $groupName => $groupRules) {
        foreach ($groupRules as $rule) {
            if (preg_match('/^\s*([^=\/]*)\s*(?:→|>|=>|\/)\s*([^\/]*)\s*(?:\/\s*([^\/]*))?(?:\s*\/\s*(.*))?\s*$/u', trim($rule), $matches)) {
                $source = trim($matches[1]);
                $target = trim($matches[2]);
                $contexts = isset($matches[3]) && $matches[3] !== '' ? splitIgnoringBraces($matches[3]) : [];
                $exclusions = isset($matches[4]) && $matches[4] !== '' ? splitIgnoringBraces($matches[4]) : [];

                $uniqueChars = array_merge($uniqueChars, preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY));
                $uniqueChars = array_merge($uniqueChars, preg_split('//u', $target, -1, PREG_SPLIT_NO_EMPTY));

                foreach ($contexts as $context) {
                    $uniqueChars = array_merge($uniqueChars, preg_split('//u', $context, -1, PREG_SPLIT_NO_EMPTY));
                }

                foreach ($exclusions as $exclusion) {
                    $uniqueChars = array_merge($uniqueChars, preg_split('//u', $exclusion, -1, PREG_SPLIT_NO_EMPTY));
                }
            } elseif (!preg_match('/^(\p{Lu}\p{Ll}*)\s*=/u', $rule)) {
                $erros[] = "Regra inválida ignorada ao coletar caracteres: $rule";
            }
        }
    }

    $uniqueChars = array_unique($uniqueChars);
    $reservedChars = ['∅', 'ø', '→', ':', '=>', ' ', "\n", "\t", "\r", '=', '/', '_', '#', '<', '>', '(', ')', '[', ']', '{', '}', ',', '?', '*', '.', '~', '|', '+'];

    $ignoredChars = ['´', '`', '^', '¨',"'"];
    $uniqueChars = array_diff($uniqueChars, $reservedChars, $ignoredChars);

    $classes['?'] = array_values($uniqueChars);

    $result = [];
    $intermediateResults = []; // Novo: armazenar formas intermediárias
    $intermediateRules = []; // Novo: armazenar as regras correspondentes
    $numLines = count($inputLines);
    $numBlocks = count($ruleGroups);
    $blockKeys = array_keys($ruleGroups);
    $intermediateResults['input'] = array_filter($inputLines);
    $intermediateRules['input'] = _t('Entrada');

    if ($rulesPerLines !== null) {
        // Modo seletivo: aplicar blocos de regras a grupos de linhas
        $requiredLines = $numBlocks * $rulesPerLines;

        // Verificar consistência
        if ($numLines < $requiredLines) {
            $erros[] = "Aviso: Número de linhas ($numLines) insuficiente para $numBlocks blocos com $rulesPerLines linhas por bloco. Usando comportamento padrão.";
            $rulesPerLines = null;
        } elseif ($numLines > $requiredLines) {
            $erros[] = "Aviso: Sobram linhas ($numLines > $requiredLines). Último bloco será reutilizado.";
        }
    }

    foreach ($inputLines as $line) {

        preg_match('/[\s\t]+/', $line, $separatorMatches);
        $separator = $separatorMatches ? $separatorMatches[0] : ' ';

        $words = preg_split('/[\s\t]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            $result[] = $line;
            continue;
        }

        $transformedWords = $words;
        $lineClasses = $classes;

        if ($rulesPerLines === null) {
            foreach ($ruleGroups as $groupName => $groupRules) {
                $tempWords = [];
                foreach ($transformedWords as $word) {
                    $newWord = $word;
                    foreach ($groupRules as $ruleIndex => $rule) {
                        // Verifica se a regra é uma definição de classe (ex.: X=abc ou Á=[á,é,ó]) // 
                        
                        if (preg_match('/^(\p{Lu}\p{Ll}*)\s*=\s*(\{[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+(?:\s*,\s*[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+)*\s*\}|[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+|[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+(?:\s*,\s*[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+)*)\s*$/u', $rule, $matches)) {
                            $className = $matches[1]; // Letra maiúscula ou acentuada (ex.: Á)
                            $classValue = $matches[2]; // Valor (ex.: [á,é,ó] ou áéó)

                            // Processa o valor da classe '/^\{([^\}]*)\}\s*$/u'
                            if (preg_match('/^\{([^\}]*)\}\s*$/u', $classValue, $listMatches)) {
                                // Formato [á,é,ó]: divide por vírgulas, remove espaços
                                $characters = array_map('trim', explode(',', $listMatches[1]));
                                $characters = array_filter($characters); // Remove elementos vazios
                            } elseif (mb_strpos($classValue,",")>0){
                                // Formato a,b,c: divide por vírgulas, remove espaços
                                $characters = array_map('trim', explode(',', $classValue));
                                $characters = array_filter($characters); // Remove elementos vazios
                            } else {
                                // Formato áéó: divide em caracteres individuais
                                $characters = preg_split('//u', $classValue, -1, PREG_SPLIT_NO_EMPTY);
                            }
                            // Atualiza a classe
                            $lineClasses[$className] = $characters;
                            continue; // Pula a aplicação da regra
                        }
                        $newWord = applySingleRule($newWord, $rule, $lineClasses);
                        // Armazenar forma intermediária e a regra correspondente
                        $intermediateResults["{$groupName}_rule_{$ruleIndex}"][] = $newWord;
                        if ($wordIndex == 0) { // Armazenar a regra apenas uma vez por grupo
                            $intermediateRules["{$groupName}_rule_{$ruleIndex}"] = $rule;
                        }
                    }
                    $tempWords[] = $newWord;
                }
                $transformedWords = $tempWords;
                //$intermediateResults["after_group_{$groupName}"][] = implode($separator, $tempWords);
            }
        }else{
            $blockIndex = floor($lineIndex / $rulesPerLines);
            if ($blockIndex >= $numBlocks) {
                $blockIndex = $numBlocks - 1; // Reusar último bloco
            }
            $blockName = $blockKeys[$blockIndex];
            $groupRules = $ruleGroups[$blockName];

            $tempWords = [];
            foreach ($transformedWords as $word) {
                $newWord = $word;
                foreach ($groupRules as $ruleIndex => $rule) {
                    if (preg_match('/^(\p{Lu}\p{Ll}*)\s*=\s*(\{[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+(?:\s*,\s*[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+)*\s*\}|[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+|[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+(?:\s*,\s*[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+)*)\s*$/u', $rule, $matches)) {
                        $className = $matches[1];
                        $classValue = $matches[2];
                        if (preg_match('/^\{([^\}]*)\}\s*$/u', $classValue, $listMatches)) {
                            $characters = array_map('trim', explode(',', $listMatches[1]));
                            $characters = array_filter($characters);
                        } elseif (mb_strpos($classValue, ",") > 0) {
                            $characters = array_map('trim', explode(',', $classValue));
                            $characters = array_filter($characters);
                        } else {
                            $characters = preg_split('//u', $classValue, -1, PREG_SPLIT_NO_EMPTY);
                        }
                        $lineClasses[$className] = $characters;
                        continue;
                    }
                    $newWord = applySingleRule($newWord, $rule, $lineClasses);
                    // Armazenar forma intermediária e a regra correspondente
                    $intermediateResults["{$groupName}_rule_{$ruleIndex}"][] = $newWord;
                    if ($wordIndex == 0) { // Armazenar a regra apenas uma vez por grupo
                        $intermediateRules["{$groupName}_rule_{$ruleIndex}"] = $rule;
                    }
                }
                $tempWords[] = $newWord;
            }
            $transformedWords = $tempWords;
            //$intermediateResults["after_group_{$groupName}"][] = implode($separator, $tempWords);
        }

        foreach ($substitutions as $sub) {
            if (preg_match('/^\s*(\S+)\s*=>\s*(\S+)\s*$/u', $sub, $matches)) {
                $from = $matches[1];
                $to = $matches[2];
                $transformedWords = array_map(function($word) use ($from, $to) {
                    return str_replace($from, $to, $word);
                }, $transformedWords);
            }
        }

        $result[] = implode($separator, $transformedWords);
    }

    if ($wasString) {
        return [$result[0], $erros, ['intermediate' => $intermediateResults, 'rules' => $intermediateRules]];;
    }
    return [$result,$erros,['intermediate' => $intermediateResults, 'rules' => $intermediateRules]];
}

function applySingleRule($word, $rule, $classes) {
    $rule = preserveSpacesInBrackets($rule);
    $rule = preg_replace('/\s+/u', '', trim($rule));
    
    if (!preg_match('/^\s*([^=\/]*)\s*(?:→|>|=>|\/)\s*([^\/]*)\s*(?:\/\s*([^\/]*))?(?:\s*\/\s*(.*))?\s*$/u', trim($rule), $matches)) {
        $erros[] = "Regra inválida: $rule";
        return $word;
    }

    $source = trim($matches[1]);
    $target = trim($matches[2]);
    $contexts = isset($matches[3]) && $matches[3] !== '' ? splitIgnoringBraces($matches[3]) : ['_'];
    $exclusions = isset($matches[4]) && $matches[4] !== '' ? splitIgnoringBraces($matches[4]) : [];

    $len = mb_strlen($word);

    $source = convertBracketedClasses($source, $classes);
    $target = convertBracketedClasses($target, $classes);
    $contexts = array_map(function($context) use ($classes) {
        return convertBracketedClasses($context, $classes);
    }, $contexts);
    $exclusions = array_map(function($exclusion) use ($classes) {
        return convertBracketedClasses($exclusion, $classes);
    }, $exclusions);

    $maxRepetitions = $len-1; // Limite de repetições min($len, 3); // IDEIA é se tá no inicio da palavra e contexto é antes do _, ignorar caracteres a mais!
    $contexts = array_map(function($context) use ($classes, $maxRepetitions) {
        return rewriteRepeater($context, $classes, $maxRepetitions);
    }, $contexts);
    $exclusions = array_map(function($exclusion) use ($classes, $maxRepetitions) {
        return rewriteRepeater($exclusion, $classes, $maxRepetitions);
    }, $exclusions);

    list($sourceGroups, $captureGroups) = processSource($source, $classes);
    $targetValue = processTarget($target, $sourceGroups, $captureGroups, $source, $classes);

    $isInsertion = $source === '~' || $source === '' || $source === '∅' || $source === 'ø' || $source === '0';
    $isRemoval = $target === '~' || $target === '' || $target === '∅' || $target === 'ø' || $target === '0';

    $newWord = '';
    $i = 0;
    $appliedPositions = [];
    $insertionApplied = [];

    //error_log("Processing word: '$word' with rule: '$rule'");
    //error_log("Target value: " . json_encode($targetValue));

    while ($i < $len) {
        $currentChar = mb_substr($word, $i, 1);
        $applied = false;

        //error_log("Checking position $i (char: '$currentChar')");

        foreach (generateGroupCombinations($sourceGroups) as $combination) {
            $src = '';
            $srcLen = 0;
            $isCurrentInsertion = false;
            $matchedChars = [];
            $classMatched = false;
            $capturedValues = [];

            foreach ($combination as $group) {
                if ($group['type'] === 'insertion') {
                    $isCurrentInsertion = true;
                    $matchedChars[] = '~';
                    $capturedValues[$group['capture']] = '~';
                } else {
                    $src .= $group['value'];
                    $srcLen += mb_strlen($group['value']);
                    $matchedChars[] = $group['value'];
                    $capturedValues[$group['capture']] = $group['value'];
                    if ($group['type'] === 'class' || $group['type'] === 'capture') {
                        $classMatched = true;
                    }
                }
            }

            //error_log("Testing combination: src='$src', matchedChars=" . json_encode($matchedChars) . ", capturedValues=" . json_encode($capturedValues));

            if ($isCurrentInsertion && isset($insertionApplied[$i])) {
                //error_log("Skipping: Insertion already applied at position $i");
                continue;
            }

            // Verifica correspondência exata do caractere atual
            if (!$isCurrentInsertion && ($i + $srcLen > $len || mb_substr($word, $i, $srcLen) !== $src)) {
                //error_log("No match: mb_substr('$word', $i, $srcLen) = '" . mb_substr($word, $i, $srcLen) . "' !== '$src'");
                continue;
            }

            if ($isCurrentInsertion && !$classMatched) {
                //error_log("Skipping: Insertion requires class match");
                //continue;
            }

            if (!empty($captureGroups) && isset($appliedPositions[$i])) {
                //error_log("Skipping: Position $i already applied");
                continue;
            }

            $contextValid = false;

            //echo "<br>\n===Rule: ".$rule." === Word: $word===<br>\n"; 

            foreach ($contexts as $context) {
                if ($context === '_') {
                    $contextValid = true;
                    //error_log("Context '_' is valid");
                    break;
                }

                $contextValid = checkContexts($context, $classes, $capturedValues, $word, $i, $srcLen, $len, $targetValue);
                if ($contextValid) break;                
            }

            if ($contextValid && !empty($exclusions)) {
                foreach ($exclusions as $exclusion) {

                    $contextValid = !checkContexts($exclusion, $classes, $capturedValues, $word, $i, $srcLen, $len, $targetValue);
                    if ($contextValid) break;
                }
            }

            if ($contextValid  && mb_substr($word, $i, mb_strlen($src)) === $src) {
                //echo "mb_substr($word, $i, mb_strlen($src))=".mb_substr($word, $i, mb_strlen($src))." === $src\n";
                $replacement = '';
                if ($isRemoval) {
                    $newWord .= '';
                } elseif (is_array($targetValue) && isset($targetValue['type']) && $targetValue['type'] === 'capture') {
                    $replacement = ''; //$targetValue['value'];
                    $classMapping = $targetValue['classMapping'] ?? [];

                    /*
                    foreach ($capturedValues as $captureIndex => $captureValue) {
                        $replacement = str_replace($captureIndex, $captureValue, $replacement);
                    }
                    foreach ($classMapping as $srcClass => $mapping) {
                        foreach ($mapping as $srcValue => $tgtValue) {
                            if (isset($capturedValues[$captureGroups[array_search($srcClass, $captureGroups)]]) &&
                                $capturedValues[$captureGroups[array_search($srcClass, $captureGroups)]] === $srcValue) {
                                $replacement = str_replace($srcClass, $tgtValue, $replacement);
                            }
                        }
                    }
                    */
                    foreach ($targetValue['groups'] as $tgtGroup) {
                        if ($tgtGroup['type'] === 'capture') {
                            $captureIndex = $tgtGroup['value'];
                            if (isset($capturedValues[$captureIndex])) {
                                $replacement .= $capturedValues[$captureIndex];
                            } else {
                                //error_log("Erro: Captura $captureIndex não encontrada em capturedValues");
                                $replacement .= $captureIndex;
                            }
                        } elseif ($tgtGroup['type'] === 'class' && isset($classes[$tgtGroup['value'][0]])) {
                            $className = $tgtGroup['value'][0];
                            // Encontrar a classe de origem correspondente
                            $srcClass = null;
                            for ($j = count($sourceGroups) - 1; $j >= 0; $j--) {
                                if ($sourceGroups[$j]['type'] === 'class' && isset($classes[$sourceGroups[$j]['value'][0]])) {
                                    $srcClass = $sourceGroups[$j]['value'][0];
                                    break;
                                }
                            }
                            if ($srcClass && isset($classMapping[$srcClass])) {
                                $srcCaptureIndex = array_search($srcClass, $captureGroups);
                                if ($srcCaptureIndex !== false && isset($capturedValues[$srcCaptureIndex])) {
                                    $srcValue = $capturedValues[$srcCaptureIndex];
                                    $mappedValue = $classMapping[$srcClass][$srcValue] ?? $srcValue;
                                    $replacement .= $mappedValue;
                                } else {
                                    $replacement .= $classes[$className][0]; // Fallback
                                    //error_log("Aviso: Captura para classe $srcClass não encontrada");
                                }
                            } else {
                                $replacement .= $classes[$className][0]; // Fallback
                                //error_log("Aviso: Mapeamento para classe $className não encontrado");
                            }
                        } else {
                            $replacement .= $tgtGroup['value'][0];
                        }
                    }


                    /*
                    foreach ($combination as $group) {
                        $captureIndex = $group['capture'];
                        $captureValue = $group['value'];
                        $replacement = str_replace($captureIndex, $captureValue, $replacement);
                    }
                    */

                    $newWord .= $replacement;
                    $appliedPositions[$i] = true;
                } elseif (is_array($targetValue)) {
                    $replacement = '';
                    foreach ($matchedChars as $char) {
                        $replacement .= $targetValue[$char] ?? $char;
                    }
                    $newWord .= $replacement;
                } else {
                    $newWord .= $targetValue;
                }
                // Avança o índice apenas pelo comprimento dos caracteres reais (exclui ~)
                $i += $srcLen; // $i += $isCurrentInsertion && $srcLen === 0 ? 0 : $srcLen;
                if ($isCurrentInsertion) {
                    $insertionApplied[$i] = true;
                }
                $applied = true;
                //echo ("Applying rule '$rule' to '$word' at position $i: src='$src', matchedChars=" . json_encode($matchedChars) . ", replacement='$replacement'");
                break;
            }
        }

        if (!$applied && $i < $len) {
            $newWord .= mb_substr($word, $i, 1);
            //error_log("No rule applied at position $i, copying char: '$currentChar'");
            $i++;
        }
    }

    //error_log("Result: '$newWord'");
    return $newWord;
}

function processSource($source, $classes) { // abre origens - conferir capturas 
    $sourceGroups = [];
    $captureGroups = [];
    $groupIndex = 1;

    if ($source === '' || $source === '~' || $source === 'ø' || $source === '0' || $source === '∅') {
        $sourceGroups = [['type' => 'insertion', 'value' => ['~'], 'capture' => $groupIndex]];
        $captureGroups[$groupIndex] = '~';
        return [$sourceGroups, $captureGroups];
    }

    $source = preserveSpacesInBrackets($source);
    $source = preg_replace('/\s+/u', '', $source);

    $len = mb_strlen($source);
    $i = 0;
    while ($i < $len) {
        $char = mb_substr($source, $i, 1);
        $currentGroup = ['type' => 'literal', 'value' => [], 'capture' => $groupIndex];

        if ($char === '{') {
            $end = mb_strpos($source, '}', $i);
            if ($end !== false) {
                $list = mb_substr($source, $i, $end - $i + 1);
                if (preg_match('/^\{([^\]]+)\}$/u', $list, $match)) {
                    $options = array_map('trim', explode(',', $match[1]));
                    $expanded = [];
                    foreach ($options as $option) {
                        [$longestClass, $nextPos] = findLongestClass($option, $classes, ['{', '(', ')', '}', '[', ']', '~', ',']);
                        if ($longestClass && $nextPos === mb_strlen($option)) {
                            $expanded = array_merge($expanded, $classes[$longestClass]);
                        } else {
                            $expanded[] = $option;
                        }
                    }
                    $currentGroup['type'] = 'class';
                    $currentGroup['value'] = $expanded;
                    $sourceGroups[] = $currentGroup;
                    $captureGroups[$groupIndex] = $match[1];
                    $groupIndex++;
                    $i = $end + 1;
                    continue;
                }
            }
        } elseif ($char === '~'  || $char === 'ø' || $char === '0' || $char === '∅') {
            $currentGroup['type'] = 'insertion';
            $currentGroup['value'] = ['~'];
            $sourceGroups[] = $currentGroup;
            $captureGroups[$groupIndex] = '~';
            $groupIndex++;
            $i++;
            continue;
        } elseif (preg_match('/^[1-9]$/', $char)) { // $char < 10
            $captureIndex = intval($char);
            if ($captureIndex >= $groupIndex) {
                $erros[] = "Erro: Captura $char referencia grupo inexistente em $source";
                return [[], []];
            }
            $currentGroup['type'] = 'capture';
            $currentGroup['value'] = [$char];
            $currentGroup['refers_to'] = $captureIndex;
            $sourceGroups[] = $currentGroup;
            $captureGroups[$groupIndex] = $char;
            $groupIndex++;
            $i++;
            continue;
        } elseif ($char === '(') {
            $end = mb_strpos($source, ')', $i);
            if ($end !== false) {
                $optional = mb_substr($source, $i + 1, $end - $i - 1);
                $currentGroup['type'] = 'optional';
                $currentGroup['value'] = [$optional, ''];
                $sourceGroups[] = $currentGroup;
                $captureGroups[$groupIndex] = $optional;
                $groupIndex++;
                $i = $end + 1;
                continue;
            }
        } else{
            /*
            $potentialClass = '';
            $j = $i;
            while ($j < $len && !in_array(mb_substr($source, $j, 1), ['{', '[', '(', '~', '1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                $potentialClass .= mb_substr($source, $j, 1);
                if (isset($classes[$potentialClass])) {
                    $currentGroup['type'] = 'class';
                    $currentGroup['value'] = $classes[$potentialClass];
                    $sourceGroups[] = $currentGroup;
                    $captureGroups[$groupIndex] = $potentialClass;
                    $groupIndex++;
                    $i = $j + 1;
                    continue 2;
                }
                $j++;
            }
            */
            [$longestClass, $nextPos] = findLongestClass(mb_substr($source, $i), $classes);
            if ($longestClass) {
                $currentGroup['type'] = 'class';
                $currentGroup['value'] = $classes[$longestClass];
                $sourceGroups[] = $currentGroup;
                $captureGroups[$groupIndex] = $longestClass;
                $groupIndex++;
                $i += $nextPos;
                continue;
            }

            $currentGroup['type'] = 'literal';
            $currentGroup['value'] = [$char];
            $sourceGroups[] = $currentGroup;
            $captureGroups[$groupIndex] = $char;
            $groupIndex++;
            $i++;
        }
    }

    if ($groupIndex > 10) {
        $erros[] = "Erro: Mais de 9 grupos de captura em $source";
        return [[], []];
    }

    //error_log("Source '$source' processed as: " . json_encode($sourceGroups) . ", captureGroups: " . json_encode($captureGroups));
    return [$sourceGroups, $captureGroups];
}

function processTarget($target, $sourceGroups, $captureGroups, $source, $classes) {
    if ($target === '~' || $target === '') {
        return '';
    }

    $targetGroups = processPattern($target, $classes);
    if (empty($targetGroups)) {
        //error_log("Erro: Nenhum grupo processado para target '$target'");
        return $target;
    }

    $hasInsertion = in_array('insertion', array_column($sourceGroups, 'type'));
    $hasCapture = in_array('capture', array_column($sourceGroups, 'type')) || in_array('capture', array_column($targetGroups, 'type'));

    //if ($hasCapture) {
        // Marcar destino como contendo capturas para substituição posterior
        //return ['type' => 'capture', 'value' => $target, 'groups' => $targetGroups];
    //}

    $classMapping = [];
    foreach ($targetGroups as $tgtGroup) {
        if ($tgtGroup['type'] === 'class' && isset($classes[$tgtGroup['value'][0]])) {
            $targetClass = $tgtGroup['value'][0];
            // Procura uma classe correspondente na origem (ex.: V na origem para T no destino)
            /*
            foreach ($sourceGroups as $srcGroup) {
                if ($srcGroup['type'] === 'class' && isset($classes[$srcGroup['value'][0]])) {
                    $sourceClass = $srcGroup['value'][0];
                    // Mapeia valores das classes por posição
                    $srcValues = $classes[$sourceClass];
                    $tgtValues = $classes[$targetClass];
                    for ($i = 0; $i < count($srcValues); $i++) {
                        if (isset($tgtValues[$i])) {
                            $classMapping[$sourceClass][$srcValues[$i]] = $tgtValues[$i];
                        }
                    }
                    //error_log("Class mapping: $sourceClass -> $targetClass: " . json_encode($classMapping[$sourceClass]));
                }
            }
            */
            $sourceClass = null;
            for ($i = count($sourceGroups) - 1; $i >= 0; $i--) {
                if ($sourceGroups[$i]['type'] === 'class' && isset($classes[$sourceGroups[$i]['value'][0]])) {
                    $sourceClass = $sourceGroups[$i]['value'][0];
                    break;
                }
            }
            if ($sourceClass) {
                $srcValues = $classes[$sourceClass];
                $tgtValues = $classes[$targetClass];
                for ($i = 0; $i < count($srcValues); $i++) {
                    if (isset($tgtValues[$i])) {
                        $classMapping[$sourceClass][$srcValues[$i]] = $tgtValues[$i];
                    } else {
                        $classMapping[$sourceClass][$srcValues[$i]] = $srcValues[$i]; // Fallback
                    }
                }
                //error_log("Class mapping: $sourceClass -> $targetClass: " . json_encode($classMapping[$sourceClass]));
            }
        }


    }

    if ($hasCapture || !empty($classMapping)) {
        // Marcar destino como contendo capturas ou mapeamento de classes
        return ['type' => 'capture', 'value' => $target, 'groups' => $targetGroups, 'classMapping' => $classMapping];
    }

    $mapping = [];
    $targetIndex = 0;
    //echo json_encode($sourceGroups);
    //echo json_encode($targetGroups);

    foreach ($sourceGroups as $srcGroup) {
        if ($targetIndex >= count($targetGroups) || count($sourceGroups) != count($targetGroups)) {
            //error_log("Erro: Target '$target' tem menos grupos que source '$source'");
            return $target;
        }
        //echo json_encode($srcGroup);

        if ($srcGroup['type'] === 'insertion') {
            if ($targetGroups[$targetIndex]['type'] === 'literal') {
                $mapping['~'] = $targetGroups[$targetIndex]['value'][0]; // Ex.: 'w'
                //error_log("Mapping insertion: '~' => '" . $mapping['~'] . "'");
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'insertion') {
                $mapping['~'] = '';
                $targetIndex++;
            }
        } elseif ($srcGroup['type'] === 'class') {
            if ($targetGroups[$targetIndex]['type'] === 'class') {
                $srcValues = $srcGroup['value']; // Ex.: ['k', 'g']
                $tgtValues = $targetGroups[$targetIndex]['value']; // Ex.: ['g', 'k']
                //error_log("Mapping class: srcValues=" . json_encode($srcValues) . ", tgtValues=" . json_encode($tgtValues));
                for ($i = 0; $i < count($srcValues); $i++) {
                    $mapping[$srcValues[$i]] = $tgtValues[$i] ?? $srcValues[$i];
                }
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'insertion') {
                $srcValues = $srcGroup['value']; // Ex.: ['k', 'g']
                $tgtValues = $targetGroups[$targetIndex]['value']; // Ex.: ['g', 'k']
                //error_log("Mapping class: srcValues=" . json_encode($srcValues) . ", tgtValues=" . json_encode($tgtValues));
                for ($i = 0; $i < count($srcValues); $i++) {
                    $mapping[$srcValues[$i]] = '';
                }
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'literal') {
                $srcValues = $srcGroup['value']; // Ex.: ['k', 'g']
                $tgtValues = $targetGroups[$targetIndex]['value']; // Ex.: ['g', 'k']
                //error_log("Mapping class: srcValues=" . json_encode($srcValues) . ", tgtValues=" . json_encode($tgtValues));
                for ($i = 0; $i < count($srcValues); $i++) {
                    $mapping[$srcValues[$i]] = $targetGroups[$targetIndex]['value'][0];
                }
                $targetIndex++;
            }
        } elseif ($srcGroup['type'] === 'literal') {
            if ($targetGroups[$targetIndex]['type'] === 'literal') {
                $mapping[$srcGroup['value'][0]] = $targetGroups[$targetIndex]['value'][0]; // Ex.: 'k' => 'g'
                //error_log("Mapping $targetIndex literal: '" . $srcGroup['value'][0] . "' => '" . $targetGroups[$targetIndex]['value'][0] . "'");
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'insertion') {
                $mapping['~'] = '';
                $targetIndex++;
            }
        }
    }
    // echo json_encode($mapping); die();
    
    if (!empty($mapping)) {
        //echo("Target mapping for '$target': " . json_encode($mapping));
        return $mapping;
    }

    // Mapeamento grupo a grupo se o número de grupos for igual (sem inserção)
    if (count($sourceGroups) === count($targetGroups) && !$hasInsertion) {
        $mapping = [];
        for ($i = 0; $i < count($sourceGroups); $i++) {
            $srcGroup = $sourceGroups[$i];
            $tgtGroup = $targetGroups[$i];

            if (empty($srcGroup['value']) || empty($tgtGroup['value'])) {
                $erros[] = "Erro: Grupo vazio no source ou target. Source: " . print_r($srcGroup, true) . ", Target: " . print_r($tgtGroup, true);
                continue;
            }

            if ($tgtGroup['type'] === 'insertion' || ($tgtGroup['type'] === 'literal' && $tgtGroup['value'][0] === '~')) {
                $mapping[$srcGroup['value'][0]] = '';
            } elseif ($srcGroup['type'] === 'insertion' || ($srcGroup['type'] === 'literal' && $srcGroup['value'][0] === '~')) {
                $mapping['~'] = $tgtGroup['value'][0];
            } elseif ($srcGroup['type'] === 'class' && $tgtGroup['type'] === 'class') { 
                if (count($srcGroup['value']) === count($tgtGroup['value'])) {
                    for ($j = 0; $j < count($srcGroup['value']); $j++) {
                        $mapping[$srcGroup['value'][$j]] = $tgtGroup['value'][$j];
                    }
                } else {
                    for ($j = 0; $j < count($srcGroup['value']); $j++) {
                        $mapping[$srcGroup['value'][$j]] = $tgtGroup['value'][0];
                    }
                }
            } elseif ($srcGroup['type'] === 'literal' && $tgtGroup['type'] === 'literal') {
                $mapping[$srcGroup['value'][0]] = $tgtGroup['value'][0];
            } elseif ($srcGroup['type'] === 'class' && $tgtGroup['type'] === 'literal') {
                for ($j = 0; $j < count($srcGroup['value']); $j++) {
                    $mapping[$srcGroup['value'][$j]] = $tgtGroup['value'][0];
                }
            } else {
                //error_log("Aviso: Tipos incompatíveis no mapeamento. Source: " . print_r($srcGroup, true) . ", Target: " . print_r($tgtGroup, true));
                $mapping[$srcGroup['value'][0]] = $tgtGroup['value'][0];
            }
        }
        //error_log("Target mapping for '$target': " . json_encode($mapping));
        return $mapping;
    }

    // Fallback para classes ou literais
    if (preg_match('/^\{([^\}]+)\}(\p{M}*)$/u', $target, $match)) {
        $elements = array_map('trim', explode(',', $match[1]));
        $modifier = $match[2] ?? '';
        return $elements[0] . $modifier;
    } elseif (isset($classes[$target])) {
        return $classes[$target][0];
    }

    //error_log("Aviso: Retornando target '$target' como fallback");
    return $target;
}

function generateGroupCombinations($groups) {
    $combinations = [[]];
    foreach ($groups as $index => $group) {
        $newCombinations = [];
        foreach ($combinations as $combo) {
            if ($group['type'] === 'optional') {
                foreach ($group['value'] as $value) {
                    $newCombo = $combo;
                    $newCombo[] = ['value' => $value, 'type' => 'optional', 'capture' => $group['capture']];
                    $newCombinations[] = $newCombo;
                }
            } elseif ($group['type'] === 'insertion') {
                $newCombo = $combo;
                $newCombo[] = ['value' => '~', 'type' => 'insertion', 'capture' => $group['capture']];
                $newCombinations[] = $newCombo;
            } elseif ($group['type'] === 'capture') {
                // Captura referencia um grupo anterior
                $refIndex = $group['refers_to'] - 1;
                if ($refIndex < 0 || $refIndex >= count($groups)) {
                    $erros[] = "Erro: Captura referencia índice inválido: " . $group['refers_to'];
                    continue;
                }
                $refGroup = $groups[$refIndex];
                // Usa os mesmos valores do grupo referenciado
                foreach ($refGroup['value'] as $value) {
                    $newCombo = $combo;
                    $newCombo[] = ['value' => $value, 'type' => 'capture', 'capture' => $group['capture'], 'refers_to' => $group['refers_to']];
                    $newCombinations[] = $newCombo;
                }
                /*
                $newCombo = $combo;
                $newCombo[] = ['value' => '*', 'type' => 'capture', 'capture' => $group['capture']]; // * representa qualquer caractere
                $newCombinations[] = $newCombo;
                */
            } else { // literal, class, capture
                foreach ($group['value'] as $value) {
                    $newCombo = $combo;
                    $newCombo[] = ['value' => $value, 'type' => $group['type'], 'capture' => $group['capture']];
                    $newCombinations[] = $newCombo;
                }
            }
        }
        $combinations = $newCombinations;
    }
    return $combinations;
}

function processPattern($pattern, $classes) {
    $groups = [];
    $len = mb_strlen($pattern);
    $i = 0;
    while ($i < $len) {
        $char = mb_substr($pattern, $i, 1);
        $currentGroup = ['type' => 'literal', 'value' => []];

        if ($char === '{') {
            $end = mb_strpos($pattern, '}', $i);
            if ($end !== false) {
                $list = mb_substr($pattern, $i, $end - $i + 1);
                if (preg_match('/^\{([^\}]+)\}$/u', $list, $match)) {
                    $options = array_map('trim', explode(',', $match[1]));
                    $expanded = [];
                    foreach ($options as $option) {
                        [$longestClass, $nextPos] = findLongestClass($option, $classes, ['{', '(', ')', '}', '[', ']', '~', ',']);
                        if ($longestClass && $nextPos === mb_strlen($option)) {
                            $expanded = array_merge($expanded, $classes[$longestClass]);
                        } else {
                            $expanded[] = $option;
                        }
                    }
                    $currentGroup['type'] = 'class';
                    $currentGroup['value'] = $expanded;
                    $groups[] = $currentGroup;
                    $i = $end + 1;
                    continue;
                }
            }
        } elseif ($char === '~' || $char === 'ø' || $char === '0' || $char === '∅') {
            $currentGroup['type'] = 'insertion';
            $currentGroup['value'] = ['~'];
            $groups[] = $currentGroup;
            $i++;
            continue;
        } elseif ($char < 10) {
            $currentGroup['type'] = 'capture';
            $currentGroup['value'] = $char;
            $groups[] = $currentGroup;
            $i++;
            continue;
        } elseif ($char === '(') {
            $end = mb_strpos($pattern, ')', $i);
            if ($end !== false) {
                $optional = mb_substr($pattern, $i + 1, $end - $i - 1);
                $currentGroup['type'] = 'optional';
                $currentGroup['value'] = [$optional, ''];
                $groups[] = $currentGroup;
                $i = $end + 1;
                continue;
            }
        } else {
            /*
            $potentialClass = '';
            $j = $i;
            while ($j < $len && !in_array(mb_substr($pattern, $j, 1), ['{', '(', '[', '~'])) {
                $potentialClass .= mb_substr($pattern, $j, 1);
                if (isset($classes[$potentialClass])) {
                    $currentGroup['type'] = 'class';
                    $currentGroup['value'] = $classes[$potentialClass];
                    $groups[] = $currentGroup;
                    $i = $j + 1;
                    continue 2;
                }
                $j++;
            }
            */
            [$longestClass, $nextPos] = findLongestClass(mb_substr($pattern, $i), $classes);
            if ($longestClass) {
                $currentGroup['type'] = 'class';
                $currentGroup['value'] = $classes[$longestClass];
                $groups[] = $currentGroup;
                $i += $nextPos;
                continue;
            }
            $currentGroup['value'] = [$char];
            $groups[] = $currentGroup;
            $i++;
        }
    }
    //error_log("Pattern '$pattern' processed as: " . json_encode($groups));
    return $groups;
}

function splitIgnoringBraces($string) {
    $result = [];
    $current = '';
    $braceLevel = 0;
    $parenLevel = 0;
    $length = mb_strlen($string);

    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($string, $i, 1);

        if ($char === '{') {
            $braceLevel++;
            $current .= $char;
        } elseif ($char === '}') {
            $braceLevel--;
            $current .= $char;
        } elseif ($char === '(') {
            $parenLevel++;
            $current .= $char;
        } elseif ($char === ')') {
            $parenLevel--;
            $current .= $char;
        } elseif ($char === ',' && $braceLevel === 0 && $parenLevel === 0) {
            $result[] = trim($current);
            $current = '';
        } else {
            $current .= $char;
        }
    }

    if ($current !== '') {
        $result[] = trim($current);
    }

    return $result;
}

function rewriteRepeater($pattern, $classes, $maxRepetitions) {
    $result = '';
    $length = mb_strlen($pattern);
    $i = 0;

    while ($i < $length) {
        $char = mb_substr($pattern, $i, 1);
        
        if ($char === '{' || $char === '(') {
            $closing = ($char === '{') ? '}' : ')';
            $j = $i + 1;
            $class_content = '';
            
            // Lê até encontrar o fechamento correspondente
            while ($j < $length && mb_substr($pattern, $j, 1) !== $closing) {
                $class_content .= mb_substr($pattern, $j, 1);
                $j++;
            }
            
            // Verifica se há fechamento
            if ($j < $length && mb_substr($pattern, $j, 1) === $closing) {
                $i = $j + 1;
                $token = $char . $class_content . $closing;
                
                // Verifica se há repetidor +
                $has_repeater = ($i < $length && mb_substr($pattern, $i, 1) === '+');
                if ($has_repeater) {
                    $i++;
                    // Copia o token como veio na primeira vez
                    $result .= $token;
                    // Adiciona repetições entre parênteses
                    $repeat_token = ($char === '{') ? '(' . $class_content . ')' : $token;
                    for ($k = 1; $k < $maxRepetitions; $k++) {
                        $result .= $repeat_token;
                    }
                } else {
                    $result .= $token;
                }
            } else {
                // Caso não encontre fechamento, trata como literal
                $result .= $char;
                $i++;
            }
        } else {
            /*
            // Verifica se é uma classe predefinida
            $found_class = false;
            foreach (array_keys($classes) as $class_name) {
                $class_length = mb_strlen($class_name);
                if ($i + $class_length <= $length && 
                    mb_substr($pattern, $i, $class_length) === $class_name) {
                    $token = $class_name;
                    $i += $class_length;
                    $has_repeater = ($i < $length && mb_substr($pattern, $i, 1) === '+');
                    if ($has_repeater) {
                        $i++;
                        $result .= $token;
                        for ($k = 1; $k < $maxRepetitions; $k++) {
                            $result .= '(' . $token . ')';
                        }
                    } else {
                        $result .= $token;
                    }
                    $found_class = true;
                    break;
                }
            }
            
            // Se não for uma classe predefinida, trata como literal
            if (!$found_class) {
                $token = $char;
                $i++;
                $has_repeater = ($i < $length && mb_substr($pattern, $i, 1) === '+');
                if ($has_repeater) {
                    $i++;
                    $result .= $token;
                    for ($k = 1; $k < $maxRepetitions; $k++) {
                        $result .= '(' . $token . ')';
                    }
                } else {
                    $result .= $token;
                }
            }
            */
            [$longestClass, $nextPos] = findLongestClass(mb_substr($pattern, $i), $classes);
            if ($longestClass) {
                $token = $longestClass;
                $i += $nextPos;
                $has_repeater = ($i < $length && mb_substr($pattern, $i, 1) === '+');
                if ($has_repeater) {
                    $i++;
                    $result .= $token;
                    for ($k = 1; $k < $maxRepetitions; $k++) {
                        $result .= '(' . $token . ')';
                    }
                } else {
                    $result .= $token;
                }
            } else {
                $token = $char;
                $i++;
                $has_repeater = ($i < $length && mb_substr($pattern, $i, 1) === '+');
                if ($has_repeater) {
                    $i++;
                    $result .= $token;
                    for ($k = 1; $k < $maxRepetitions; $k++) {
                        $result .= '(' . $token . ')';
                    }
                } else {
                    $result .= $token;
                }
            }
        }
    }
    
    return $result;
}

function patternToRegex($pattern, $classes, $capturedValues) {
    $pattern = preserveSpacesInBrackets($pattern);
    $pattern = preg_replace('/\s+/u', '', $pattern);

    $regex = '';
    $len = mb_strlen($pattern);
    $i = 0;

    while ($i < $len) {
        $char = mb_substr($pattern, $i, 1);

        // Substituir capturas (ex.: 1, 2, 3)
        if (preg_match('/^[1-9]$/', $char) && isset($capturedValues[$char])) {
            $regex .= preg_quote($capturedValues[$char], '/');
            $i++;
            continue;
        }
        
        if ($char === '{') {
            $end = mb_strpos($pattern, '}', $i);
            if ($end !== false) {
                $options = mb_substr($pattern, $i + 1, $end - $i - 1);
                $optionList = array_map('trim', explode(',', $options));
                $expanded = [];
                foreach ($optionList as $option) {
                    [$longestClass, $nextPos] = findLongestClass($option, $classes, ['{', '(', ')', '}', '[', ']', '~', ',']);
                    if ($longestClass && $nextPos === mb_strlen($option)) {
                        $expanded = array_merge($expanded, array_map('preg_quote', $classes[$longestClass], array_fill(0, count($classes[$longestClass]), '/')));
                    } else {
                        $expanded[] = preg_quote($option, '/');
                    }
                }
                $regex .= '[' . implode('', $expanded) . ']';
                $i = $end + 1;
                continue;
            }
        }
        if ($char === '(') {
            $end = mb_strpos($pattern, ')', $i);
            if ($end !== false) {
                $options = mb_substr($pattern, $i + 1, $end - $i - 1);
                $optionList = array_map('trim', explode(',', $options));
                $expanded = [];
                foreach ($optionList as $option) {
                    [$longestClass, $nextPos] = findLongestClass($option, $classes, ['{', '(', ')', '}', '[', ']', '~', ',']);
                    if ($longestClass && $nextPos === mb_strlen($option)) {
                        $expanded = array_merge($expanded, array_map('preg_quote', $classes[$longestClass], array_fill(0, count($classes[$longestClass]), '/')));
                    } else {
                        $expanded[] = preg_quote($option, '/');
                    }
                }
                $regex .= '(?:[' . implode('', $expanded) . '])?';
                $i = $end + 1;
                continue;
            }
        }

        /*
        // Processar underscore e limite como marcadores
        if ($char === '_' || $char === '#') {
            $regex .= $char;
            $i++;
            continue;
        }
        */

        // Processar coringa (*)
        if ($char === '*') {
            $regex .= '.*?';
            $i++;
            continue;
        }

        // Verificar classes soltas (ex.: Vogal, C)
        /*
        $potentialClass = '';
        $j = $i;
        while ($j < $len && !in_array(mb_substr($pattern, $j, 1), ['{', '(', ')', '}', '_', '#', '*', '+', ',', '1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
            $potentialClass .= mb_substr($pattern, $j, 1);
            if (isset($classes[$potentialClass])) {
                $expanded = array_map('preg_quote', $classes[$potentialClass], array_fill(0, count($classes[$potentialClass]), '/'));
                $regex .= '[' . implode('', $expanded) . ']';
                $i = $j + 1;
                continue 2;
            }
            $j++;
        }
        */
        [$longestClass, $nextPos] = findLongestClass(mb_substr($pattern, $i), $classes);
        if ($longestClass) {
            $expanded = array_map('preg_quote', $classes[$longestClass], array_fill(0, count($classes[$longestClass]), '/'));
            $regex .= '[' . implode('', $expanded) . ']';
            $i += $nextPos;
            continue;
        }

        // Caracteres literais
        $regex .= preg_quote($char, '/');
        $i++;
    }
    return $regex;
}

function checkContexts($context, $classes, $capturedValues, $word, $i, $srcLen, $len, $targetValue){
    $contextParts = explode('_', $context);
    $before = $contextParts[0] ?? '';
    $after = $contextParts[1] ?? '';

    $valid = false;
    $isLimitBefore = false;
    $isLimitAfter = false;
    if (mb_strpos($before, '#') !== false) {
        $before = mb_substr($before, mb_strpos($before, '#') + 1);
        $isLimitBefore = true;
    }
    if (mb_strpos($after, '#') !== false) {
        $after = mb_substr($after, 0, mb_strpos($after, '#'));
        $isLimitAfter = true;
    }

    // Converter padrões em expressões regulares
    $beforeRegex = patternToRegex($before, $classes, $capturedValues);
    $afterRegex = patternToRegex($after, $classes, $capturedValues);

    // Preparar trechos de $word para verificação
    $beforePart = $i > 0 ? mb_substr($word, 0, $i) : '';
    $afterPart = mb_substr($word, $i + $srcLen); //$afterPart = mb_substr($word, $i, $len);

    // Ajustar regex para limites
    if ($isLimitBefore) {
        $beforeRegex = '^' . $beforeRegex . '$';
        // Verificar se a posição atual é o início da palavra  
        if ($i > mb_strlen($before)) {
            return false;
        }            
    } else {
        $beforeRegex = $beforeRegex . '$';
    }
    //echo " $i + $srcLen < $len - ";
    if ($isLimitAfter) {
        $afterRegex = '^' . $afterRegex . '$';
        // Verificar se a posição atual é o fim da palavra
        //if ($i + $srcLen < $len ) {
        if ($i + $srcLen + mb_strlen($after) < $len ) { // + 1 ?
            return false;
        }
    } else {
        $afterRegex = '^' . $afterRegex;
    }

    // Verificar correspondência
    $beforeMatch = $before == '' || preg_match('/' . $beforeRegex . '/u', $beforePart);
    $afterMatch = $after == '' || preg_match('/' . $afterRegex . '/u', $afterPart);

    //echo "<br>".$beforeRegex."_".mb_substr($word, $i, 1)."_".$afterRegex." - ".$beforePart."_".$afterPart."<br>\nValid = $beforeMatch && $afterMatch \n<br>\n";

    $valid = $beforeMatch && $afterMatch;

    return $valid;

}

function expandNestedClasses($classes) {
    $expanded = [];
    $processed = []; // Evitar recursão infinita

    foreach ($classes as $className => $chars) {
        if (in_array($className, $processed)) {
            continue; // Evitar ciclos
        }
        $processed[] = $className;
        $newChars = [];
        foreach ($chars as $char) {
            if (isset($classes[$char])) {
                // Classe aninhada: expandir recursivamente
                $nestedClasses = expandNestedClasses([$char => $classes[$char]] + array_diff_key($classes, [$className => true]));
                $newChars = array_merge($newChars, $nestedClasses[$char]);
            } else {
                $newChars[] = $char;
            }
        }
        $expanded[$className] = array_unique($newChars);
    }

    return $expanded;
}

function intersectClasses($classNames, $classes) {
    if (empty($classNames)) {
        return [];
    } 
    $result = $classes[$classNames[0]] ?? [];
    foreach (array_slice($classNames, 1) as $className) {
        if (!isset($classes[$className])) {
            return []; // Classe não definida
        }
        $result = array_intersect($result, $classes[$className]);
    }
    return array_values($result); // Retorna como array indexado
}

function preserveSpacesInBrackets($string) {
    $result = '';
    $length = mb_strlen($string);
    $i = 0;
    $insideBrackets = false;

    while ($i < $length) {
        $char = mb_substr($string, $i, 1);
        if ($char === '[') {
            $insideBrackets = true;
            $result .= $char;
            $i++;
            continue;
        } elseif ($char === ']') {
            $insideBrackets = false;
            $result .= $char;
            $i++;
            continue;
        } elseif ($insideBrackets && $char === ' ') {
            $result .= '|';
            $i++;
            continue;
        }
        $result .= $char;
        $i++;
    }

    return $result;
}

function convertBracketedClasses($pattern, $classes) {
    $result = '';
    $length = mb_strlen($pattern);
    $i = 0;

    while ($i < $length) {
        $char = mb_substr($pattern, $i, 1);

        if ($char === '[') {
            $end = mb_strpos($pattern, ']', $i);
            if ($end !== false) {
                $list = mb_substr($pattern, $i + 1, $end - $i - 1);
                if (preg_match('/^[\p{L}|]+$/u', $list)) {
                    $classNames = array_filter(array_map('trim', explode('|', $list)));
                    $resolvedNames = [];
                    foreach ($classNames as $name) {
                        [$longestClass, $nextPos] = findLongestClass($name, $classes, ['|']);
                        if ($longestClass && $nextPos === mb_strlen($name)) {
                            $resolvedNames[] = $longestClass;
                        } else {
                            $resolvedNames[] = $name;
                        }
                    }
                    $chars = intersectClasses($resolvedNames, $classes);
                    if (!empty($chars)) {
                        $charList = implode(',', $chars);
                        // Verificar se [xx yy] está imediatamente cercado por ( e )
                        $isWrappedInParens = ($i > 0 && $end + 1 < $length &&
                            mb_substr($pattern, $i - 1, 1) === '(' &&
                            mb_substr($pattern, $end + 1, 1) === ')');
                        if ($isWrappedInParens) {
                            // Consumir os parênteses externos e gerar (chars)
                            $result = mb_substr($result, 0, -1); // Remover o '(' adicionado anteriormente
                            $result .= '(' . $charList . ')';
                            $i = $end + 2; // Pular o ']' e o ')'
                        } else {
                            // Gerar {chars} se não estiver em parênteses ou se os parênteses não forem imediatos
                            $result .= '{' . $charList . '}';
                            $i = $end + 1;
                        }
                        continue;
                    }
                }
            }
        }
        $result .= $char;
        $i++;
    }

    return $result;
}
?>