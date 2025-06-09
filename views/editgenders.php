
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];
$id_classe = $_GET['k'];
$id_depende = 0;

$idioma = array();   
$sql = "SELECT i.*, c.nome AS nomeClasse,
    (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    FROM classes c LEFT JOIN idiomas i ON c.id_idioma = i.id 
    WHERE c.id = ".$id_classe.";";
//
$result = mysqli_query($GLOBALS['dblink'],$sql) or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
    $idioma  = $r;
};

$title = '';

if ($idioma['nome_legivel']=='' || ( $idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 ) ) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}

?>
<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />
<input type="hidden" id="idOpcao" value="0" />
<input type="hidden" id="selAlterado" value="0" />




        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editparts&iid=<?=$id_idioma?>"><?=$idioma['nomeClasse']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Gêneros')?></a></li>
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
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Gêneros')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                        <a href="#" class="btn btn-primary d-none d-sm-inline-block" onclick="novaPalavra(0)">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Novo')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div id="listaDePalavras" class="list-group list-group-flush overflow-auto" style="max-height: 35rem"> </div>  
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Detalhes')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                    <a id="saveBtn" style="display:none" onclick="execGuardarPalavra()" class="btn btn-primary d-none d-sm-inline-block">
                                        <?=_t('Salvar')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-3">
                                <label class="control-label"><?=_t('Nome')?></label>
                                <input type="text" class="form-control" id="nome" 
                                onchange="gravarPalavra()" placeholder="<?=_t('Palavra no próprio idioma')?>">
                        </div>

                        <div class="mb-3">
                                <label class="control-label"><?=_t('Gloss')?></label>
                                    <select id="gloss" class="form-select" onchange="gravarPalavra()" >
                                        <option value="0" selected><?=_t('Não especificado')?></option>
                                        <?php 
                                        $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses ;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($l = mysqli_fetch_assoc($langs)){
                                            echo '<option value="'.$l['id'].'" title="'.$l['descricao'].'"';
                                            //if ($idioma['id_classe'] == $l['id']) echo ' selected';
                                            echo '>'.$l['gloss'].' - '.$l['descricao'].'</option>';
                                        }
                                        ?>
                                    </select>
                        </div>
                            <!--div class="mb-3">
                                    <label class="control-label">Obrigatório</label>
                                    <select id="obrigatorio" class="form-select " onchange="gravarPalavra()" >
                                        <option value="0" title="As palavras podem ser de um ou outro tipo, ou nenhum">Não</option>
                                        <option value="1" title="As palavras são obrigatoriamente de um ou outro tipo">Sim</option>
                                    </select>
                            </div-->

                        <div class="mb-3">
                                <label class="control-label"><?=_t('Descrição')?></label>
                                <textarea class="form-control" id="descricao" rows="5"
                                        onchange="gravarPalavra()"  placeholder="<?=_t('Palavra no próprio idioma')?>"></textarea>
                        </div>

                        </div>









                    </div> 
                </div>
            </div>


            </div>
          </div>
        </div>




<script> 

function execGuardarPalavra(){
    $.post("api.php?action=ajaxGravarGenero"
        +"&cid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>&k=<?=$id_classe?>", 
    { nome:$('#nome').val(),
        gloss:$('#gloss').val(),
        descricao:$('#descricao').val(),
        obrigatorio:0
    }, function (data){
        if ($.trim(data) > 0){
            $('#idPalavra').val($.trim(data));
            $('#selAlterado').val('0');
            $("#listaDePalavras").load("api.php?action=listarGeneros&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>");
            $('#saveBtn').hide();
        }else{
            alert(data);
        };
    });
}
function gravarPalavra(){
    // depende = 0 apenas para classes
    // se é subopções, depende = id da opcao superior
    
    $('#saveBtn').show();
}; 

function abrirPalavra(pid){
    $('#saveBtn').hide();
	$(".divWord").removeClass("card card-active bg-primary-lt");        
	$("#row_"+pid).addClass("card card-active bg-primary-lt");
    $('#idPalavra').val(pid); 
    $('#selAlterado').val('1');
    $('#idOpcao').val(0);
    $("#opcaoEdit").hide();
    $.getJSON( "api.php?action=getDetalhesGenero&k=" +pid , function(data){ 
        $.each( data, function( key, val ) {
            $('#nome').val(data[0].nome); 
            $('#descricao').val(data[0].descricao); 
            $('#obrigatorio').val(0); 
            updateTablerSelect('gloss',data[0].id_gloss);//$('#gloss').val(data[0].id_gloss); 
            
            //$(".chosen-select").chosen();
        }); 

    });
};

function apagarGenero(pid){
    if (confirm("<?=_t('Apagar este gênero?')?>"))
        $.get("api.php?action=ajaxApagarGenero&id="+pid, function (data){
                    if ($.trim(data)=='1'){
						novaPalavra();
                    }else alert(data);
                });
};

$(document).ready(function(){
    createTablerSelect('gloss');
    novaPalavra();
}); 

function novaPalavra(){
    $('#saveBtn').hide();
    $('#idPalavra').val(0); 
    $('#selAlterado').val('0');
    $('#obrigatorio').val(0); 
    $('#idOpcao').val(0);
    $("#opcaoEdit").hide();
    //$('#listaOpcoes').html('Nada selecionado');
    $('#nome').val(''); 
    $('#descricao').val(''); 
    updateTablerSelect('gloss','0');//$('#gloss').val(''); 
    //$(".chosen-select").chosen();
    $("#listaDePalavras").load("api.php?action=listarGeneros&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>");
};


</script>