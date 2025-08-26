<?php
mb_internal_encoding('UTF-8');

$erros = array();

function parseSoundChangeRules($text) {
  $lines = array_map('trim', explode("\n", $text)); // Divide o texto em linhas e remove espaços
  $result = []; // Array associativo final com grupos de regras
  $currentBlock = false; // Rastreia o bloco atual
  $blockRules = []; // Regras do bloco atual
  $blockName = 'default'; // Nome padrão para blocos sem nome e regras soltas

  $result['default'] = [];

  foreach ($lines as $line) {
      if ($line === '' || preg_match('/^(\/\/|##)/', $line)) {
          continue;
      }

      if (preg_match('/^\{\s*([a-zA-Z][a-zA-Z0-9]*)?\s*$/', $line, $matches)) {
          if ($currentBlock) {
              $result[$blockName] = $blockRules;
              $blockRules = [];
          }
          $blockName = !empty($matches[1]) ? $matches[1] : 'default';
          $currentBlock = true;
          $result[$blockName] = $result[$blockName] ?? [];
          continue;
      }

      if ($line === '}' && $currentBlock) {
          $result[$blockName] = $blockRules;
          $currentBlock = false;
          $blockRules = [];
          $blockName = 'default';
          continue;
      }

      if ($currentBlock) {
          $blockRules[] = $line;
      } else {
          $result['default'][] = $line;
      }
  }

  if ($currentBlock && !empty($blockRules)) {
      $result[$blockName] = $blockRules;
  }

  foreach ($result as $key => $rules) {
      if (empty($rules)) {
          unset($result[$key]);
      }
  }

  return $result;
}

