
<?php 
$banco = $_GET['id'];
$id_iidesc = $_SESSION['KondisonairUzatorDiom'];

if ($_GET['iid']>0) $id_idioma = $_GET['iid'];

$idioma = array();
$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho
            FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
               WHERE i.id = '".$id_iidesc."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};
$escritadesc = $idioma['eid'];
$fontedesc = $idioma['fonte'];
$tamanhodesc = $idioma['tamanho'];

$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho, e.substituicao
            FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
               WHERE i.id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};
$escrita = $idioma['eid'];
$fonte = $idioma['fonte'];
$tamanho = $idioma['tamanho'];

    $query = "SELECT * FROM wordbanks l
        WHERE id = ".$banco.";";
    $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    $bancoDados = mysqli_fetch_assoc($result);


	$scriptAutoSubstituicao = '';

    $escritaPadrao = 1; $fonte = 0;

    $scriptSalvarNativo .= 'salvarNativo('.$escrita.');';
    $autoon = '';

    if($fonte==3){

        if($idioma['substituicao']==1){

            $scriptAutoSubstituicao .= '$.post("api.php?action=getAutoSubstituicao&eid='.$escrita.'",{ p: data }, function (data2){
                if(data2=="-1") exibirNativa(id,"",'.$fonte.',"'.$tamanho.'");
                else { if(data2.length > 0) exibirNativa(id,data2,'.$fonte.',"'.$tamanho.'");}
            });';

            $autoon = ' ('._t('Automático').')';
        }

    }else{

        if($idioma['substituicao']==1){

            $scriptAutoSubstituicao .= 'let data2 = getAutoSubstituicao("'.$escrita.'",data);
                if (data2 == "-1") exibirNativa(id,"","'.$fonte.'","'.$tamanho.'");
                else if(data2.length > 0) exibirNativa(id,data2,"'.$fonte.'","'.$tamanho.'");';

            $autoon = ' ('._t('Automático').')';
        }
    };
   
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
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Importar')?></a></li>
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

            <div class="col-6">
                  <div class="card sticky-top">
                    <div class="card-header">
                      <h3 class="card-title"><?=_t('Gerar palavras')?></h3>
                      <div class="card-actions">
                        <?php if ($_GET['iid']>0){ ?>
                        <a href="#" class="btn btn-primary" onclick="aplicarGerar()">
                          <?=_t('Gerar')?>
                        </a>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label"><?=_t('Idioma')?></label>
                                <select class="form-select" id="idsig" onchange="window.location.href='?page=wordbank&id=<?=$_GET['id']?>&iid='+$('#idsig').val()"><option value="0" selected><?=_t('Selecionar meu idioma...')?></option><?php 
                                        $oiids = mysqli_query($GLOBALS['dblink'],
                                        "SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
                                        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                        WHERE i.id_usuario = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink'])); // AND buscavel = 1
                                    while($oid = mysqli_fetch_assoc($oiids)) {
                                        echo '<option value="'.$oid['iid'].'" data=e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'" '.($oid['iid']==$_GET['iid']?'selected':'').'>'.$oid['nome_legivel'].'</option>';
                                    };
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-6">
                                <label class="form-label"><?=_t('Quantidade de palavras')?></label>
                                <input type="text" class="form-control" name="example-text-input" id="num_palavras" value="100">
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="overflow-y: auto;max-height: 60vh;">
                        <div class="mr-0 row" id="divnp">
                                <label class="form-label text-secondary"><?=_t('As classes, sílabas e pesos devem ser configurados na tela Sílabas.')?></label>
                        </div>
                    </div>
                  </div>
              </div>


            <div class="col-6">
                <div class="card">

                    <div class="card-header">
                        <h3 class="card-title"><?=$bancoDados['titulo']?></h3>
						<div class="card-actions">
                            <?php if($_GET['iid']>0){ ?>
                                <a href="#" class="btn btn-primary" onclick="aplicarImportacao()">
                                <?=_t('Importar')?>
                                </a>
                            <?php }; ?>
						</div>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php 
                            if ($escrita > -1) $qextra = '';
                            if($id_idioma>0/* && $id_idioma!=$id_iidesc*/)
                                if ($escrita > -1)
                                    $qxtra = "(SELECT GROUP_CONCAT(pronuncia, '*', significado, '*', romanizacao, '*', palavra SEPARATOR '|') 
                                        FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id AND epn.id_escrita = ".$escrita."
                                        LEFT JOIN palavras_referentes pr ON pr.id_palavra = ep.id
                                        WHERE pr.id_referente = r.id AND ep.id_idioma = ".$id_idioma." AND ep.id_forma_dicionario = 0) as palavrasExtras,";
                                else
                                    $qxtra = "(SELECT GROUP_CONCAT(pronuncia, '*', significado, '*', romanizacao SEPARATOR '|') 
                                        FROM palavras ep
                                        LEFT JOIN palavras_referentes pr ON pr.id_palavra = ep.id
                                        WHERE pr.id_referente = r.id AND ep.id_idioma = ".$id_idioma." AND ep.id_forma_dicionario = 0) as palavrasExtras,";

                            $query = "SELECT b.*, r.descricao,".$qxtra."
                                (SELECT GROUP_CONCAT(pronuncia, '*', significado, '*', romanizacao, '*', palavra SEPARATOR '|') 
                                    FROM palavras ep LEFT JOIN palavrasNativas epn ON epn.id_palavra = ep.id
                                    LEFT JOIN palavras_referentes pr ON pr.id_palavra = ep.id
                                    WHERE pr.id_referente = r.id AND ep.id_idioma = ".$id_iidesc." AND ep.id_forma_dicionario = 0) as palavrasSig

                                FROM listas_referentes b 
                                LEFT JOIN referentes r ON r.id = b.id_referente
                                    WHERE b.id_lista = ".$banco."
                                ORDER BY ordem;"; // palavras na lingua

                            // iid pra referenciar as palavras, não referentes

                            $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 

                            $totalWords = 0;
                            while($r = mysqli_fetch_assoc($result)){
                                $pals = explode("|",$r['palavrasSig']);

                                $nativo = '';
                                $pronuncia = '';
                                $romanizacao = '';
                                $signficado = '';

                                foreach($pals as $pal){
                                    $pal = explode("*",$pal);
                                    $nativo .= $pal[3].', ';
                                    $pronuncia .= $pal[0].', ';
                                    $romanizacao .= $pal[2].', ';
                                    $signficado .= $pal[1].', ';
                                }

                                $jatem = '';
                                //if($id_iidesc!=$id_idioma){
                                    $exs = explode("|",$r['palavrasExtras']);
                                    foreach($exs as $ex){
                                        $x = explode("*",$ex);

                                        if($x[3] && $escrita>-1) $jatem .= getSpanPalavraNativa($x[3],$escrita,$fonte,$tamanho).' ';

                                        if($x[2]) $jatem .= $x[2].' '; // $romanizacao .= $pal[2].', ';
                                        else if($x[0]) $jatem .= '/'.$x[0].'/ '; //$pronuncia .= $pal[0].', ';
                                        
                                        //$signficado .= $pal[1].', ';
                                    }
                                //}

                                echo '<div class="list-group-item" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)" id="r'.$r['id_referente'].'">
                                    <div class="row align-items-center" id="r'.$r['id_referente'].'">
                                        <div class="col-auto" ><input type="checkbox" value="'.$r['id_referente'].'" id="ck'.$r['id_referente'].'" class="form-check-input ck-inputs"></div>
                                        <div class="col-auto">
                                            <a href="#" class="text-reset d-block">'.($nativo!=''?getSpanPalavraNativa(substr($nativo,0,-2),$escritadesc,$fontedesc,$tamanhodesc)/*'<span class="custom-font-'.$escritadesc.'">'.substr($nativo,0,-2).'</span>'*/: ( $romanizacao!='' ? substr($romanizacao,0,-2):substr($pronuncia,0,-2) ) ).'</a>
                                            <div class="d-block text-secondary mt-n1">'.$r['descricao'].'</div>
                                        </div>
                                        <div class="col">
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">'._t('Pronuncia').'</span>
                                                <input type="text" class="form-control" autocomplete="off" id="pron'.$r['id_referente'].'" onchange="checarPronuncia(\''.$r['id_referente'].'\',\''.$id_idioma.'\')" onkeyup="editarPalavra(\''.$r['id_referente'].'\')">
                                            </div>';
                                if ($escrita>-1){
                                            echo '<div class="input-group mb-2">
                                                <span class="input-group-text">'._t('Romanizacao').'</span>
                                                <input type="text" class="form-control" autocomplete="off" id="rom'.$r['id_referente'].'" onkeyup="editarPalavra(\''.$r['id_referente'].'\')" onchange="checarRomanizacao(\''.$r['id_referente'].'\',\''.$id_idioma.'\')">
                                            </div>';
                                }

                                if ($escrita>-1){
                                    if($fonte==3){
                                        // echo input oculto e div visual e btn de add lateral
                                    }else{
                                        echo '<div class="input-group mb-2">
                                                <span class="input-group-text">'._t('Nativo').'</span>
                                                <input type="text" class="form-control custom-font-'.$escrita.'" autocomplete="off" id="nat'.$r['id_referente'].'" onkeyup="editarPalavra(\''.$r['id_referente'].'\')" onchange="checarNativo(\''.$r['id_referente'].'\',\''.$escrita.'\')">
                                            </div>';
                                    }
                                }

                                echo '<div class="input-group mb-2">
                                                <span class="input-group-text">'._t('Significado').'</span>
                                                <input type="text" class="form-control" autocomplete="off" id="sig'.$r['id_referente'].'" onkeyup="editarPalavra(\''.$r['id_referente'].'\')">
                                            </div>';
                                
                                if ($jatem!='') echo '<label class="form-label alert alert-warning">Já tem palavra(s) com este referente: '.$jatem.'</label>';

                                echo '</div>
                                    </div>
                                </div>';

                                $totalWords++;
                            };

                        ?>
                    </div>
                </div>
            </div>


            </div>
          </div>
        </div>

