
<?php 
	$id_idioma = $_GET['iid'] ?: 0;
	$id_usuario = $_GET['uid'] ?: 0;

	$idioma = array();   
	$result = mysqli_query($GLOBALS['dblink'],"SELECT *, (SELECT nome_legivel FROM idiomas d WHERE d.id = i.id_idioma_descricao LIMIT 1) as desc_idioma,
            (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = '".$_SESSION['KondisonairUzatorIDX']."' LIMIT 1) as collab FROM idiomas i
						WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
	    $idioma  = $r;
	};

	$usuario = array();
	$result = mysqli_query($GLOBALS['dblink'],"SELECT * FROM usuarios
						WHERE id = '".$id_usuario."';") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
	    $usuario  = $r;
	};

	if ($_SESSION['KondisonairUzatorIDX'] > 0 && ($idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX'] || $idioma['collab'] > 0 ) ) {
		// listagem top frases do idioma - link pra edição
        $breadcrumb = '<li class="breadcrumb-item"><a href="?page=editlanguage&iid='.$id_idioma.'">'.$idioma['nome_legivel'].'</a></li>';
        $htmlItem = '`<div data-search="` + val.search +
            `" class="list-group-item divWord"><div class="row">
              <div class="col-auto" data-bs-toggle="tooltip">
                <a href="?page=phrase&iid='.$id_idioma.'&id=`+val.id+`" >` + val.frase + ` </a>
              </div>
              <!--div class="col text-truncate">
                <a href="?page=phrase&iid='.$id_idioma.'&id=`+val.id+`" class="text-body d-block">` + val.original + `</a>
                <div class="text-secondary text-truncate">` + ( val.idioma_original ? val.idioma_original : \'\' ) + `</div>
              </div-->
              <div class="col-auto"><a href="?page=editphrase&iid=`+val.id_idioma+`&id=`+val.id+`" >Editar</a></div>
          </div></div>`';
	}else if($id_idioma > 0){
        // apenas listagem de top frases do idioma
        $breadcrumb = '<li class="breadcrumb-item"><a href="?page=language&iid='.$id_idioma.'">'.$idioma['nome_legivel'].'</a></li>';
        $htmlItem = '`<div data-search="` + val.search +
            `" class="list-group-item divWord"><div class="row">
              <div class="col-auto" data-bs-toggle="tooltip">
                <a href="?page=phrase&iid='.$id_idioma.'&id=`+val.id+`" >` + val.frase + ` </a>
              </div>
          </div></div>`';
    }else if($id_usuario > 0){
        // apenas listagem de frases do usuario - edit se é o logado
        //$breadcrumb = '<li class="breadcrumb-item"><a href="?page=user&uid='.$id_usuario.'">'.$usuario['nome'].'</a></li>';

        if ($id_usuario == $_SESSION['KondisonairUzatorIDX']) // minhas frases
            $htmlItem = '`<div data-search="` + val.search +
                `" class="list-group-item divWord"><div class="row">
                <div class="col-auto" data-bs-toggle="tooltip">
                    <a href="?page=phrase&iid=`+val.id_idioma+`&id=`+val.id+`" >` + val.frase + ` </a>
                </div>
                <div class="col-auto"><a href="?page=editphrase&iid=`+val.id_idioma+`&id=`+val.id+`" >Editar</a></div>
                <div class="col-auto"><a class="btn btn-sm btn-danger" onclick="delWord(\'`+val.id+`\')">Del</a></div>
            </div></div>`';
        else //frases de algum usuario ae
            $htmlItem = '`<div data-search="` + val.search +
                `" class="list-group-item divWord"><div class="row">
                <div class="col-auto" data-bs-toggle="tooltip">
                    <a href="?page=phrase&iid=`+val.id_idioma+`&id=`+val.id+`" >` + val.frase + ` </a>
                </div>
            </div></div>`';

    }else {
        //lista random top frases
        //add select pra idiomas
        $htmlItem = '`<div data-search="` + val.search +
            `" class="list-group-item divWord"><div class="row">
              <div class="col-auto" data-bs-toggle="tooltip">
                <a href="?page=phrase&iid=`+val.id_idioma+`&id=`+val.id+`" >` + val.frase + ` </a>
              </div>
              <!--div class="col text-truncate">
                <a href="?page=phrase&iid=`+val.id_idioma+`&id=`+val.id+`" class="text-body d-block">` + val.original + `</a>
                <div class="text-secondary text-truncate">` + ( val.idioma_original ? val.idioma_original : \'\' ) + `</div>
              </div-->
          </div></div>`';
    }

?>

        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li><?=$breadcrumb?>
                      <li class="breadcrumb-item active"><a><?=_t('Frases')?></a></li>
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

                        <div class="mt-3">
                            <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" onchange="$('#wordTable').toggleClass('listaLonga');">
                            <span class="form-check-label"><?=_t('Página curta')?></span>
                            </label>
                        </div>

						<div class="mt-5">
							<?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>
                                <a class="btn btn-primary w-100 mt-2" href="?page=phrases&uid=<?=$_SESSION['KondisonairUzatorIDX']?>"><?=_t('Minhas frases')?></a>
                            <?php }; ?>
							<a class="btn btn-primary w-100 mt-2" href="?page=phrases"><?=_t('Todas as frases')?></a>
                        </div>

                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Frases')?></h3>
						<div class="card-actions">
                            <?php if($_SESSION['KondisonairUzatorIDX']>0){?>
                            <div class="row">
                                <div class="col">
                                        <a href="index.php?page=editphrase&iid=<?=$id_idioma?>" class="btn btn-primary d-none d-sm-inline-block">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Nova frase')?>
                                    </a>
                                </div>
                            </div>
                            <?php } ?>
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

        let palavra, sub = '';

        html += <?=$htmlItem?>;
    });
    return html;
};

function loadFrases(){ 
    $("#testFilter").val('');
    $("#filtro").val('dici');
    //updateTablerSelect('filtro', 'dici');
    $("#filter-div").show();

    $.get("api.php?action=listPhrases&iid=<?=$id_idioma?>&uid=<?=$id_usuario?>", function (lex){
        $("#wordTable").html(listFormat(lex));
        $('[data-bs-toggle="tooltip"]').tooltip();
    })
};

$(document).ready(function(){
    loadFrases();
}); 

</script>