function findLongestClass($option, $classes, $delimiters = ['{', '(', ')', '}', '_', '$', '#', '*', '+', ',', '1', '2', '3', '4', '5', '6', '7', '8', '9', '[', ']', '~']) {
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
    if (!is_string($lines) && !is_array($lines)) {
        throw new InvalidArgumentException("lines deve ser uma string ou array.");
    }
    if (!is_array($rules) && !(is_array($rules) && !isset($rules[0]))) {
        throw new InvalidArgumentException("rules deve ser um array ou array associativo.");
    }
    if (!is_array($substitutions) || !is_array($classes)) {
        throw new InvalidArgumentException("substitutions e classes devem ser arrays.");
    }
    if ($rulesPerLines !== null && (!is_int($rulesPerLines) || $rulesPerLines < 1)) {
        throw new InvalidArgumentException("rulesPerLines deve ser um inteiro positivo ou null.");
    }
    if (count($lines) > 10000 || count($rules) > 1000 || count($substitutions) > 1000 || count($classes) > 1000) {
        throw new InvalidArgumentException("Número excessivo de linhas, regras, substituições ou classes.");
    }

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
                $erros[] = "KSC: Regra inválida ignorada ao coletar caracteres: $rule";
            }
        }
    }

    $uniqueChars = array_unique($uniqueChars);
    $reservedChars = ['∅', 'ø', '→', ':', '=>', ' ', "\n", "\t", "\r", '=', '/', '_', '$', '#', '<', '>', '(', ')', '[', ']', '{', '}', ',', '?', '*', '.', '~', '|', '+'];

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
        $requiredLines = $numBlocks * $rulesPerLines;

        if ($numLines < $requiredLines) {
            $erros[] = "KSC: Aviso: Número de linhas ($numLines) insuficiente para $numBlocks blocos com $rulesPerLines linhas por bloco. Usando comportamento padrão.";
            $rulesPerLines = null;
        } elseif ($numLines > $requiredLines) {
            $erros[] = "KSC: Aviso: Sobram linhas ($numLines > $requiredLines). Último bloco será reutilizado.";
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
                        
                        if (preg_match('/^(\p{Lu}\p{Ll}*)\s*=\s*(\{[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+(?:\s*,\s*[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+)*\s*\}|[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+|[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+(?:\s*,\s*[\p{L}\x{0250}-\x{02AF}\x{1D00}-\x{1DBF}\p{M}]+)*)\s*$/u', $rule, $matches)) {
                            $className = $matches[1]; // Letra maiúscula ou acentuada (ex.: Á)
                            $classValue = $matches[2]; // Valor (ex.: [á,é,ó] ou áéó)

                            if (preg_match('/^\{([^\}]*)\}\s*$/u', $classValue, $listMatches)) {
                                $characters = array_map('trim', explode(',', $listMatches[1]));
                                $characters = array_filter($characters); // Remove elementos vazios
                            } elseif (mb_strpos($classValue,",")>0){
                                $characters = array_map('trim', explode(',', $classValue));
                                $characters = array_filter($characters); // Remove elementos vazios
                            } else {
                                $characters = preg_split('//u', $classValue, -1, PREG_SPLIT_NO_EMPTY);
                            }
                            $lineClasses[$className] = $characters;
                            continue; // Pula a aplicação da regra
                        }
                        $newWord = applySingleRule($newWord, $rule, $lineClasses);
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
        $erros[] = "KSC: Regra inválida: $rule";
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

    $maxRepetitions = $len-1; 
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

    while ($i < $len) {
        $currentChar = mb_substr($word, $i, 1);
        $applied = false;

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

            if ($isCurrentInsertion && isset($insertionApplied[$i])) {
                continue;
            }

            if (!$isCurrentInsertion && ($i + $srcLen > $len || mb_substr($word, $i, $srcLen) !== $src)) {
                continue;
            }

            if (!empty($captureGroups) && isset($appliedPositions[$i])) {
                continue;
            }

            $contextValid = false;

            foreach ($contexts as $context) {
                if ($context === '_') {
                    $contextValid = true;
                    break;
                }

                $contextValid = checkContexts($context, $classes, $capturedValues, $word, $i, $srcLen, $len, $targetValue);
                if ($contextValid) break;                
            }

            if ($contextValid && !empty($exclusions)) {
                foreach ($exclusions as $exclusion) {

                    $contextValid = !checkContexts($exclusion, $classes, $capturedValues, $word, $i, $srcLen, $len, $targetValue);
                    if (!$contextValid) break;
                }
            }

            if ($contextValid  && 
                mb_substr($word, $i, mb_strlen($src)) === $src) {
                $replacement = '';
                if ($isRemoval) {
                    $newWord .= '';
                } elseif (is_array($targetValue) && isset($targetValue['type']) && $targetValue['type'] === 'capture') {
                    $replacement = ''; 
                    $classMapping = $targetValue['classMapping'] ?? [];

                    foreach ($targetValue['groups'] as $tgtGroup) {
                        if ($tgtGroup['type'] === 'capture') {
                            $captureIndex = $tgtGroup['value'];
                            if (isset($capturedValues[$captureIndex])) {
                                $replacement .= $capturedValues[$captureIndex];
                            } else {
                                $replacement .= $captureIndex;
                            }
                        } elseif ($tgtGroup['type'] === 'class' && isset($classes[$tgtGroup['value'][0]])) {
                            $className = $tgtGroup['value'][0];
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
                                    $replacement .= $classes[$className][0];
                                }
                            } else {
                                $replacement .= $classes[$className][0];
                            }
                        } else {
                            $replacement .= $tgtGroup['value'][0];
                        }
                    }
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
                $i += $srcLen;
                if ($isCurrentInsertion) {
                    $insertionApplied[$i] = true;
                }
                $applied = true;
                break;
            }
        }
        
        if (!$applied && $i <= $len) {
            $newWord .= mb_substr($word, $i, 1);
            $i++;
        }
    }

    if ($isInsertion && !isset($insertionApplied[$len])) {
        foreach (generateGroupCombinations($sourceGroups) as $combination) {
            $srcLen = 0;
            $isCurrentInsertion = false;
            $matchedChars = [];
            $capturedValues = [];

            foreach ($combination as $group) {
                if ($group['type'] === 'insertion') {
                    $isCurrentInsertion = true;
                    $matchedChars[] = '~';
                    $capturedValues[$group['capture']] = '~';
                }
            }

            if (!$isCurrentInsertion) {
                continue;
            }

            $contextValid = false;

            foreach ($contexts as $context) {
                if ($context === '_') {
                    $contextValid = true;
                    break;
                }

                $contextValid = checkContexts($context, $classes, $capturedValues, $word, $len, $srcLen, $len, $targetValue);
                if ($contextValid) break;
            }

            if ($contextValid && !empty($exclusions)) {
                foreach ($exclusions as $exclusion) {
                    $contextValid = !checkContexts($exclusion, $classes, $capturedValues, $word, $len, $srcLen, $len, $targetValue);
                    if ($contextValid) break;
                }
            }

            if ($contextValid) {
                $newWord .= is_string($targetValue) ? $targetValue : ( $targetValue['~'] ?? '');
                $insertionApplied[$len] = true;
                break;
            }
        }
    }

    return $newWord;
}