<script>

    $("#num_palavras").val('<?=$totalWords?>');

	function exibirNativa(id,palavra,fonte = 0, tamanho = ''){
		$('#nat'+id).val( palavra );
	}

    function checarRomanizacao(id,idioma){
		editarPalavra(id);
	};
	<?php if($idioma['checar_sons']==1){ ?>
	function checarPronuncia(id,idioma){
		editarPalavra(id);
		var tmpPron = $("#pron"+id).val();
		$("#pron"+id).removeClass( 'is-invalid' );
        let data = getChecarPronuncia(idioma, tmpPron, 1);
        console.log('pron: '+data);
        if(data=='-1'){ 
            $("#pron"+id).addClass( 'is-invalid' );
        }else{
            $("#pron"+id).val( data );
            $("#rom"+id).val( tmpPron );
            data = tmpPron;
            <?=$scriptAutoSubstituicao?>
        };
	};
	<?php }else{
		echo 'function checarPronuncia(id,idioma){editarPalavra(id);}';
	}; ?>
	function checarNativo(id,eid){
		$("#nat"+id).removeClass( 'is-invalid' );
		editarPalavra(id);
		$.post('api.php?action=getChecarNativo&eid='+eid, {
			p: $("#nat"+id).val()
		}, function (data){ 
			if(data=='-1'){ 
				$("#nat"+id).addClass( 'is-invalid' );
			}else{
				if (data.lenght > 0)
					$("#nat"+id).val( data );
			}
		});
	};

    function editarPalavra(id){
        $("#ck"+id).attr("checked","true");
    };

    function aplicarImportacao(){
        var pals = []; 
        var rid = 0;
        var ok = true;
        $(".ck-inputs").each(function(el){ 
            if($(this).is(':checked')){
                rid = $(this).val();
                pals.push({rid: rid, pron: $("#pron"+rid).val(), rom: $("#rom"+rid).val(), nat: $("#nat"+rid).val(), sig: $("#sig"+rid).val()});
                if ($("#pron"+rid).val() == '' || $("#sig"+rid).val() == '') ok = false;
            }
        });

        if (ok){
            $.post('api.php?action=importWordbank&iid=<?=$id_idioma?>&eid=<?=$escrita?>', {
                pals
            }, function (data){ 
                if($.trim(data) > -1){ 
                    alert($.trim(data)+' palavras importadas!');
                    window.location.href = 'index.php?page=editlexicon&iid=<?=$id_idioma?>';
                }else{
                    alert(data);
                }
            });
        }else{
            alert("<?=_t("Não se esqueça de preencher pelo menos a pronúncia e o significado das palavras que deseja importar.")?>");
        }
    };


