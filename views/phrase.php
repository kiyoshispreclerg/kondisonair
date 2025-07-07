<?php
// Fetch main phrase details
$id_frase = $_GET['id'] ?: 0;
$id_idioma = $_GET['iid'] ?: 0;
$id_usuario = $_SESSION['KondisonairUzatorIDX'] ?: 0;

if ($id_frase <= 0) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}

// Fetch main phrase data
$result = mysqli_query($GLOBALS['dblink'], "SELECT 
        f.*, i.nome_legivel, i.id_usuario as dono, e.id as eid, e.tamanho, e.id_fonte as fonte, u.username as usuario_nome, e.separadores, e.binario,
        f2.frase as frase2, f2.id_idioma as id_idioma2, i2.nome_legivel as nome_legivel2, e2.id as eid2, e2.tamanho as tamanho2, e2.id_fonte as fonte2
    FROM frases f
    LEFT JOIN idiomas i ON f.id_idioma = i.id
    LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
    LEFT JOIN usuarios u ON f.id_criador = u.id
    LEFT JOIN frases f2 ON f.id_original = f2.id
    LEFT JOIN idiomas i2 ON f2.id_idioma = i2.id
    LEFT JOIN escritas e2 ON e2.id_idioma = i2.id AND e2.padrao = 1
    WHERE f.id = '$id_frase';") or die(mysqli_error($GLOBALS['dblink']));
$main_phrase = mysqli_fetch_assoc($result);

if (!$main_phrase) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}

$id_idioma = $main_phrase['id_idioma'];
$breadcrumb = '<li class="breadcrumb-item"><a href="?page=language&iid=' . $id_idioma . '">' . htmlspecialchars($main_phrase['nome_legivel']) . '</a></li>
    <li class="breadcrumb-item"><a href="?page=phrases&iid=' . $id_idioma . '">' . _t('Frases') . '</a></li>
    <li class="breadcrumb-item active">' . _t('Frase') . '</li>';

