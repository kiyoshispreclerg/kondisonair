<?php 
$banco = $_GET['id'];
$id_iidesc = $_SESSION['KondisonairUzatorDiom'];

$id_idioma = 0;
if ($_GET['iid']>0) $id_idioma = $_GET['iid'];

$compare = $_GET['compare'] ?? '';
$compare_iids = array_filter(explode(',', $compare));

$all_iids = [];
if($id_idioma > 0) $all_iids[] = $id_idioma;
foreach($compare_iids as $ci) if($ci > 0 && !in_array($ci, $all_iids)) $all_iids[] = $ci;

$idioma_desc = array();
$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho
            FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
               WHERE i.id = '".$id_iidesc."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma_desc  = $r;
};
$escritadesc = $idioma_desc['eid'];
$fontedesc = $idioma_desc['fonte'];
$tamanhodesc = $idioma_desc['tamanho'];

$idiomas = [];
foreach($all_iids as $iid){
    $result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho, e.substituicao
                FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                   WHERE i.id = '".$iid."';") or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)) { 
    $idiomas[$iid]  = $r;
    };
}

    $query = "SELECT * FROM wordbanks l
        WHERE id = ".$banco.";";
    $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    $bancoDados = mysqli_fetch_assoc($result);

   
?>

        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=wordbanks"><?=_t('Bancos de palavras')?></a></li>
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Comparar')?></a></li>
                    </ol>
                </h2>
              </div>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-cards">

            <div class="col-3">
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title"><?=_t('Selecionar idiomas para comparar')?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label"><?=_t('Idioma principal')?></label>
                                <select class="form-select" id="idsig">
                                    <option value="0" <?php echo ($id_idioma==0?'selected':''); ?>><?=_t('Selecionar idioma...')?></option><?php 
                                    $oiids = mysqli_query($GLOBALS['dblink'],
                                        "SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
                                        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                        WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."';") or die(mysqli_error($GLOBALS['dblink']));
                                    while($oid = mysqli_fetch_assoc($oiids)) {
                                        echo '<option value="'.$oid['iid'].'" data-e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'" '.($oid['iid']==$id_idioma?'selected':'').'>'.$oid['nome_legivel'].'</option>';
                                    };

                                    $oiids = mysqli_query($GLOBALS['dblink'],
                                        "SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
                                        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                        WHERE i.publico = 1;") or die(mysqli_error($GLOBALS['dblink']));
                                    if (mysqli_num_rows($oiids)>0) echo '<option value="0" disabled>'._t('Idiomas naturais').'</option>';
                                    while($oid = mysqli_fetch_assoc($oiids)) {
                                        echo '<option value="'.$oid['iid'].'" data-e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'" '.($oid['iid']==$id_idioma?'selected':'').'>'.$oid['nome_legivel'].'</option>';
                                    };
                                    ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label"><?=_t('Adicionar idioma para comparação')?></label>
                                <select class="form-select" id="add_compare">
                                    <option value="0" selected><?=_t('Selecionar idioma...')?></option><?php 
                                    $oiids = mysqli_query($GLOBALS['dblink'],
                                        "SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
                                        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                        WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."';") or die(mysqli_error($GLOBALS['dblink']));
                                    while($oid = mysqli_fetch_assoc($oiids)) {
                                        echo '<option value="'.$oid['iid'].'" data-e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'">'.$oid['nome_legivel'].'</option>';
                                    };

                                    $oiids = mysqli_query($GLOBALS['dblink'],
                                        "SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
                                        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                        WHERE i.publico = 1;") or die(mysqli_error($GLOBALS['dblink']));
                                    if (mysqli_num_rows($oiids)>0) echo '<option value="0" disabled>'._t('Idiomas naturais').'</option>';
                                    while($oid = mysqli_fetch_assoc($oiids)) {
                                        echo '<option value="'.$oid['iid'].'" data-e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'">'.$oid['nome_legivel'].'</option>';
                                    };
                                    ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?=_t('Filtrar')?></label>
                                <input type="text" class="form-control" id="filter_search" placeholder="<?=_t('Buscar por descrição ou palavra...')?>">
                            </div>
                        </div>
                    </div>
                  </div>
              </div>

<?php if(!empty($all_iids)){ ?>
<div class="row row-cards">
<?php foreach($all_iids as $iid){ 
    $idioma = $idiomas[$iid];
    $escrita = $idioma['eid'];
    $fonte = $idioma['fonte'];
    $tamanho = $idioma['tamanho'];

    if ($escrita > -1)
        $qxtra = "(SELECT GROUP_CONCAT(pronuncia, '*', significado, '*', romanizacao, '*', palavra SEPARATOR '|') 
            FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id AND epn.id_escrita = ".$escrita."
            LEFT JOIN palavras_referentes pr ON pr.id_palavra = ep.id
            WHERE pr.id_referente = r.id AND ep.id_idioma = ".$iid." AND ep.id_forma_dicionario = 0) as palavrasExtras,";
    else
        $qxtra = "(SELECT GROUP_CONCAT(pronuncia, '*', significado, '*', romanizacao SEPARATOR '|') 
            FROM palavras ep
            LEFT JOIN palavras_referentes pr ON pr.id_palavra = ep.id
            WHERE pr.id_referente = r.id AND ep.id_idioma = ".$iid." AND ep.id_forma_dicionario = 0) as palavrasExtras,";

    $query = "SELECT b.*, d.descricao,".$qxtra."
        (SELECT GROUP_CONCAT(pronuncia, '*', significado, '*', romanizacao, '*', palavra SEPARATOR '|') 
            FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
            LEFT JOIN palavras_referentes pr ON pr.id_palavra = ep.id
            WHERE pr.id_referente = r.id AND ep.id_idioma = ".$id_iidesc." AND ep.id_forma_dicionario = 0) as palavrasSig

        FROM listas_referentes b 
        LEFT JOIN referentes r ON r.id = b.id_referente
        LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$_SESSION['KondisonairUzatorDiom']."'
            WHERE b.id_lista = ".$banco."
        ORDER BY ordem;";

    $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 

    $temp_compares = $compare_iids;
    if($iid == $id_idioma){
        $new_iid = !empty($temp_compares) ? array_shift($temp_compares) : 0;
        $new_compare = implode(',', $temp_compares);
    }else{
        $new_iid = $id_idioma;
        $new_compare = implode(',', array_filter(array_diff($compare_iids, [$iid])));
    }
    $remove_url = '&iid='.$new_iid.'&compare='.$new_compare;
    ?>
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=$idioma['nome_legivel']?></h3>
                        <div class="card-actions">
                            <a href="?page=wordbank&id=<?=$banco?>&iid=<?=$iid?>" class="btn btn-primary"><?=_t('Editar')?></a>
                            <a href="?page=wordcompare&id=<?=$banco?><?=$remove_url?>" class="btn btn-danger"><?=_t('Remover')?></a>
                        </div>
                    </div>
                    <div class="list-group list-group-flush word-list" style="overflow-y: auto;max-height: 60vh;" data-iid="<?=$iid?>">
                        <?php 
                            while($r = mysqli_fetch_assoc($result)){
                                $pals = explode("|",$r['palavrasSig']);

                                $nativo_desc = '';
                                $pron_desc = '';
                                $rom_desc = '';
                                $sig_desc = '';

                                foreach($pals as $pal){
                                    $pal = explode("*",$pal);
                                    $nativo_desc .= ($pal[3] ?? '').', ';
                                    $pron_desc .= $pal[0].', ';
                                    $rom_desc .= $pal[2].', ';
                                    $sig_desc .= $pal[1].', ';
                                }

                                $desc_word = ($nativo_desc!='' ? getSpanPalavraNativa(substr($nativo_desc,0,-2),$escritadesc,$fontedesc,$tamanhodesc) : ( $rom_desc!='' ? substr($rom_desc,0,-2):substr($pron_desc,0,-2) ) );

                                $exs = explode("|",$r['palavrasExtras'] ?? '');

                                $pronuncia = '';
                                $significado = '';
                                $romanizacao = '';
                                $nativo = '';

                                $has_words = false;

                                foreach($exs as $ex){
                                    if($ex){
                                        $has_words = true;
                                        $x = explode("*",$ex);
                                        $pronuncia .= $x[0].', ';
                                        $significado .= $x[1].', ';
                                        $romanizacao .= ($x[2] ?? '').', ';
                                        if($escrita > -1) $nativo .= ($x[3] ?? '').', ';
                                    }
                                }

                                $pronuncia = substr($pronuncia,0,-2);
                                $significado = substr($significado,0,-2);
                                $romanizacao = substr($romanizacao,0,-2);
                                $nativo = substr($nativo,0,-2);

                                echo '<div class="list-group-item" id="r'.$r['id_referente'].'">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <a href="#" class="text-reset d-block">'.$desc_word.'</a>
                                            <div class="d-block text-secondary mt-n1">'.$r['descricao'].'</div>';
                                if($has_words){
                                    if ($escrita>-1){
                                        echo '<div class="mb-2">
                                            <span class="custom-font-'.$escrita.'">'.$nativo.'</span>
                                        </div>';
                                    }
                                    echo '<div class="mb-2">
                                            <span class="">'.( $romanizacao ? $romanizacao.' /'.$pronuncia.'/' : $pronuncia).'</span>
                                        </div>';

                                    echo '<div class="mb-2">
                                            <span class="">'.$significado.'</span>
                                        </div>';
                                }else{
                                    echo '<div class="text-secondary">'._t('Nenhuma palavra definida neste idioma.').'</div>';
                                }
                                echo '</div>
                                    </div>
                                </div>';
                            }
                        ?>
                    </div>
                </div>
            </div>
<?php } ?>
</div>
<?php } ?>

            </div>
          </div>
        </div>