function processSource($source, $classes) { 
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
                $erros[] = "KSC: Erro: Captura $char referencia grupo inexistente em $source";
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
        $erros[] = "KSC: Erro: Mais de 9 grupos de captura em $source";
        return [[], []];
    }

    return [$sourceGroups, $captureGroups];
}

function processTarget($target, $sourceGroups, $captureGroups, $source, $classes) {
    if ($target === '~' || $target === '' || $target === 'ø' || $target === '0' || $target === '∅') {
        return '';
    }

    $targetGroups = processPattern($target, $classes);
    if (empty($targetGroups)) {
        return $target;
    }

    $hasInsertion = in_array('insertion', array_column($sourceGroups, 'type'));
    $hasCapture = in_array('capture', array_column($sourceGroups, 'type')) || in_array('capture', array_column($targetGroups, 'type'));

    $classMapping = [];
    foreach ($targetGroups as $tgtGroup) {
        if ($tgtGroup['type'] === 'class' && isset($classes[$tgtGroup['value'][0]])) {
            $targetClass = $tgtGroup['value'][0];
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
                        $classMapping[$sourceClass][$srcValues[$i]] = $srcValues[$i];
                    }
                }
            }
        }
    }

    if ($hasCapture || !empty($classMapping)) {
        return ['type' => 'capture', 'value' => $target, 'groups' => $targetGroups, 'classMapping' => $classMapping];
    }

    $mapping = [];
    $targetIndex = 0;



    foreach ($sourceGroups as $srcGroup) {
        if ($targetIndex >= count($targetGroups) || count($sourceGroups) != count($targetGroups)) {
            return $target;
        }

        if ($srcGroup['type'] === 'insertion') {
            if ($targetGroups[$targetIndex]['type'] === 'literal') {
                $mapping['~'] = $targetGroups[$targetIndex]['value'][0];
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'insertion') {
                $mapping['~'] = '';
                $targetIndex++;
            }
        } elseif ($srcGroup['type'] === 'class') {
            if ($targetGroups[$targetIndex]['type'] === 'class') {
                $srcValues = $srcGroup['value'];
                $tgtValues = $targetGroups[$targetIndex]['value'];
                for ($i = 0; $i < count($srcValues); $i++) {
                    $mapping[$srcValues[$i]] = $tgtValues[$i] ?? $srcValues[$i];
                }
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'insertion') {
                $srcValues = $srcGroup['value']; 
                $tgtValues = $targetGroups[$targetIndex]['value'];
                for ($i = 0; $i < count($srcValues); $i++) {
                    $mapping[$srcValues[$i]] = '';
                }
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'literal') {
                $srcValues = $srcGroup['value'];
                $tgtValues = $targetGroups[$targetIndex]['value'];
                for ($i = 0; $i < count($srcValues); $i++) {
                    $mapping[$srcValues[$i]] = $targetGroups[$targetIndex]['value'][0];
                }
                $targetIndex++;
            }
        } elseif ($srcGroup['type'] === 'literal') {
            if ($targetGroups[$targetIndex]['type'] === 'literal') {
                $mapping[$srcGroup['value'][0]] = $targetGroups[$targetIndex]['value'][0]; 
                $targetIndex++;
            } else if ($targetGroups[$targetIndex]['type'] === 'insertion') {
                $mapping['~'] = '';
                $targetIndex++;
            }
        }
    }
    
    if (!empty($mapping)) {
        return $mapping;
    }

    // Novo mapeamento para lidar com classes e literais
    if (count($sourceGroups) === count($targetGroups)) {
        for ($i = 0; $i < count($sourceGroups); $i++) {
            $srcGroup = $sourceGroups[$i];
            $tgtGroup = $targetGroups[$i];

            if ($srcGroup['type'] === 'class' && $tgtGroup['type'] === 'class') {
                $srcValues = $srcGroup['value'];
                $tgtValues = $tgtGroup['value'];
                for ($j = 0; $j < count($srcValues); $j++) {
                    $mapping[$srcValues[$j]] = $tgtValues[$j] ?? $srcValues[$j];
                }
            } elseif ($srcGroup['type'] === 'literal' && $tgtGroup['type'] === 'literal') {
                $mapping[$srcGroup['value'][0]] = $tgtGroup['value'][0];
            } elseif ($srcGroup['type'] === 'literal' && $tgtGroup['type'] === 'insertion') {
                $mapping[$srcGroup['value'][0]] = '';
            } elseif ($srcGroup['type'] === 'class' && $tgtGroup['type'] === 'literal') {
                for ($j = 0; $j < count($srcGroup['value']); $j++) {
                    $mapping[$srcGroup['value'][$j]] = $tgtGroup['value'][0];
                }
            } else {
                $erros[] = "KSC: Erro: Mapeamento inválido entre source e target. Source: " . print_r($srcGroup, true) . ", Target: " . print_r($tgtGroup, true);
            }
        }
        return $mapping;
    }

    // Fallback para caso de classes não alinhadas
    if (preg_match('/^\{([^\}]+)\}(\p{M}*)$/u', $target, $match)) {
        $elements = array_map('trim', explode(',', $match[1]));
        $modifier = $match[2] ?? '';
        return $elements[0] . $modifier;
    } elseif (isset($classes[$target])) {
        return $classes[$target][0];
    }
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
                $refIndex = $group['refers_to'] - 1;
                if ($refIndex < 0 || $refIndex >= count($groups)) {
                    $erros[] = "KSC: Erro: Captura referencia índice inválido: " . $group['refers_to'];
                    continue;
                }
                $refGroup = $groups[$refIndex];
                foreach ($refGroup['value'] as $value) {
                    $newCombo = $combo;
                    $newCombo[] = ['value' => $value, 'type' => 'capture', 'capture' => $group['capture'], 'refers_to' => $group['refers_to']];
                    $newCombinations[] = $newCombo;
                }
            } else {
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
            
            while ($j < $length && mb_substr($pattern, $j, 1) !== $closing) {
                $class_content .= mb_substr($pattern, $j, 1);
                $j++;
            }
            
            if ($j < $length && mb_substr($pattern, $j, 1) === $closing) {
                $i = $j + 1;
                $token = $char . $class_content . $closing;
                
                $has_repeater = ($i < $length && mb_substr($pattern, $i, 1) === '+');
                if ($has_repeater) {
                    $i++;
                    $result .= $token;
                    $repeat_token = ($char === '{') ? '(' . $class_content . ')' : $token;
                    for ($k = 1; $k < $maxRepetitions; $k++) {
                        $result .= $repeat_token;
                    }
                } else {
                    $result .= $token;
                }
            } else {
                $result .= $char;
                $i++;
            }
        } else {
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

        if ($char === '*') {
            $regex .= '.*?';
            $i++;
            continue;
        }

        [$longestClass, $nextPos] = findLongestClass(mb_substr($pattern, $i), $classes);
        if ($longestClass) {
            $expanded = array_map('preg_quote', $classes[$longestClass], array_fill(0, count($classes[$longestClass]), '/'));
            $regex .= '[' . implode('', $expanded) . ']';
            $i += $nextPos;
            continue;
        }

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
        //$word = $word.'#';
        $after = mb_substr($after, 0, mb_strpos($after, '#'));
        $isLimitAfter = true;
    }

    $beforeRegex = patternToRegex($before, $classes, $capturedValues);
    $afterRegex = patternToRegex($after, $classes, $capturedValues);

    $beforePart = $i > 0 ? mb_substr($word, 0, $i) : '';
    $afterPart = mb_substr($word, $i + $srcLen);

    if ($isLimitBefore) {
        $beforeRegex = '^' . $beforeRegex . '$';
        if ($i > mb_strlen($before)) {
            return false;
        }            
    } else {
        $beforeRegex = $beforeRegex . '$';
    }
    if ($isLimitAfter) {
        $afterRegex = '^' . $afterRegex . '$';
        //if ($i + $srcLen < $len ) {
        if ($i + $srcLen + mb_strlen($after) < $len ) {
            return false;
        }
    } else {
        $afterRegex = '^' . $afterRegex;
    }

    $beforeMatch = $before == '' || preg_match('/' . $beforeRegex . '/u', $beforePart);
    $afterMatch = $after == '' || preg_match('/' . $afterRegex . '/u', $afterPart);

    $valid = $beforeMatch && $afterMatch;
    return $valid;
}