// Fetch translations
$translations = [];
$result = mysqli_query($GLOBALS['dblink'], "SELECT f.*, i.nome_legivel, u.username as usuario_nome, e.id as eid, e.tamanho, e.id_fonte as fonte
    FROM frases f
    LEFT JOIN idiomas i ON f.id_idioma = i.id
    LEFT JOIN usuarios u ON f.id_criador = u.id
    LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
    WHERE f.id_original = '$id_frase';") or die(mysqli_error($GLOBALS['dblink']));
while ($row = mysqli_fetch_assoc($result)) {
    $translations[] = $row;
}

$textoSentenca=$main_phrase['frase'];
$separadorPalavras = preg_split('//u', $main_phrase['separadores'], null, PREG_SPLIT_NO_EMPTY) ?: [" "];
$iniciadoresPalavras = preg_split('//u', $e['iniciadores'], null, PREG_SPLIT_NO_EMPTY) ?: ["\n"];
foreach ($iniciadoresPalavras as $sep){
    $textoSentenca = str_replace($sep," ".$sep,$textoSentenca);
}
$mainPhrase = getStudySentence($separadorPalavras,$textoSentenca,$id_idioma,$main_phrase['eid'], ($main_phrase['binario']>0?' BINARY ':''),$main_phrase['fonte'],$main_phrase['tamanho']  )[0];

// frases para comparação

function removerParametro($param, $valorRemover) {
    $url = $_SERVER['REQUEST_URI'];
    $query = parse_url($url, PHP_URL_QUERY);
    $params = [];
    
    if ($query) {
        parse_str($query, $params);
    }
    
    if (isset($params[$param])) {
        $params[$param] = array_diff((array)$params[$param], [$valorRemover]);
        if (empty($params[$param])) {
            unset($params[$param]);
        }
    }
    
    if (empty($params)) {
        return strtok($url, '?');
    }
    return strtok($url, '?') . '?' . http_build_query($params);
}

function adicionarParametro($param, $valor) {
    $url = $_SERVER['REQUEST_URI'];
    $query = parse_url($url, PHP_URL_QUERY);
    $params = [];
    
    if ($query) {
        parse_str($query, $params);
    }
    
    $params[$param][] = $valor; // Suporta múltiplos valores
    return strtok($url, '?') . '?' . http_build_query($params);
}

?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <?=$breadcrumb?>
                    </ol>
                </h2>
            </div>
            <?php if ($main_phrase['id_criador'] == $_SESSION['KondisonairUzatorIDX']) { ?>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="?page=editphrase&id=<?=$id_frase?>&iid=<?=$id_idioma?>" class="btn btn-primary d-none d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3" />
                            <path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3" />
                            <path d="M16 5l3 3" />
                        </svg>
                        <?=_t('Editar')?>
                    </a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row  row-cards">
            <div class="col-8">
                <div class="card sticky-top">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Frase')?></h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <div class="col-9">
                                
                                <div style="white-space:preserve;
                                    transform: scale(1.5);
                                    transform-origin: top left;
                                    width: 62%;
                                    display: block; margin-bottom:10%"

                                    <?php if ($main_phrase['fonte'] == 3) { ?>
                                        class="editable-drawchar custom-font-<?=$main_phrase['eid']?>"
                                    <?php } else { ?>
                                        class="custom-font-<?=$main_phrase['eid']?>"
                                    <?php } ?>

                                    id="textoMarcado"><?php echo $mainPhrase; ?></div>

                                
                            </div>
                            <div class="col-3">
                                <p class="text-muted">
                                    <a href="?page=profile&user=<?=$main_phrase['usuario_nome']?>"><?=htmlspecialchars($main_phrase['usuario_nome'])?></a><br>
                                    <?=date('d/m/Y H:i', strtotime($main_phrase['data_criacao']))?><br>
                                    <?php
                                    $tags_result = mysqli_query($GLOBALS['dblink'], "SELECT tag FROM tags WHERE tipo_dest = 'phrase' AND id_dest = '$id_frase';");
                                    $tags = [];
                                    while ($tag = mysqli_fetch_assoc($tags_result)) {
                                        $tags[] = htmlspecialchars($tag['tag']);
                                    }
                                    if ($tags) { ?>
                                        <?=_t('Tags')?>: <?=implode(', ', $tags)?>
                                    <?php } 
                                    $arts = mysqli_query($GLOBALS['dblink'], "SELECT a.id, a.nome FROM artyg_dest d LEFT JOIN artygs a ON a.id = d.id_artyg WHERE tipo_dest = 'phrase' AND id_dest = '$id_frase';");
                                    if (mysqli_num_rows($arts)>0) { echo '<br>'._t('Artigos').'<br>';
                                        while ($art = mysqli_fetch_assoc($arts)) { ?>
                                            <?='<a href="?page=article&id='.$art['id'].'">'.$art['nome'].'</a><br>';?>
                                    <?php }
                                        } ?>
                                    <?php if ($main_phrase['descricao']){ ?><?php echo '<br>'._t('Tradução original').'<br>'.$main_phrase['descricao']; ?><?php } ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php 

                        if (isset($_GET['compara'])) {
                            $comps = array_unique((array)$_GET['compara']);
                            foreach ($comps as $comp) {

                                $result = mysqli_query($GLOBALS['dblink'], "SELECT 
                                        f.*, i.nome_legivel, i.id_usuario as dono, e.id as eid, e.tamanho, e.id_fonte as fonte, u.username as usuario_nome, e.separadores, e.binario,
                                        f2.frase as frase2, f2.id_idioma as id_idioma2, i2.nome_legivel as nome_legivel2, e2.id as eid2, e2.tamanho as tamanho2, e2.id_fonte as fonte2
                                    FROM frases f
                                    LEFT JOIN idiomas i ON f.id_idioma = i.id
                                    LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                    LEFT JOIN usuarios u ON f.id_criador = u.id
                                    LEFT JOIN frases f2 ON f.id_original = f2.id
                                    LEFT JOIN idiomas i2 ON f2.id_idioma = i2.id
                                    LEFT JOIN escritas e2 ON e2.id_idioma = i2.id AND e2.padrao = 1
                                    WHERE f.id = '$comp';") or die(mysqli_error($GLOBALS['dblink']));
                                $frase = mysqli_fetch_assoc($result);

                                $textoSentenca=$frase['frase'];
                                $separadorPalavras = preg_split('//u', $frase['separadores'], null, PREG_SPLIT_NO_EMPTY) ?: [" "];
                                $iniciadoresPalavras = preg_split('//u', $e['iniciadores'], null, PREG_SPLIT_NO_EMPTY) ?: ["\n"];
                                foreach ($iniciadoresPalavras as $sep){
                                    $textoSentenca = str_replace($sep," ".$sep,$textoSentenca);
                                }
                                $frase_texto = getStudySentence($separadorPalavras,$textoSentenca,$frase['id_idioma'],$frase['eid'], ($frase['binario']>0?' BINARY ':''),$frase['fonte'],$frase['tamanho']  )[0];

                        ?>
                        <div class="mb-3 row">
                            <div class="col-9">
                                
                                <div style="white-space:preserve;
                                    transform: scale(1.5);
                                    transform-origin: top left;
                                    width: 62%;
                                    display: block; margin-bottom:10%"

                                    <?php if ($frase['fonte'] == 3) { ?>
                                        class="editable-drawchar custom-font-<?=$frase['eid']?>"
                                    <?php } else { ?>
                                        class="custom-font-<?=$frase['eid']?>"
                                    <?php } ?>

                                    id="textoMarcado"><?php echo $frase_texto; ?></div>

                                
                            </div>
                            <div class="col-3">
                                <p class="text-muted">
                                    <a href="?page=profile&user=<?=$frase['usuario_nome']?>"><?=htmlspecialchars($frase['usuario_nome'])?></a><br>
                                    <?=date('d/m/Y H:i', strtotime($frase['data_criacao']))?><br>
                                    <?php
                                    $tags_result = mysqli_query($GLOBALS['dblink'], "SELECT tag FROM tags WHERE tipo_dest = 'phrase' AND id_dest = '$id_frase';");
                                    $tags = [];
                                    while ($tag = mysqli_fetch_assoc($tags_result)) {
                                        $tags[] = htmlspecialchars($tag['tag']);
                                    }
                                    if ($tags) { ?>
                                        <?=_t('Tags')?>: <?=implode(', ', $tags)?>
                                    <?php } 
                                    $arts = mysqli_query($GLOBALS['dblink'], "SELECT a.id, a.nome FROM artyg_dest d LEFT JOIN artygs a ON a.id = d.id_artyg WHERE tipo_dest = 'phrase' AND id_dest = '$id_frase';");
                                    if (mysqli_num_rows($arts)>0) { echo '<br>'._t('Artigos').'<br>';
                                        while ($art = mysqli_fetch_assoc($arts)) { ?>
                                            <?='<a href="?page=article&id='.$art['id'].'">'.$art['nome'].'</a><br>';?>
                                    <?php }
                                        } ?>
                                    <?php if ($frase['descricao']){ ?><?php echo '<br>'._t('Tradução original').'<br>'.$frase['descricao']; ?><?php } ?>

                                    <a class="text-muted text-secondary" href="?page=language&iid=<?=$frase['id_idioma']?>"><?=htmlspecialchars($frase['nome_legivel'])?></a>
                                    <a href="<?php echo removerParametro('compara', $comp); ?>" class="btn btn-sm">Remover</a>
                                </p>
                            </div>
                        </div>

                        <?php   }  } ?>

                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Traduções')?></h3>
                        <div class="card-actions">
                            <div class="row">
                                <?php if($_SESSION['KondisonairUzatorIDX']>0){?>
                                <div class="col">
                                        <a href="index.php?page=editphrase&original=<?=$id_frase?>" class="btn btn-primary d-none d-sm-inline-block">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Adicionar tradução')?>
                                    </a>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class=" ">
                            <div class="row align-items-center">

                                <?php if ($main_phrase['id_original'] > 0) { ?>
                                    <a class="col" href="?page=phrase&id=<?=$main_phrase['id_original']?>&iid=<?=$main_phrase['id_idioma2']?>"> 
                                        <?php if ($main_phrase['fonte2'] == 3) { ?>
                                            <div class="form-control editable-drawchar custom-font-<?=$main_phrase['eid2']?>" id="drawchar_editable_<?=$main_phrase['eid2']?>" data-eid="<?=$main_phrase['eid2']?>" data-fonte="<?=$main_phrase['fonte2']?>" data-tamanho="<?=$main_phrase['tamanho2']?>">
                                                <?=htmlspecialchars($main_phrase['frase2'])?>
                                            </div>
                                        <?php } else { ?>
                                            <span class="custom-font-<?=$main_phrase['eid2']?>"><?=htmlspecialchars($main_phrase['frase2'])?></span>
                                        <?php } ?>
                                        <p>
                                            <a class="text-muted text-secondary" href="?page=language&iid=<?=$main_phrase['id_idioma2']?>"><?=_t('%1 (frase original)',[$main_phrase['nome_legivel2']])?></a>
                                            <a href="<?php echo adicionarParametro('compara', $main_phrase['id_original']); ?>" class="btn btn-sm"><?=_t('Comparar')?></a>
                                        </p>
                                    </a>
                                <?php } ?>
                                <?php foreach ($translations as $translation) { ?>

                                <a class="col" href="?page=phrase&id=<?=$translation['id']?>&iid=<?=$translation['id_idioma']?>">
                                    <?php if ($translation['fonte'] == 3) { ?>
                                        <div class="form-control editable-drawchar custom-font-<?=$translation['eid']?>" id="drawchar_editable_<?=$translation['eid']?>" data-eid="<?=$translation['eid']?>" data-fonte="<?=$translation['fonte']?>" data-tamanho="<?=$translation['tamanho']?>">
                                            <?=htmlspecialchars($translation['frase'])?>
                                        </div>
                                    <?php } else { ?>
                                        <span class="custom-font-<?=$translation['eid']?>"><?=htmlspecialchars($translation['frase'])?></span>
                                    <?php } ?>
                                    <p>
                                        <a class="text-muted text-secondary" href="?page=language&iid=<?=$translation['id_idioma']?>"><?=htmlspecialchars($translation['nome_legivel'])?></a>
                                        <a href="<?php echo adicionarParametro('compara', $translation['id']); ?>" class="btn btn-sm"><?=_t('Comparar')?></a>
                                    </p>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
<script>
$(document).ready(function() {
    $('.pstud').tooltip({html:true});  
});
function cpk(pids = '', st = 9, aid, pal = '', ps = '0',este,refs){
	$(".pstud").removeClass('palSelected');
    if (!refs) return;
    refs.split('.').forEach(element => {
        $(".r"+refs).addClass('palSelected');
    });
}
</script>