<script>
    let current_compare = '<?=$compare?>';
    $('#idsig').on('change', function(){
        let url = '?page=wordcompare&id=<?=$banco?>&iid=' + this.value;
        if(current_compare) url += '&compare=' + current_compare;
        window.location.href = url;
    });
    $('#add_compare').on('change', function(){
        let val = this.value;
        if(val == 0) return;
        let compares = current_compare ? current_compare.split(',') : [];
        if(compares.includes(val)) return;
        let new_compare = current_compare ? current_compare + ',' + val : val;
        window.location.href = '?page=wordcompare&id=<?=$banco?>&iid=' + $('#idsig').val() + '&compare=' + new_compare;
    });
    $('#filter_search').on('keyup', function(){
        var val = this.value.toLowerCase();
        $('.list-group-item').each(function(){
            var text = $(this).text().toLowerCase();
            if(text.indexOf(val) > -1) $(this).show(); else $(this).hide();
        });
    });

    // Synchronized scrolling
    let isSyncing = false;
    /*
    $('.word-list').on('scroll', function(){
        if(isSyncing) return;
        isSyncing = true;

        const $thisList = $(this);
        const scrollTop = $thisList.scrollTop();
        const thisIid = $thisList.data('iid');

        // Find the visible referent item
        let visibleReferent = null;
        $thisList.find('.list-group-item').each(function(){
            const $item = $(this);
            const itemTop = $item.position().top;
            if(itemTop >= 0 && itemTop < $thisList.height() / 2){
                visibleReferent = $item.attr('id');
                return false; // Break loop
            }
        });

        if(visibleReferent){
            // Sync other lists to the same referent
            $('.word-list').each(function(){
                if($(this).data('iid') !== thisIid){
                    const $otherList = $(this);
                    const $targetItem = $otherList.find('#' + visibleReferent);
                    if($targetItem.length){
                        $otherList.scrollTop($targetItem.position().top + $otherList.scrollTop());
                    }
                }
            });
        }

        isSyncing = false;
    });
    */

    formatarTablerSelect('idsig', null);
    formatarTablerSelect('add_compare', null);
</script>