function expandNestedClasses($classes, $depth = 0) {
    if ($depth > 5) {
        throw new RuntimeException("Profundidade máxima de recursão atingida em expandNestedClasses.");
    }
    $expanded = [];
    $processed = [];

    foreach ($classes as $className => $chars) {
        if (in_array($className, $processed)) {
            continue;
        }
        $processed[] = $className;
        $newChars = [];
        foreach ($chars as $char) {
            if (isset($classes[$char])) {
                $nestedClasses = expandNestedClasses([$char => $classes[$char]] + array_diff_key($classes, [$className => true]), $depth + 1);
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
            return [];
        }
        $result = array_intersect($result, $classes[$className]);
    }
    return array_values($result);
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
                        $isWrappedInParens = ($i > 0 && $end + 1 < $length &&
                            mb_substr($pattern, $i - 1, 1) === '(' &&
                            mb_substr($pattern, $end + 1, 1) === ')');
                        if ($isWrappedInParens) {
                            $result = mb_substr($result, 0, -1);
                            $result .= '(' . $charList . ')';
                            $i = $end + 2;
                        } else {
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

function applySoundChangesByGroup($lines, $rules, $substitutions = [], $classes = [], $rulesPerLines = null) {
    // Validações iniciais
    if (!is_array($lines)) {
        throw new InvalidArgumentException("lines deve ser um array.");
    }
    if (!is_array($rules) || isset($rules[0])) {
        //throw new InvalidArgumentException("rules deve ser um array associativo de grupos.");
    }
    if (!is_array($substitutions) || !is_array($classes)) {
        throw new InvalidArgumentException("substitutions e classes devem ser arrays.");
    }
    if (count($lines) !== count($rules)) {
        throw new InvalidArgumentException("O número de linhas (" . count($lines) . ") deve ser igual ao número de grupos de regras (" . count($rules) . ").");
    }
    if (count($lines) > 10000 || count($rules) > 1000 || count($substitutions) > 1000 || count($classes) > 1000) {
        throw new InvalidArgumentException("Número excessivo de linhas, regras, substituições ou classes.");
    }

    $inputLines = $lines;
    $ruleGroups = $rules;
    $erros = [];

    // Expandir classes aninhadas
    $classes = expandNestedClasses($classes);

    // Coletar caracteres únicos
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
                $erros[] = "KSC: Regra inválida ignorada ao coletar caracteres: $rule";
            }
        }
    }
    $uniqueChars = array_unique($uniqueChars);
    $reservedChars = ['∅', 'ø', '→', ':', '=>', ' ', "\n", "\t", "\r", '=', '/', '_', '$', '#', '<', '>', '(', ')', '[', ']', '{', '}', ',', '?', '*', '.', '~', '|', '+'];
    $ignoredChars = ['´', '`', '^', '¨', "'"];
    $uniqueChars = array_diff($uniqueChars, $reservedChars, $ignoredChars);
    $classes['?'] = array_values($uniqueChars);

    // Processar cada linha com o grupo de regras correspondente
    $result = [];
    $blockKeys = array_keys($ruleGroups);
    foreach ($inputLines as $lineIndex => $line) {
        preg_match('/[\s\t]+/', $line, $separatorMatches);
        $separator = $separatorMatches ? $separatorMatches[0] : ' ';
        $words = preg_split('/[\s\t]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            $result[] = $line;
            continue;
        }

        $transformedWords = $words;
        $lineClasses = $classes;
        $blockName = $blockKeys[$lineIndex];
        $groupRules = $ruleGroups[$blockName];

        // Aplicar todas as regras do grupo à linha
        $tempWords = [];
        foreach ($transformedWords as $wordIndex => $word) {
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
            }
            $tempWords[] = $newWord;
        }
        $transformedWords = $tempWords;

        // Aplicar substituições
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

    return [$result, $erros];
}
?>