
				 <!-- PANEL START -->
				 <?php 
	$id_idioma = $_GET['iid'];
	$filtro = 'dici';
	if (isset($_GET['t']) && $_GET['t']!='') $filtro = $_GET['t'];

	if (!$_GET['pid']>0) $_GET['pid'] = 0;
	$idioma = array();   
	$romanizacao = 0;
	$result = mysqli_query($GLOBALS['dblink'],"SELECT *, (SELECT nome_legivel FROM idiomas d WHERE d.id = i.id_idioma_descricao LIMIT 1) as desc_idioma,
            (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i
						WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
	$idioma  = $r;
	};
	$romanizacao = $idioma['romanizacao'];
	if ($idioma['nome_legivel']=='' || ( $idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX']  && !$idioma['collab'] > 0 )) {
		
			echo '<script>window.location = "index.php";</script>';
			exit;
	}

	$fonts = '';
   
?>

        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Léxico')?></a></li>
                    </ol>
                </h2>
              </div>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deckx row-cards">
                
            <div class="col-md-3">
                <div class="card sticky-top">
                    <div class="card-body"> 

                        <div class="mb-3" id="filter-div">
                            <label class="form-label"><?=_t('Buscar')?></label>
                            <input type="text" class="form-control" id="testFilter" onkeyup="filtrarPalavras(false)/*testFilter('divWord','testFilter',$('#filtro').val())*/" placeholder="<?=_t('Pesquisar palavra/significado')?>">
                        </div>

                        <div class="mb-4">
                            <div class="form-label"><?=_t('Filtrar')?></div>
                            <select class="form-select" id="filtro" title="<?=_t('Filtrar por')?>..." type="text" value="" onchange="filtrarPalavras(true)">
                                <option value="dici" title="<?=_t('Apenas as palavras na forma de dicionário')?>" <?php if($filtro=='dici') echo 'selected'; ?>><?=_t('Palavras base')?></option>
                                <option value="tudo" title="<?=_t('Todas as flexões e contrações de todas as palavras')?>" <?php if($filtro=='tudo') echo 'selected'; ?>><?=_t('Todas as palavras')?></option>
                                <option disabled><?=_t('Classes de palavras')?></option>
                                <?php 
                                $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classes WHERE id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
                                while ($l = mysqli_fetch_assoc($langs)){
                                    echo '<option value="'.$l['id'].'" title="'.$l['descricao'].'"';
                                    if($filtro==$l['id']) echo ' selected';
                                    echo '>'.$l['nome'].'</option>';
                                }
                                ?>
                                <option disabled><?=_t('Outros tipos de palavras')?></option>
                                <option value="cont" title="<?=_t('Apenas as palavras contraídas')?>" <?php if($filtro=='cont') echo 'selected'; ?>><?=_t('Contrações')?></option>
                                <option value="morf" title="<?=_t('Partes de palavras não usadas como palavras soltas')?>" <?php if($filtro=='morf') echo 'selected'; ?>><?=_t('Morfemas')?></option>
                                <option value="expr" title="<?=_t('Expressões em que as palavras possuem significado diferente ou mais específico do que quando isoladas')?>" <?php if($filtro=='expr') echo 'selected'; ?>><?=_t('Expressões')?></option>
                            </select>
                        </div>

						<div class="mt-5">
							<a class="btn btn-primary w-100 mt-2" title="<?=_t('Recarregar todo o dicionário normalmente')?>" onClick="loadPalavras()" onDblClick="loadPalavras(0,true)"><?=_t('Tudo')?></a>
							<a class="btn btn-primary w-100 mt-2" title="<?=_t('Palavras com a mesma pronúncia')?>" onClick="loadEspecial(2)"><?=_t('Homófonos')?></a>
							<a class="btn btn-primary w-100 mt-2" title="<?=_t('Palavras com a mesma escrita')?>" onClick="loadEspecial(3)"><?=_t('Homógrafos')?></a>
							<a class="btn btn-primary w-100 mt-2" title="<?=_t('Palavras com apenas um som diferente entre si')?>" onClick="loadEspecial(4)"><?=_t('Pares mínimos')?></a>
                        </div>

                        <div class="mt-5">
						    <!--a class="btn btn-primary w-100 mt-2" href="index.php?page=usagelevels&iid=<?=$id_idioma?>" >Níveis de uso</a>
							<a class="btn btn-primary w-100 mt-2" title="<?=_t('Gerador de palavras aleatórias')?>" href="index.php?page=wordgen&iid=<?=$id_idioma?>"><?=_t('Gerador de palavras')?></a-->
							<a class="btn btn-primary w-100 mt-2" title="<?=_t('Listas de palavras para rápida importação')?>" href="index.php?page=wordbanks&iid=<?=$id_idioma?>"><?=_t('Bancos e gerador de palavras')?></a>
                        </div>

                        <div class="mt-3">
                            <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" onchange="$('#wordTable').toggleClass('listaLonga');">
                            <span class="form-check-label"><?=_t('Página curta')?></span>
                            </label>
                        </div>


                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Palavras')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                        <a href="index.php?page=editword&iid=<?=$id_idioma?>" class="btn btn-primary d-none d-sm-inline-block">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Nova palavra')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div class="card-bodyx">
						<div class="list-group list-group-flush overflow-auto" id="wordTable">

						</div>
                    </div>
                </div>
            </div>



            </div>
          </div>
        </div>
        <style>.listaLonga{max-height: 35rem}</style>

<script>
/*
function editWord(pid){
    window.location = "index.php?page=editword&iid=<?=$id_idioma?>&pid="+pid;
	//$("#pmb").load("api.php?action=getWordEdit&pid="+pid+"&iid=<?=$id_idioma?>");
	//$("#modalPalavra").modal('show');
}
*/

var filter1 = 'rom';
var filter2 = 'ni';

function novaImportacao(){
	$("#caixaTexto").show();
};

function delWord(pid,dic){ 

	let text = '<?=_t('Apagar esta palavra e todas as suas formas e outras palavras que a têm como forma de dicionário?')?>';//'Apagar esta palavra e todas as suas formas e outras palavras que a têm como forma de dicionário?';

	if (dic>0){
		text = '<?=_t('Apagando esta forma da palavra')?>'; //'Apagando esta forma da palavra?';
	}

    if (confirm(text)) $.get("api.php?action=ajaxApagarPalavras&pid="+pid, function (data){
                    if ($.trim(data)=='1'){
						window.location.reload(true); //href="index.php?page=editlexicon&iid=<?=$id_idioma?>";
                    }else alert(data);
                });
	
};

function pularPara(index = ''){
	$("#tabelaPalavras").DataTable().column(0).search(index).draw(); //row( 30 ).scrollTo(false);
};

function loadIndice(index = 0){
	$("#listaDeCaracteres").html('<div class="loaderSpin"></div>');
	$("#listaDeCaracteres").load("api.php?action=listarIndiceCaracteres&iid=<?=$id_idioma?>&o="+$("#ordem").val()+"&to="+$("#tipo_ordem").val());
};

function listFormat(json){
    let html = "";
    data = JSON.parse(json);
    $.each( data, function( key, val ) {
        if (val.inicial) html += `<div class="list-group-header sticky-top">`+val.inicial+`</div>`;

        let palavra, sub = ''; // depende da lingua carregada, a função já pega pronuncia, romanizacao ou nativo (com span custom-font)

        <?php 
        if ($idioma['romanizacao']==1) { ?>

            /*
            if ($nativo=="<span class='custom-font-".$escrita."' ></span>") {
                if($r['romanizacao']!='') $nativo = "<span class='text-secondary' >".$r['romanizacao']."</span> ";
                else $nativo = "<span class='text-secondary' >/".$r['pronuncia']."/</span> ";
            }
            */

            sub = val.romanizacao + ' /' + val.pronuncia + '/';
            if (val.nativo.length > 0) {
                palavra = val.nativo;
            }else if (val.romanizacao.length > 0) palavra = val.romanizacao;
            else palavra = val.pronuncia;
        <?php } else { ?>

            // if ($nativo=="<span class='custom-font-".$escrita."' ></span>") $nativo = "<span class='text-secondary' >/".$r['pronuncia']."/</span> ";

            sub = '/' + val.pronuncia + '/';
            if (val.nativo.length > 0) {
                palavra = val.nativo;
            }else palavra = val.pronuncia;
        <?php }; ?>

        html += `<div data-search="` + val.search +
            `" class="list-group-item divWord" ` + val.indexdata + `><div class="row">
              <div class="col-auto" data-bs-toggle="tooltip" title="`+sub+`">
                <a href="?page=editword&iid=<?=$id_idioma?>&pid=`+val.id+`" >` + palavra + ` </a>
              </div>
              <div class="col text-truncate">
                <a href="?page=editword&iid=<?=$id_idioma?>&pid=`+val.id+`" class="text-body d-block">` + val.significado + `</a>
                <div class="text-secondary text-truncate">` + val.classe + ( val.rels > 0 ? ' - <?=_t('Formas')?>: '+val.rels : '' ) + `</div>
              </div>
              <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delWord(\'`+val.id+`\',\'` + val.id_forma_dicionario + `\')">Del</a></div>
          </div></div>`;
    });
    return html;
};

function loadPalavras(index = 0, forceReload = false){ 
    $("#testFilter").val('');
    $("#filtro").val('dici');
    //updateTablerSelect('filtro', 'dici');
    $("#filter-div").show();
    
    $.get("api.php?action=getLastChange&data=lexicon&iid=<?=$id_idioma?>", function (data){
        if (forceReload || data > localStorage.getItem("k_lexicon_<?=$id_idioma?>_updated")){
            console.log('local lexicon outdated > update');
            $.get("api.php?action=listWords&id=<?=$id_idioma?>&t=dici&o="+$("#ordem").val()+"&to="+$("#tipo_ordem").val()+"&i="+index, function (lex){
                $("#wordTable").html(listFormat(lex));
                localStorage.setItem("k_lexicon_<?=$id_idioma?>", lex);
                localStorage.setItem("k_lexicon_<?=$id_idioma?>_updated", data);
                $('[data-bs-toggle="tooltip"]').tooltip();
            })
        }else{
            console.log('local lexicon load');
            $("#wordTable").html( listFormat(localStorage.getItem("k_lexicon_<?=$id_idioma?>")) );
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
};

function filtrarPalavras(reset = false){
    if(!reset){
        if($("#filtro").val()>0) testFilter('divWord',"testFilter",$("#filtro").val());
        else testFilter('divWord',"testFilter");
    }else if ($("#filtro").val()=='dici'){
        loadPalavras();
        $("#testFilter").val("");
    }else if($("#filtro").val()=='tudo' || $("#filtro").val()=='cont' || $("#filtro").val()=='morf' || $("#filtro").val()=='expr'){
        $.get("api.php?action=listWords&id=<?=$id_idioma?>&t="+$("#filtro").val()+"&o="+$("#ordem").val()+"&to="+$("#tipo_ordem").val()+"&i=0", function (lex){
            $("#wordTable").html(listFormat(lex));
            $('[data-bs-toggle="tooltip"]').tooltip();
        })
        $("#testFilter").val("");
    }else if($("#filtro").val()>0){
        //loadPalavras();
        //$(".divWord:not .k"+$("#filtro").val()).remove();
        testFilter('divWord',"testFilter",$("#filtro").val());
    }else{
        $("#wordTable").load("api.php?action=listWords&id=<?=$id_idioma?>&t="+$("#filtro").val()+"&o="+$("#ordem").val()+"&to="+$("#tipo_ordem").val()+"&i=0");
        $("#testFilter").val("");
    };
}

function loadEspecial(tipo){
    $("#testFilter").val('');
    $("#filter-div").hide();
	$("#wordTable").html('<div class="loaderSpin"></div>');
	$("#wordTable").load("api.php?action=listWordsSpecial&id=<?=$id_idioma?>&t="+$("#filtro").val()+"&o="+$("#ordem").val()+"&to="+$("#tipo_ordem").val()+"&tipo="+tipo);
};

function reloadIndice(){
	filter1 =  $("#ordem").val();
	loadIndice( $("#ordem").val() );
}

function setFilter2(){
	filter12=  $("#tipo_ordem").val();
	loadIndice( $("#ordem").val() );
}

$(document).ready(function(){
    loadPalavras();
    //createTablerSelect('ordem');
    //createTablerSelect('filtro');
}); 
/*
document.onkeyup = function () {
  var e = e || window.event; // for IE to cover IEs window event-object
  if(e.shiftKey && e.key == "A" && $('.modal.in').length == 0) {
    editWord();
    return false;
  }
}*/

//formatarTablerSelect('ordem');
//formatarTablerSelect('filtro');

</script>