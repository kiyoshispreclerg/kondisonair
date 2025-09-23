
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];
$idioma = array();
$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*,
            (SELECT COUNT(*) FROM escritas where id_idioma = i.id) as numPublishedTexts,
            (SELECT COUNT(*) FROM escritas where id_idioma = i.id) as numUsersTexts,
            (SELECT COUNT(*) FROM soundChanges where id_idioma = i.id) as numChangesList,
            (SELECT COUNT(*) FROM palavras where id_idioma = i.id AND id_forma_dicionario = 0) as numBaseWords,
            (SELECT COUNT(*) FROM classes where id_idioma = i.id) as numParts,
            (SELECT COUNT(*) FROM palavras where id_idioma = i.id) as numTotalWords,
            (SELECT COUNT(*) FROM escritas where id_idioma = i.id) as numWritingSysts,
            (SELECT username FROM usuarios where id = i.id_usuario LIMIT 1) as criador,
            (SELECT es.id FROM escritas es where es.id_idioma = i.id AND es.padrao = 1 LIMIT 1) as eid,
            (SELECT es.id_fonte FROM escritas es where es.id_idioma = i.id AND es.padrao = 1 LIMIT 1) as fonte,
            (SELECT es.tamanho FROM escritas es where es.id_idioma = i.id AND es.padrao = 1 LIMIT 1) as tamanho,
            (SELECT palavra FROM palavrasNativas where id_palavra = i.id_nome_nativo AND id_escrita = (SELECT es.id FROM escritas es where es.id_idioma = i.id AND es.padrao = 1 LIMIT 1) LIMIT 1) as nome_nativo,
            (SELECT COUNT(*) FROM glifos where id_escrita IN(SELECT id FROM escritas where id_idioma = i.id)) as numCharsTotal,
            (SELECT COUNT(*) FROM inventarios where id_idioma = i.id) as numTotalSounds
            FROM idiomas i
               WHERE i.id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};

if ( $idioma['nome_legivel']=='') {
    echo '<script>window.location = "index.php";</script>';
    exit;
}

$romanizacao = $idioma['romanizacao'];

