
<?php 
	$id_idioma = $_GET['iid'];
	$filtro = 'dici';
	if (isset($_GET['t']) && $_GET['t']!='') $filtro = $_GET['t'];

	$idioma = array();   
	$romanizacao = 0;
	$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho, e.substituicao,
        (SELECT COUNT(*) FROM studason_palavrs WHERE id_usuario = '".$_SESSION['KondisonairUzatorIDX']."') as numPal,
        (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' LIMIT 1) as collab
        FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
        WHERE i.id = $id_idioma;") or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)) { 
        $idioma  = $r;
    };
    $romanizacao = $idioma['romanizacao'];
    $eid = $idioma['eid'] ?? 0;
    $fonte = $idioma['fonte'];
    $tamanho = $idioma['tamanho'];
    $substituicao = $idioma['substituicao'];
	if ($_GET['palavra']>0) $filtroPalavra = " AND t.texto LIKE '%".$_GET['palavra']."%' ";
    
    if (($idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX'] || $idioma['collab'] > 0 ) && $id_idioma > 0) { // my language, show add/edit
        $mdason = 'mdason';
        $query = "SELECT t.*,
            (SELECT separadores FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as separadores,
                    (SELECT binario FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as binario,
            (SELECT iniciadores FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as iniciadores,
            (SELECT id FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as eid,
            (SELECT COUNT(*) FROM tests_importasons im WHERE im.id_texto = t.id) as imports
            FROM studason_tests t
            WHERE t.id_idioma = ".$_GET['iid']." AND (t.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' OR t.id_usuario IN(
                SELECT id_idioma FROM collabs WHERE id_usuario = '".$_SESSION['KondisonairUzatorIDX']."')) $filtroPalavra;";
                //echo $query;
        $imports = 'usuários';
        $btnNovoTexto = '<a class="btn btn-primary"onClick="novoTexto()"><i class="fa fa-plus"></i> Novo texto</a>';


        $modalNovoTexto = '<div class="modal modal-blur" id="modalAddText" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content"  >
                <div class="modal-header">
                  <h5 class="modal-title" id="modaltitle"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body panel-body" id="addTextPanel"> 
                    <div class="mb-3">
                        <label class="form-label">'._t('Título legível').'</label>
                        <input type="text" class="form-control" id="testTytol" />
                    </div>
                    <div>
                        <label class="form-label">'._t('Texto nativo').($substituicao ? ' ('._t('Automático').')' : '').
                        ($fonte ==3 ? ' <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasDrawchar" role="button" aria-controls="offcanvasEnd" onclick="loadCharDiv('.$eid.',\'drawcharlist'.$eid.'\',false,'.$fonte.')">'._t('Inserir caractere').'</a>' : '').'</label>'.
                        ( $fonte ==3 ?
                            '<input type="hidden" class="escrita_nativa" id="escrita_nativa_'.$eid.'" />
                            <div class="form-control editable-drawchar" id="drawchar_editable_'.$eid.'" contenteditable="true" data-eid="'.$eid.'" data-fonte="'.$fonte.'" data-tamanho="'.$tamanho.'" style="height: 200px; overflow-y: auto;"></div>'
                            :
                            '<textarea data-bs-toggle="autosize" class="form-control custom-font-'.$eid.'" id="testStudason" rows="10"></textarea>'
                        )
                    .'</div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-success" title="Salvar texto" data-toggle="tooltip" onClick="salvarTexto()">'._t('Salvar').'</a>
                </div>
                </div>
            </div>
            </div>';

        $funcSalvarTexto = '

        function editTexto(id,title){
            $("#curTexto").val(id);
            $("#modaltitle").html("'._t('Editar texto').'");
            $("#testTytol").val(title);
            $.get("api.php?action=testMdason&id="+id, function(data){'.(
                $fonte == 3 ?
                'exibirNativa('.$eid.', $.trim(data), '.$fonte.', "'.$tamanho.'");'
                :'$("#testStudason").val( $.trim(data) );'
                ).'$("#modalAddText").modal("show");
            });
        };

        function novoTexto(){
            $("#curTexto").val(0);
            $("#modaltitle").html("'._t('Novo texto').'");
            $("#testTytol").val("");
            $("#testStudason").val("");
            $("#modalAddText").modal("show");
        };

        function publicaTexto(id){
            $.post("api.php?action=publicaTexto&id="+id, function (data){
                    if ($.trim(data) == "ok"){
                        //$("#modalAddText").modal("hide");
                        location.reload(true);
                    }else{
                        alert(data);
                    }
                });
        };

        function salvarTexto(){
                var texto = '.($fonte == 3 ? '$("#escrita_nativa_'.$eid.'").val()' : '$("#testStudason").val()' ).';
                $.post("api.php?action=testSalvar&iid='.$id_idioma.'&id="+ $("#curTexto").val(), 
                    {   texto: texto,
                        titulo: $("#testTytol").val()
                    }, function (data){
                    if ($.trim(data) == "ok"){
                        //$("#modalAddText").modal("hide");
                        location.reload(true);
                    }else{
                        alert(data);
                    }
                });
            };';
    }else if ($id_idioma > 0){ 
        
        // todos textos públicos da língua, com status particular do usuario logado ou nenhum status
        $query = "SELECT t.*,
            (SELECT separadores FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as separadores,
                    (SELECT binario FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as binario,
            (SELECT iniciadores FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as iniciadores,
            (SELECT id FROM escritas e WHERE e.id_idioma = t.id_idioma ORDER BY e.padrao DESC LIMIT 1) as eid,
            (SELECT COUNT(*) FROM tests_importasons im WHERE im.id_texto = t.id) as imports
            FROM studason_tests t
            WHERE t.id_idioma = ".$_GET['iid']." AND t.num_palavras > 0  $filtroPalavra;"; // 



    }else if ($_SESSION['KondisonairUzatorIDX'] > 0){

        // pegar da tbl textos importados em vez de direto textos, de todos idiomas

        // tests_importasons

        $query = "SELECT s.*,
                (SELECT separadores FROM escritas e WHERE e.id_idioma = s.id_idioma ORDER BY e.padrao DESC LIMIT 1) as separadores,
                (SELECT binario FROM escritas e WHERE e.id_idioma = s.id_idioma ORDER BY e.padrao DESC LIMIT 1) as binario,
                (SELECT iniciadores FROM escritas e WHERE e.id_idioma = s.id_idioma ORDER BY e.padrao DESC LIMIT 1) as iniciadores
            FROM tests_importasons i
                LEFT JOIN studason_tests s ON i.id_texto = s.id
                WHERE i.id_usuario = ".($_SESSION['KondisonairUzatorIDX']?:0)."  $filtroPalavra";//." AND s.num_palavras > 0;";

    }else {
        echo '<script>window.location = "index.php";</script>';
        exit;
    };

	$fonts = '';

	$stats = ''; //$idioma['numPal'].' palavras estudando e conhecidas';
   
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
                      <?php if($id_idioma > 0){?>
                      <li class="breadcrumb-item"><a href="?page=<?=$idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX']?'edit':''?>language&iid=<?=$idioma['id']?>"><?=$idioma['nome_legivel']?></a></li>
                      <?php }?>
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Textos')?></a></li>
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
                        <h3 class="card-title"><?=_t('Textos')?></h3>
                        <div class="card-actions">
                        <?=$btnNovoTexto?>
						</div>
                    </div>
                    <div class="card-bodyx">
                    <div class="list-group list-group-flush overflow-auto" style="max-height: 35rem">


                    <?php 
                        
                        // carrega direto aqui tbl lista studason_tests
                        $separadorLinhas = array("\n");


                        $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
                        
                        
                            
                        while($r = mysqli_fetch_assoc($result)){
                            $textoSentencas = $r['texto'];
                            if ($r['binario']>0) $bin = ' BINARY ';

                            $separadorPalavras = preg_split('//u', $r['separadores'] ?? $separadorRomanizacao, null, PREG_SPLIT_NO_EMPTY); // explode($e['separadores']); // array(" ",",",".");

                            $iniciadoresPalavras = preg_split('//u', $r['iniciadores'], null, PREG_SPLIT_NO_EMPTY);
                            foreach ($iniciadoresPalavras as $sep){
                                $textoSentencas = str_replace($sep," ".$sep,$textoSentencas);
                            }

                            $palDesc = 0;
                            $palCon = 0;
                            $palTotal = 0;
                            $palStud = 0;
                            $palNovas = 0;
                            $palOk = 0;

                            $btnConferir = '';

                            $listaPalavrasUnicas = array();

                            $linhas = multiexplode($separadorLinhas,$textoSentencas);
                            
                            
                            foreach ($linhas as $linha){

                                $palavras = multiexplode($separadorPalavras,$linha);
                                
                                //$listaPalavrasUnicas = array();

                                // ver se o idioma do texto tem eid
                                if ($eid > 0){
                                    $pnp = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = p.id";
                                    //$pnd = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = dic.id";
                                    $pnq = "pn.palavra as nativa, ";
                                    $pno = "pn.palavra ";
                                }else{
                                    $pnp = "";
                                    //$pnd = "LEFT JOIN palavrasNativas pn ON pn.id_palavra = dic.id";
                                    $pnq = "p.romanizacao as nativa, ";
                                    $pno = "p.romanizacao ";
                                }

                                foreach ($palavras as $p){
                                    if ($p == '') continue;
                                    $pids = '';
                                
                                    if($mdason == 'mdason'){
                                        $sql = "SELECT p.*, c.id as clid, $pnq c.nome as cnome,
                                                (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as dic  
                                            FROM palavras p
                                            LEFT JOIN classes c ON p.id_classe = c.id $pnp 
                                            WHERE $bin $pno = '$p' AND p.id_idioma = $id_idioma 
                                            ORDER BY p.id_forma_dicionario DESC;"; 
                                    }else{
                                        $sql = "SELECT p.*, c.id as clid, $pnq c.nome as cnome,
                                                (SELECT pd.id FROM palavras pd WHERE pd.id = p.id_forma_dicionario LIMIT 1) as dic  
                                            FROM palavras p
                                            LEFT JOIN classes c ON p.id_classe = c.id $pnp 
                                            WHERE $bin $pno = '$p' AND p.id_idioma = ".(
                                                $id_idioma > 0 ? $id_idioma : $r['id_idioma']
                                            )." ORDER BY p.id_forma_dicionario DESC;"; 
                                    }
                                    $a = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
                                    $palTotal++;
                                    if (mysqli_num_rows($a)<1){
                                        $palDesc++;
                                    }else{
                                        $palCon++;
                                        
                                        while($qpid = mysqli_fetch_assoc($a)){
                                            $pids .= $qpid['id'].',';
                                        }
                                        
                                        if ($listaPalavrasUnicas[$p]['q'] > 0) $listaPalavrasUnicas[$p]['q'] = $listaPalavrasUnicas[$p]['q'] + 1;
                                        else $listaPalavrasUnicas[$p]['q'] = 1;
                                        
                                        if($mdason == 'mdason'){
                                            $sqlPst = "SELECT * FROM studason_palavrs WHERE pids LIKE '".substr($pids,0,-1)."%' AND id_usuario = ".($_SESSION['KondisonairUzatorIDX']?:0).";";
                                        }else{
                                            $sqlPst = "SELECT * FROM studason_palavrs WHERE pids LIKE '".substr($pids,0,-1)."%' AND id_usuario = '".$_SESSION['KondisonairUzatorIDX']."';";
                                        }
                                        $qpstres = mysqli_query($GLOBALS['dblink'],$sqlPst) or die(mysqli_error($GLOBALS['dblink']));
                                        if (mysqli_num_rows($qpstres)<1){
                                            $palNovas++;
                                        }else{
                                            $qpst = mysqli_fetch_assoc($qpstres);
                                            $pst = $qpst['status_aprendido']==''?'0':$qpst['status_aprendido'];

                                            $listaPalavrasUnicas[$p]['s'] = $pst;

                                            if ($qpst['status_aprendido']==5) $palOk++;
                                            else if ($qpst['status_aprendido']>0) $palStud++;
                                            else $palNovas++;
                                        }
                                    }
                                    // echo ' '.$p.' ';
                                }
                            }

                            if($mdason == 'mdason'){
                                $btnPublicar = '';
                                if ($r['num_palavras']>0) $descs = _t('Publicado');
                                else $descs = _t('Não publicado');

                                if ($palDesc>0) {
                                    $descs .= ' - '._t('Inválido').': '.$palDesc.' '._t('palavras fora do dicionário').'<br>';
                                    $btnConferir = '/'._t('Conferir');
                                }else {
                                    $descs .= $imports=='' ? '' : ' - '._t('Importado por').' '.$r['imports'].' '._t('usuários').'<br>';
                                    if (!$r['num_palavras']>0) $btnPublicar = " <a onclick='publicaTexto(\"".$r['id']."\")' class='btn btn-primary'>"._t('Publicar')."</a> ";
                                }

                                $btnPublicar .= "<a onclick='apagarTexto(\"".$r['id']."\",".$r['imports'].")' class='btn btn-danger'>"._t('Apagar')."</a>";
                                $langName = ''; // nome idioma, caso nao tenha iid
                                
                            }
                            $novasUnicas = 0;
                            foreach($listaPalavrasUnicas as $pal => $pu){if ($pu['s']<1) { $novasUnicas++; } };

                            if($mdason == 'mdason'){
                                echo '<div class="list-group-item"><div class="row">
                                    <div class="col">
                                        <a href="?page=text&id='.$r['id'].'">'.$r['titulo'].$langName.' </a>
                                        <div class="text-secondary mt-n1">'.count($listaPalavrasUnicas)." "._t('palavras')." - ".$novasUnicas." "._t('novas')." (".round($novasUnicas/ (count($listaPalavrasUnicas) > 0 ? count($listaPalavrasUnicas) : 1)*100).'%)</div>
                                    </div>
                                    <div class="col">
                                        <a href="#" onClick="editTexto(\''.$r['id'].'\',\''.$r['titulo'].'\')" class="btn btn-primary">'._t('Editar').'</a>
                                        <div class="text-secondary mt-n1">'.
                                            ($r['imports']>0?_t('Edição do texto indisponível'):'').
                                        '</div>
                                    </div>
                                    <div class="col">'.$descs.$btnPublicar.
                                    '</div>
                                </div></div>';
                            }else{

                                echo '<div class="list-group-item"><div class="row">
                                        <div class="col">
                                        <a href="?page=text&id='.$r['id'].'">'.$r['titulo'].' </a>
                                        <div class="text-secondary text-truncate mt-n1">'.count($listaPalavrasUnicas)." "._t('palavras')." - ".$novasUnicas." "._t('novas')." (".round($novasUnicas/ (count($listaPalavrasUnicas) > 0 ? count($listaPalavrasUnicas) : 1)*100).'%)</div>
                                        </div>
                                        <div class="col">
                                            <a href="?page=text&id='.$r['id'].'" class="btn btn-primary">'._t('Ler').'</a>
                                        </div>
                                    </div></div>';

                            }
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

function apagarTexto(id,imports = 0){
    var titulo = "<?=_t('Apagar mesmo este texto?')?>";
    if (imports > 0) titulo = "<?=_t('Apagar este texto? Os usuários que o abriram também perderão completamente o acesso ao mesmo.')?>";
    if (confirm(titulo)) {
        
        $.get("api.php?action=ajaxApagarTexto&id="+id, function(data){
            if ($.trim(data)=='ok') location.reload(true);
            else alert(data);
        });

    }
}

<?=$funcSalvarTexto?>

</script>
<?=$modalNovoTexto?>