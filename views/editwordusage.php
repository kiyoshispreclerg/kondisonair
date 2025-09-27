
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];

$idioma = array();   
$sql = "SELECT i.*,
    (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    FROM idiomas i
    WHERE i.id = ".$id_idioma.";";
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

        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlexicon&iid=<?=$id_idioma?>"><?=_t('Léxico')?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Níveis de uso')?></a></li>
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
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Níveis de uso')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                        <a href="#" class="btn btn-primary d-none d-sm-inline-block" onclick="novoNivel(0)">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Novo')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div id="listaNiveis" class="list-group list-group-flush overflow-auto" style="max-height: 35rem"> </div>  
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Detalhes')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                    <a id="saveBtn" style="display:none" onclick="execSalvarNivel()" class="btn btn-primary d-none d-sm-inline-block">
                                        <?=_t('Salvar')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="idNivel" value="0">
                        <div class="mb-3">
                                <label class="control-label"><?=_t('Nome')?></label>
                                <input type="text" class="form-control" id="nome" 
                                onchange="salvarNivel()" placeholder="<?=_t('Ex.: Desuso')?>">
                        </div>
                        <div class="mb-3">
                                <label class="control-label"><?=_t('Descrição')?></label>
                                <textarea class="form-control" id="descricao" rows="5"
                                        onchange="salvarNivel()">"></textarea>
                        </div>

                        </div>
                    </div> 
                </div>
            </div>


            </div>
          </div>
        </div>




<script> 

function execSalvarNivel(){
    $.post("api.php?action=ajaxGravarNivel"
        +"&nid="+ $('#idNivel').val()+"&iid=<?=$id_idioma?>", 
    { titulo:$('#nome').val(),
        descricao:$('#descricao').val()
    }, function (data){
        if ($.trim(data) > 0){
            $('#idNivel').val($.trim(data));
            $("#listaNiveis").load("api.php?action=listarNiveis&iid=<?=$id_idioma?>");
            $('#saveBtn').hide();
        }else{
            alert(data);
        };
    });
}
function salvarNivel(){
    $('#saveBtn').show();
}; 

function abrirNivel(id = 0, nome = '', descricao = ''){
    $('#saveBtn').hide();
	$(".divN").removeClass("card card-active bg-primary-lt");        
	$("#row_"+id).addClass("card card-active bg-primary-lt");
    $('#idNivel').val(id); 
    $("#opcaoEdit").hide();
    $('#nome').val(nome); 
    $('#descricao').val(descricao); 
};

function apagarNivel(id){
    $.get("api.php?action=ajaxApagarNivel&id="+id, function (data){
        if ($.trim(data)=='ok'){
            novoNivel();
        }else if ($.trim(data)>0){
            if (confirm("<?=_t('Apagar este nível? As palavras deste nível ficarão sem nível determinado.')?>")){
                $.get("api.php?action=ajaxApagarNivel&id="+id+'&unsetWords=1', function (data){
                    if ($.trim(data)=='ok'){
                        novoNivel();
                    }else alert(data);
                });
            }
        }else alert(data);
    });
};

$(document).ready(function(){
    novoNivel();
}); 

function novoNivel(){
    $('#saveBtn').hide();
    $('#idNivel').val(0);
    $('#nome').val(''); 
    $('#descricao').val('');
    $("#listaNiveis").load("api.php?action=listarNiveis&iid=<?=$id_idioma?>");
};


</script>