?>
<input type="hidden" id="codigo" value="<?=$id_idioma?>" />



        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item active"><a><?=$id_idioma>0 ? $idioma['nome_legivel'] : 'ERR'?></a></li>
                    </ol>
                </h2>
              </div>
              <?php if ( $idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) { ?>
              <div class="col-auto ms-auto">
                <a href="?page=editlanguage&iid=<?=$id_idioma?>" class="btn btn-primary" id="btnSalvar"><?=_t('Editar')?></a>
              </div>
              <?php }; ?>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deckx row-cards">



            <div class="col-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="form-label"><?php
                        echo strlen($idioma['nome_nativo'])>0 ? getSpanPalavraNativa($idioma['nome_nativo'],$idioma['eid'],$idioma['fonte'],$idioma['tamanho']) : $idioma['nome_legivel'];
                        ?></h3>

                        <div class="datagrid">
                            <div class="datagrid-item mb-3">
                                <div class="datagrid-content"><?=$idioma['descricao']?></div>
                            </div>
                        </div>

                        <div class="datagrid">

                            <div class="datagrid-item">
                                <div class="datagrid-title"><?=_t('Conteúdo publicado')?></div>
                                <div class="datagrid-content">
                                    <?php
                                    if ($idioma['numBaseWords'] == $idioma['numTotalWords']) echo $idioma['numBaseWords'].' '._t('palavras').'<br>';
                                    else{
                                        if ($idioma['numBaseWords']>0) echo $idioma['numBaseWords'].' '._t('palavras base').'<br>';
                                        if ($idioma['numTotalWords']>0) echo $idioma['numTotalWords'].' '._t('palavras no total').'<br>';
                                    };

                                    if ($idioma['numWritingSysts']>0) echo $idioma['numWritingSysts'].' '._t('sistemas de escrita').'<br>'; // com <?=$idioma['numCharsTotal'] caracteres<br>
                                    if ($idioma['numPublishedTexts']>0) echo $idioma['numPublishedTexts'].' '._t('textos publicados').'<br>';
                                    
                                    ?>
                                </div>
                            </div>

                            <div class="datagrid-item">

                                <div class="datagrid-title"><?=_t('Criador/Responsável')?></div>
                                <div class="datagrid-content mb-3">
                                <div class="d-flex align-items-center">
                                    <a href="?page=profile&user=<?=$idioma['criador']?>"><span class="avatar avatar-xs me-2 rounded"><?=substr($idioma['criador'],0,2)?></span></a>
                                </div>
                                </div>

                                <?php 
                                $cs = mysqli_query($GLOBALS['dblink'],
                                    "SELECT username FROM collabs c LEFT JOIN usuarios u ON u.id = c.id_usuario
                                    WHERE c.id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
                                if (mysqli_num_rows($cs)>0) { ?>

                                <div class="datagrid-title"><?=_t('Contribuidores')?></div>
                                <div class="datagrid-content mb-3">
                                <div class="avatar-list avatar-list-stacked">

                                <?php while ($s = mysqli_fetch_assoc($cs)){
                                        echo '<a href="?page=profile&user='.$s['username'].'"><span class="avatar avatar-xs me-2 rounded">'.substr($s['username'],0,2).'</span></a>';
                                } ?>
                                </div>
                                </div>
                                <?php }; ?>

                            </div>
                        </div>

                    </div>
                </div>
                <div class="row row-deckx row-cards">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?=_t('Frases')?></h3>
                                <div class="card-actions">
                                    <a href="?page=phrases&iid=<?=$id_idioma?>" class="btn btn-primary"><?=_t('Ver mais')?></a>
                                </div>
                            </div>
                            
                            <div class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 25rem" id="phrases">
                            </div>
                            
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?=_t('Textos')?></h3>
                                <div class="card-actions">
                                    <a href="?page=texts&iid=<?=$id_idioma?>" class="btn btn-primary"><?=_t('Ver mais')?></a>
                                </div>
                            </div>
                            
                            <div class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 25rem" id="texts">

                            
                                <?php
                                $query = "SELECT t.*,
                                    (SELECT COUNT(*) FROM tests_importasons im WHERE im.id_texto = t.id) as imports
                                    FROM studason_tests t
                                    WHERE t.id_idioma = ".$id_idioma." AND t.num_palavras > 0 ;";

                                $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
                                
                                if (mysqli_num_rows($result)>0){
                                    while($r = mysqli_fetch_assoc($result)){

                                        echo '<div class="list-group-item"><div class="row">
                                            <div class="col-auto">
                                            <a href="?page=text&id='.$r['id'].'">'.$r['titulo'].' </a>
                                            <div class="text-secondary text-truncate mt-n1">'.$r['num_palavras'].' '._t('palavras únicas').' - '.$r['imports'].' '._t('usuários importaram').'</div>
                                            </div>
                                        </div></div>';

                                    };
                                }else echo '<div class="list-group-item">'._t('Nenhum texto').'</div>';;

                            ?>

                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Léxico')?></h3>
                        <div class="card-actions">
                            <input type="text" class="form-control" id="testFilter" onkeyup="testFilter('divWord','testFilter')" placeholder="<?=_t('Buscar')?>">
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 35rem" id="words">

                    </div>
                    
                </div>


            </div>


            </div>
          </div>
        </div>

<script>
function listFormat(json){
    let html = "";
    data = JSON.parse(json);
    $.each( data, function( key, val ) {
        if (val.inicial) html += `<div class="list-group-header sticky-top">`+val.inicial+`</div>`;

        let palavra, sub = ''; // depende da lingua carregada, a função já pega pronuncia, romanizacao ou nativo (com span custom-font)

        <?php 
        if ($idioma['romanizacao']==1) { ?>
            if (val.nativo.length > 0) {
                palavra = val.nativo;
            }else if (val.romanizacao.length > 0) palavra = val.romanizacao;
            else palavra = val.pronuncia;
            sub = val.pronuncia;
        <?php } else { ?>
            if (val.nativo.length > 0) {
                palavra = val.nativo;
                sub = val.pronuncia;
            }else palavra = val.pronuncia;
        <?php }; ?>

        html += `<div data-search="` + val.palavra + val.pronuncia + val.significado + val.romanizacao + val.extras +
            `" class="list-group-item divWord" ` + val.indexdata + `><div class="row">
              <div class="col-auto" data-bs-toggle="tooltip" title="`+sub+`" >
                <a href="?page=word&pid=`+val.id+`&iid=<?=$id_idioma?>">` + palavra + `</a>
              </div>
              <div class="col text-truncate">
                <a href="?page=word&pid=`+val.id+`&iid=<?=$id_idioma?>" class="text-body d-block">`+val.significado+`</a> 
              </div>
          </div></div>`;
    });
    return html ? html : '<div class="list-group-item">Nenhuma palavra.</div>';
}
function loadWords(){

    let data = <?=getLastChange('lexicon',$id_idioma)?>;
    if (data > localStorage.getItem("k_words_<?=$id_idioma?>_updated")){
        console.log('local words outdated > update');
        $.get("api.php?action=simpleListWords&iid=<?=$id_idioma?>&eid=<?=$idioma['eid']?>", function (lex){
            $("#words").html( listFormat(lex) );
            localStorage.setItem("k_words_<?=$id_idioma?>", lex);
            localStorage.setItem("k_words_<?=$id_idioma?>_updated", data);
        })
    }else{
        console.log('local words load');
        $("#words").html( listFormat(localStorage.getItem("k_words_<?=$id_idioma?>")) );
    }
}


function phrasesFormat(json){
    let html = "";
    data = JSON.parse(json);
    $.each( data, function( key, val ) {
        html += `<div class="list-group-item"><div class="row">
                        <div class="col-auto">
                        <a href="?page=phrase&id=`+val.id +`">`+val.frase +`</a>
                        </div>
                    </div></div>`;
    });
    return html ? html : '<div class="list-group-item">Nenhuma frase.</div>';
};

function loadFrases(){
    $.get("api.php?action=listPhrases&iid=<?=$id_idioma?>", function (lex){
        $("#phrases").html(phrasesFormat(lex));
    })
}

loadWords();
loadFrases();
</script>