function aplicarGerar(){
    $('.genPal').remove();
    $.get("?action=getKWG&iid=<?=$id_idioma?>&count="+$("#num_palavras").val(), function (data){
        $.trim(data).split("\n").forEach(el => {
            $("#divnp").after('<span class="genPal btn" id="'+el+'" draggable="true" ondragstart="dragstartHandler(event)">'+el+'</span>');
        });
	});
};

function dragstartHandler(ev) {
  ev.dataTransfer.setData("palavra", ev.target.id);
} 
function dragoverHandler(ev) {
  ev.preventDefault();
}
function dropHandler(ev) {
    ev.preventDefault();
    const data = ev.dataTransfer.getData("palavra");
    var ref = ev.target.id.replace(/\D+/g, '');

    if ( ref > 0 ) {
        $("#pron"+ref).val(data);
        checarPronuncia(ref,'<?=$id_idioma?>'); editarPalavra(ref);
    }
}
formatarTablerSelect('idsig',null);
let soundsChanged = <?=getLastChange('sounds',$id_idioma)?>;
if ( soundsChanged > localStorage.getItem("k_pronuncias_updated_<?=$id_idioma?>") ) loadPronuncias('<?=$id_idioma?>',soundsChanged, true);
soundsChanged = <?=getLastChange('autosubstituicoes',$escrita)?>;
if ( soundsChanged > localStorage.getItem("k_autosubs_updated_<?=$escrita?>") ) loadAutoSubstituicoes('<?=$escrita?>', soundsChanged, true);
</script>