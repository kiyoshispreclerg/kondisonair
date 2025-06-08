
<?php 
    
    if ($_GET['iid'] > 0){ //all articles linked directly to this language 
        $query = 'SELECT a.*, i.nome_legivel as nomeidioma,
            (SELECT a2.nome FROM artyg_dest d LEFT JOIN artygs a2 ON d.id_dest = a2.id WHERE d.tipo_dest = "text" AND d.id_artyg = a.id LIMIT 1) as dest_text
          FROM artygs a 
            LEFT JOIN idiomas i ON i.id = a.id_idioma 
          WHERE a.id_idioma  = '.$_GET['iid'].'
            AND a.id_pap = 0 AND a.publico > -1;';
            // destino

        $result = mysqli_query($GLOBALS['dblink'],"SELECT i.* 
            FROM idiomas i
               WHERE i.id = '".$_GET['iid']."';") or die(mysqli_error($GLOBALS['dblink']));
        $idioma = mysqli_fetch_assoc($result);
        $mostrarIndice = false;

    // }else if ($_GET['eid'] > 0){ //all articles linked directly to this writing system

    // }else if ($_GET['uid'] > 0){ //all articles linked directly to this universe

    // }else if ($_GET['kid'] > 0){ //all articles linked directly to this part of speech

    // }else if ($_GET['tid'] > 0){ //all articles linked directly to this text

    }else if($_SESSION['KondisonairUzatorIDX']>0){ // all my articles
        $query = 'SELECT a.*, i.nome_legivel FROM artygs a 
            LEFT JOIN idiomas i ON i.id = a.id_idioma
            WHERE a.id_usuario = '.$_SESSION['KondisonairUzatorIDX'].'
            AND a.id_pap = 0  AND a.publico > -1 ORDER BY i.nome_legivel;';
        $mostrarIndice = true;
    }else {
        echo '<script>window.location = "index.php";</script>';
        exit;
    };
   
?>

<input type="hidden" id="curTexto" value="0" />

        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <?php if($_GET['iid'] > 0){?>
                        <li class="breadcrumb-item"><a href="?page=<?=$idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX']?'edit':''?>language&iid=<?=$idioma['id']?>"><?=$idioma['nome_legivel']?></a></li>
                        <li class="breadcrumb-item active"><a href="#"><?=_t('Artigos')?></a></li>
                      <?php }else{ ?>
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Meus artigos')?></a></li>
                      <?php }?>
                    </ol>
                </h2>
              </div>
              
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Artigos')?></h3>
                        <?php if($idioma['id'] > 0){?>
                        <div class="card-actions">
                            <a href="index.php?page=editarticle&iid=<?=$idioma['id']?>" class="btn btn-primary d-none d-sm-inline-block">
                            <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                            <?=_t('Novo')?>
                            </a>
                        </div>
                        <?php }?>
                    </div>
                    <div class="card-bodyx">
                    <div class="list-group list-group-flush overflow-auto" style="max-height: 35rem">

                    <?php 
                        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
                        $ianterior = 0;
                        while($r = mysqli_fetch_assoc($result)){
                            if ($mostrarIndice) {
                                if ($ianterior != $r['id_idioma']){
                                    echo '<div class="list-group-header sticky-top">'.$r['nome_legivel'].'</div>';
                                    $ianterior = $r['id_idioma'];
                                }
                            }

                            $suba = ''; $subas = 0;
                            $query = 'SELECT a.* FROM artygs a 
                                WHERE a.id_pap = '.$r['id'].' AND publico > -1;';
                            $result2 = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
                            while($r2 = mysqli_fetch_assoc($result2)){
                                $subas++;
                                $suba .= '<div class="list-group-item active"><div class="row">
                                        <div class="col">
                                            <a href="?page=article&id='.$r2['id'].'">'.$r2['nome'].'</a>
                                            <div class="text-secondary text-truncate mt-n1">'.
                                            ($r2['dest_text']!=''?'Texto: '.$r2['dest_text']:'').
                                            '</div>
                                        </div>';
                                if($r2['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) $suba .= '<div class="col-auto">
                                            <a href="?page=editarticle&id='.$r2['id'].'&iid='.$r2['id_idioma'].'" class="btn btn-primary">'._t('Editar').'</a>
                                            <a onclick="delArt('.$r2['id'].','.$subas.')" class="btn btn-danger">'._t('Apagar').'</a>
                                        </div>';
                                $suba .= '</div></div>';
                            };

                            echo '<div class="list-group-item"><div class="row">
                                    <div class="col">
                                        <a href="?page=article&id='.$r['id'].'">'.$r['nome'].'</a>
                                        <div class="text-secondary text-truncate mt-n1">'.
                                        ($r['dest_text']!=''?'Texto: '.$r['dest_text']:'').
                                        '</div>
                                    </div>';
                            if($r['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) echo '<div class="col-auto">
                                        <a href="?page=editarticle&id='.$r['id'].'&iid='.$r['id_idioma'].'" class="btn btn-primary">'._t('Editar').'</a>
                                        <a onclick="delArt('.$r['id'].')" class="btn btn-danger">'._t('Apagar').'</a>
                                    </div>';
                            echo '</div></div>'.$suba;
 
                        }; 

                    ?>




                    </div>
                    </div>
                </div>
            </div>


            </div>
          </div>
        </div>



<script>

function delArt(id, subs){
    var titulo = "<?=_t('Apagar este artigo?')?>";
    if (subs > 0) titulo = "<?=_t('Apagar este artigo e seus subartigos?')?>";
    if (confirm(titulo)) {
        
        $.get("api.php?action=ajaxApagarArtigo&aid="+id, function(data){
            if ($.trim(data)=='ok') location.reload(true);
            else alert(data);
        });

    }
}

<?=$funcSalvarTexto?>

</script>
<?=$modalNovoTexto?>