


<?php 

 // <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
 
 	$romanizacao = 0;
	$isOwner = false;
	$fullartigo = '';

	$sql = "SELECT t.*, e.id as eid, e.id_fonte, e.tamanho,
		(SELECT nome_legivel FROM idiomas WHERE id = t.id_idioma) as idioma,
		(SELECT checar_sons FROM idiomas WHERE id = t.id_idioma) as checar_sons,
		(SELECT romanizacao FROM idiomas WHERE id = t.id_idioma) as romanizacao,
		(SELECT id_artyg FROM artyg_dest WHERE id_dest = t.id AND tipo_dest = 'text' LIMIT 1) as artigo_ligado,
		( SELECT texto FROM artygs WHERE id = (SELECT id_artyg FROM artyg_dest WHERE id_dest = t.id AND tipo_dest = 'text' LIMIT 1) LIMIT 1) as artigo_texto,
		(SELECT id_usuario FROM idiomas WHERE id = t.id_idioma) as luid,
		(SELECT id FROM collabs WHERE id_idioma = t.id_idioma AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as lcid
		FROM studason_tests t
		LEFT JOIN escritas e  ON e.id_idioma = t.id_idioma
		WHERE  t.id = ".$_GET['id']."  ORDER BY e.padrao DESC;";
		
	$langs = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));


	$e = mysqli_fetch_assoc($langs);  
	$checar_sons = $e['checar_sons'];
	$id_idioma = $e['id_idioma'];
	$idioma = $e['idioma'];
	$titulo = $e['titulo'];
	$romanizacao = $e['romanizacao'];
	$link_audio = $e['link_audio'];

	$fonte = $e['id_fonte'];
	$tamanho = $e['tamanho'];

	$romanizacao = $e['romanizacao'];
	
	// if ($fonte < 0) die("Ainda não suporta escritas personalizadas, apenas por fontes tipográficas.");

	$texto = '';
	$id_texto = $_GET['id'];
	if ($e['eid']>0) $eid = $e['eid'];
	if ($e['luid'] == $_SESSION['KondisonairUzatorIDX'] || $e['lcid'] > 0) $isOwner = true;

	/*
	$langs = mysqli_query($GLOBALS['dblink'],"SELECT e.*, f.arquivo as fonte FROM escritas e 
		LEFT JOIN fontes f ON f.id = e.id_fonte
		WHERE id_idioma = ".$id_idioma." ORDER BY e.padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));
		*/

	$separador = ' '; // get pelo idioma

	//$stats = 'X novas (x%)  status: total palavras, total ok, total novas, total aprendendo, porcentagens  audio/video player';

   	// // 9: não existe no kondisonair ainda ->  0- nova no aprendizado -> 1~4 aprendendo -> 5 aprendida/ignorada/ok


	//xxxxx se não owner, add em textos estudando
	if (!$isOwner){
		$tos = mysqli_query($GLOBALS['dblink'],"SELECT i.* FROM tests_importasons i
			LEFT JOIN studason_tests s ON i.id_texto = s.id
			WHERE i.id_usuario = ".$_SESSION['KondisonairUzatorIDX']." AND i.id_texto = ".$id_texto.";") or die(mysqli_error($GLOBALS['dblink']));
		if (mysqli_num_rows($tos)<1){
			mysqli_query($GLOBALS['dblink'],"INSERT INTO tests_importasons SET
			id_texto = ".$id_texto.", 
			id_usuario = ".$_SESSION['KondisonairUzatorIDX']) or die(mysqli_error($GLOBALS['dblink']));
		}
	}



?>
<style>
	<?php if($_SESSION['KondisonairUzatorIDX']>0){ 
		$opacidade = '0.8';	
	?>

	.pstat-0{
		--tblr-bg-opacity: <?=$opacidade?>;
  		background-color: rgba(var(--tblr-orange-rgb), var(--tblr-bg-opacity)) !important;
	}
	.pstat-1, .pstat-2{
		--tblr-bg-opacity: <?=$opacidade?>;
  		background-color: rgba(var(--tblr-yellow-rgb), var(--tblr-bg-opacity)) !important;
	}
	.pstat-3, .pstat-4{
		--tblr-bg-opacity: <?=$opacidade?>;
		background-color: rgba(var(--tblr-indigo-rgb), var(--tblr-bg-opacity)) !important; /* background-color: yellow;*/
	}
	.pstat-5{
		background-color: unset;
	}
	.pstat-9{
		--tblr-bg-opacity: <?=$opacidade?>;
		background-color: rgba(var(--tblr-red-rgb), var(--tblr-bg-opacity)) !important;
	}
	<?php } ?>
	.unmarked {
		background-color: unset !important;
	}
	.nostud {
		margin: 2px 0;
		border-radius: 3px;
		border: 1px solid transparent;
	}
	.pstud {
		margin: 2px 0;
		border-radius: 3px;
		border: 1px solid transparent;
		transition: all 0.15s ease-in-out;
	}
	.pstud:hover {
		border: 1px solid ;
		border-color: var(--tblr-card-color);
		cursor:pointer;
		/*transform: scale(1.06);*/
	}
	.lightSquare {
		padding: 3px;
		border-color: var(--tblr-border-color);
	}
	.panelpal {
		/*border-radius: 10px;*/
		/*background-color: black;*/
		padding: 8px;
		margin-bottom: 4px;
	}
	.panelpal div {
		margin-bottom: 0px !important;
	}
	.badge {
		cursor:pointer;
	}
	.sGl {
		font-family: 'sans';
		font-size: small; /*8px;*/
		display:none;
	}
	.palSelected {
		border: 1px solid;
		border-color: var(--tblr-card-color);
	}
</style>
<input type="hidden" id="curText" value="<?=$id_texto?>" />
<input type="hidden" id="curAid" value="0" />
<input type="hidden" id="curPal" value="" />
<input type="hidden" id="idPalavra" value="0" />


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
					<ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=<?=$isOwner?'edit':''?>language&iid=<?=$id_idioma?>"><?=$idioma?></a></li>
                      <li class="breadcrumb-item"><a href="?page=texts&iid=<?=$id_idioma?>"><?=_t('Textos')?></a></li>
                      <li class="breadcrumb-item active"><a><?=$titulo?></a></li>
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



            <div class="col-9">
                <div class="card mb-3">
                  <div class="card-header">
                    <h4 class="card-title"><?=_t('Texto')?></h4>
					<div class="card-actions">
						<?php if ($_SESSION['KondisonairUzatorIDX'] > 0) { ?>
                          <a class="btn btn-primary" onClick="hideCl()" id="btnHideCl"><?=_t('Ocultar cores')?></a>
                          <a class="btn btn-primary" style="display:none" onClick="showCl()" id="btnShowCl"><?=_t('Mostrar cores')?></a>
						  <?php } ?>
                          <a class="btn btn-primary" style="display:none" onClick="hideGl()" id="btnHideGl"><?=_t('Ocultar detalhes')?></a>
                          <a class="btn btn-primary" onClick="showGl()" id="btnShowGl"><?=_t('Mostrar detalhes')?></a>
					</div>
                  </div>
                  <div class="card-body">
                    <div class="col-sm-12 custom-font-<?=$eid?>" style="white-space:preserve;" id="textoMarcado"></div> 
                  </div>
                </div>

				<?php if($isOwner){ ?>
                <div class="card sticky-top">
                  <div class="card-body">
				  		<label class="form-label"><?=_t('Artigo vinculado')?> <a class="btn btn-sm btn-primary" onclick="abrirArtigoSel()"><?=_t('Ver artigo')?></a></label>
						<select id="artigo" class="form-select" onchange="updateArtVinculado()">
							<option value="0" selected><?=_t('Nenhum')?></option>
							<?php 
								$langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM artygs WHERE id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
								while ($lang = mysqli_fetch_assoc($langs)){
									echo '<option value="'.$lang['id'].'" ';
									if ($idartigo == $lang['id']) echo ' selected';
									echo ' >'.$lang['nome'].'</option>';
								}
							?>

						</select>
					
                    <div class="col-sm-12 custom-font-<?=$eid?>" style="white-space:preserve;" id="textoMarcado"></div> 
					
                  </div>
                </div>
				<?php }else if($fullartigo != ''){ ?>
				<div class="card">
                  <div class="card-body">
                    <div style="overflow-y:scroll;max-height:35rem;white-space:preserve;"><?=$fullartigo?></div> 
					
                  </div>
                </div>
				<?php } ?>
            </div>

            <div class="col-3">

                <div class="card sticky-top">
                  <div class="card-header">
                    <div class="col-12">
                      <div class="col-12">
                        <div class="form-group" >
                          <span class="control-label" id="tstats"><?=$stats?></span>&nbsp;
                          <!--a class="btn btn-sm btn-info btn-rounded" onClick=" ">Conheço todas</a-->
                        </div>
                      </div>
                      <?php 		
                      if($link_audio!=''){
                        echo '<div class="col-12">
                          <audio controls="controls" id="audio_player" style="width: 100%;">
                          <source src="audio/'.$id_idioma.'/'.$link_audio.'" type="audio/ogg" />
                          Your browser does not support the audio element.
                          </audio>
                        </div>';
                      };?>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="col-12"  style="overflow-y: scroll; max-height: 35rem" id="painelStud"> </div> 
                  </div>
                  

                </div>
            </div>


            </div>
          </div>
        </div>











<script>

/*
window.onscroll = function() {autoScrollPanel()};

function autoScrollPanel(){
	var pos = document.documentElement.scrollTop;
	var el = document.getElementById('painelStud').style.top;
	var tamTop = 130;

	var elb = document.getElementById('painelStud').offsetHeight;

	if (pos < tamTop) pos = 0;
	else pos = pos - tamTop;

	if ( elb > window.screen.height && pos > 0){
		pos = pos - window.screen.height;
	}
	

	document.getElementById('painelStud').style.top = pos+'px';
}
*/

function showCl(){
	$(".pstud").removeClass('unmarked'); $("#btnHideCl").show(); $("#btnShowCl").hide();
}
function hideCl(){
	$(".pstud").addClass('unmarked'); $("#btnHideCl").hide(); $("#btnShowCl").show();
}

function showGl(){
	$(".sGl").show(); $("#btnHideGl").show(); $("#btnShowGl").hide();
	$(".pstud").addClass('lightSquare');
}
function hideGl(){
	$(".sGl").hide(); $("#btnHideGl").hide(); $("#btnShowGl").show();
	$(".pstud").removeClass('lightSquare');
}

function loadTexto(){
	//$("#textoMarcado").html('<div class="loaderSpin"></div>');
	//$("#textoMarcado").html('<div class="loaderSpin"></div>');
	$.get("api.php?action=getStudTest&id="+$('#curText').val(), function (data){
		$("#textoMarcado").html(data);
	});
};

function cpk(pids = '', st = 9, aid, pal = '', ps = '0',este){

	// unselect all
	// select essa pal
	$(".pstud").removeClass('palSelected');
	if (ps != '0') $(".pstud-"+ps).addClass('palSelected');

	<?php if ($fonte<0){ ?>
	$("#painelStud").html(`<div class="mb-3"><div class="form-group"><div >`+$(este).html()+`</div></div></div>`);
	<?php }else{ ?>
	$("#painelStud").html(`<div class="mb-3"><div class="form-group"><div class="custom-font-<?=$eid?>">`+pal+`</div></div></div>`);
	<?php }; ?>
	
	$("#curAid").val(aid);
	$("#curPal").val(pal);
	//$("#painelStud").html('<div class="loaderSpin"></div>');
	//$("#painelStud").append(`<div class="mb-3"><div class="form-group"><div class="custom-font-<?=$eid?>">`+pal+`</div></div></div>`);

	// add apenas se a lingua for do proprio usuario
	if (<?php if (!$isOwner) echo 'false && '; ?>st == 9) $("#painelStud").append(`<div class=""><div class="form-group"> 
		<a target="_blank" href="?page=editword&new=`+pal+`&iid=<?=$id_idioma?>" class="btn btn-primary"><?=_t('Adicionar ao dicionário')?></a> 
		</div></div>`);
	else{
	
		 // st, apenas se for entre 0 e 5, 9 não existe deve abrir o add palavra
		 <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>

		$("#painelStud").append(`<div class="mb-3"><div class="form-group">
			<span class="badge bg-red`+( st == 0 ? ' text-red-fg' : '-lt' )+`" onClick="updateStAprend(`+$("#curAid").val()+`,0,'`+pids+`',\'`+pal+`\',\'`+ps+`\')"><?=_t('Nova')?></span>
			<span class="badge bg-orange`+( st == 1 ? ' text-orange-fg' : '-lt' )+`" onClick="updateStAprend(`+$("#curAid").val()+`,1,'`+pids+`',\'`+pal+`\',\'`+ps+`\')"><?=_t('Vista')?></span>
			<span class="badge bg-yellow`+( st == 2 ? ' text-yellow-fg' : '-lt' )+`" onClick="updateStAprend(`+$("#curAid").val()+`,2,'`+pids+`',\'`+pal+`\',\'`+ps+`\')"><?=_t('Aprendendo')?></span>
			<span class="badge bg-indigo`+( st == 3 ? ' text-indigo-fg' : '-lt' )+` " onClick="updateStAprend(`+$("#curAid").val()+`,3,'`+pids+`',\'`+pal+`\',\'`+ps+`\')"><?=_t('Conhecida')?></span>
			<span class="badge bg-blue`+( st == 5 ? ' text-blue-fg' : '-lt' )+`" onClick="updateStAprend(`+$("#curAid").val()+`,5,'`+pids+`',\'`+pal+`\',\'`+ps+`\')"><?=_t('Aprendida')?></span>
			</div></div>`);
			<?php } ?>

		// get com tds palavras já
		<?php //if ($isOwner) 
			echo "pids = pids + ',c';"; ?>
		pids.split(',').forEach(function(pid){ 
			$.get("api.php?action=getStudPal&pid="+pid<?php if ($isOwner) echo '+"&edit=1"'; ?>+"&iid=<?=$id_idioma?>&pal="+pal, function (data){
				$("#painelStud").append(data);
			});
		});
		$("#painelStud").append(apend);
	};

};

function updateStAprend(aid,st,pids,pal='',ps='0'){
	
	$.get("api.php?action=studasonPalSalvar&aid="+$("#curAid").val()+"&s="+st+"&pids="+pids, function (data){
		// update na tela a cor dos btn, atraves de mais uma classe
		if ($.trim(data)>0) {
			$("#curAid").val($.trim(data));
			//$(".btnap"+st).addClass("btnapx");
			// tirar das badges qualquer bg-*-lt e text-*-fg, e add bg-*
			loadTexto();
			cpk(pids,st,aid,pal,ps)
		}
	});
}

$(document).ready(function(){
	loadTexto();

    $('[data-toggle="tooltip"]').tooltip(); 

	//document.getElementById('painelStud').style.height = '500px';
	//document.getElementById('textoMarcado').style.height = '500px'; //$(window).height() + 'px'; 
	//document.getElementById('wrapper').style.height = window.screen.height + 'px';
	//novaPalavra();
}); 

function abrirArtigoSel(){
	if($("#artigo").val()>0) window.open("index.php?page=article&iid=<?=$id_idioma?>&id="+$("#artigo").val());
}

<?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>

function novoSignifCom(){ alert('to do: adicionar significado comunidade'); return;
	let pal =  $("#curPal").val();

	$.confirm({
		title: 'Adicionar significado a <div class="custom-font-<?=$eid?>">'+pal+'</div>',
		type: 'green', 
		typeAnimated: true,
		content: '<input type="text" class="form-control" id="signif" placeholder="Novo significado...">'  ,
		containerFluid: true, 
		buttons: {
			"Salvar": function () {
				var idl = this.$content.find('#signif').val();
				if(idl==''){
					$.alert('Vazio!');
					return false;
				};
				$.get("api.php?action=studasonPalSigSalvar&iid=<?=$id_idioma?>&p="+pal+"&s="+idl, function (data){
					loadTexto();
					//load palavra ?
				});
			},
			Cancelar: function () {
					
			} 
		}
	});
}

function sgL(id){
	// like significado de comunidade
	$.get("?action=ajaxJoes&t=sigcom&id="+id+"&l=1",function(data){
        //reload palavra $("#joesDiv"+div).html(data);
		loadTexto();
	})
}

function sgD(id){
	// dislike significado de comunidade
	$.get("?action=ajaxJoes&t=sigcom&id="+id+"&l=-1",function(data){
        //reload palavra $("#joesDiv"+div).html(data);
		loadTexto();
	})
}

<?php } ?>
<?php if($isOwner){ ?>
function updateArtVinculado(){
	var dest = $("#artigo").val();
	$.get("?action=ajaxUpdateLinkArtigo&aid="+dest+"&tipo=text&dest=<?=$id_texto?>",function(data){
        if($.trim(data)=='ok'){}else alert(data);
	})
}
<?php } ?>
</script>

<style>
    .popover {
        max-width: 350px !important;
    }
    .popover-table {
        font-size: 12px;
        margin: 0;
        width: 100%;
    }
    .popover-table td, .popover-table th {
        padding: 2px 5px;
    }
    .pstud {
        cursor: pointer;
    }
</style>