
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];
$idioma = array();   
$result = mysqli_query($GLOBALS['dblink'],"SELECT *,
(SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i
               WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};

if ($idioma['nome_legivel']=='' || ($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 )) {
    echo '<script>window.location = "dash.php";</script>';
		exit;
}

?>
<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Classes de palavras')?></a></li>
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
                      <h3 class="card-title"><?=_t('Classes')?></h3>
                      <div class="card-actions">
                        <a onclick="novaPalavra()" class="btn btn-primary d-none d-sm-inline-block">
                          <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                          <?=_t('Nova classe')?>
                        </a>
                      </div>
                    </div>
                  <div class="card-bodyx">
                    <div class="list-group list-group-flush overflow-auto" id="partsTable" style="max-height: 35rem">

                    </div>
                  </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                  <div class="card-header">
                      <h3 class="card-title"><?=_t('Informações')?></h3>
                      <div class="card-actions">
                        <a id="btnSalvar" onclick="gravarPalavra()" class="btn btn-primary ">
                          Salvar
                        </a>
                      </div>
                  </div>
                  <div class="card-body">

                      <div class="mb-3">
                        <label class="form-label"><?=_t('Nome')?></label>
                        <input type="text" class="form-control" id="nome" 
                          onchange="showGravarPalavra()" placeholder="<?=_t('Ex.: Verbo')?>">
                      </div>

                      <div class="mb-3" >
                          <label class="form-label"><?=_t('Gloss')?></label>
                          <select id="gloss" onchange="showGravarPalavra()" type="text" class="form-select" value="">
                              <option value="0" selected><?=_t('Não especificado')?></option>
                              <?php 
                              $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses ;") or die(mysqli_error($GLOBALS['dblink'])); // WHERE tipo = 'k'
                              while ($lang = mysqli_fetch_assoc($langs)){
                                  echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'"';
                                  //if ($idioma['id_classe'] == $lang['id']) echo ' selected';
                                  echo '>'.$lang['gloss'].' - '.$lang['descricao'].'</option>';
                              }
                              ?>
                          </select>
                      </div>
                      
                      <div class="row mb-3" >
                          <div class="col-4" >
                              <label class="form-label"><?=_t('Tipo semântico')?></label>
                              <select id="proto_tipo" class="form-select " onchange="showGravarPalavra()" >
                                  <option value="0" selected><?=_t('Nenhuma')?></option>
                                  <option value="1" selected><?=_t('Ações/Verbos')?></option>
                                  <option value="2" selected><?=_t('Estados/Substantivos')?></option>
                              </select>
                          </div>
                          
                          <div class="col-4" >
                              <label class="form-label"><?=_t('Classe pai')?></label>
                              <select id="superior" onchange="showGravarPalavra()" type="text" class="form-select" value="">
                                  <option value="0" selected><?=_t('Nenhuma')?></option>
                                  <?php 
                                  $langs = mysqli_query($GLOBALS['dblink'],"SELECT c.*, g.gloss FROM classes c 
                                      LEFT JOIN glosses g ON g.id = c.id_gloss WHERE c.id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
                                  while ($lang = mysqli_fetch_assoc($langs)){
                                      echo '<option value="'.$lang['id'].'"';
                                      //if ($idioma['id_classe'] == $lang['id']) echo ' selected';
                                      echo '>'.$lang['gloss'].' - '.$lang['nome'].'</option>';
                                  }
                                  ?>
                              </select>
                          </div>
                          <div class="col-4" >
                              <label class="form-label"><?=_t('Palavras do paradigma')?></label>
                              <select id="paradigma" class="form-select " onchange="showGravarPalavra()" >
                                  <option value="0" selected><?=_t('Derivadas')?></option>
                                  <option value="1" selected><?=_t('Únicas')?></option>
                              </select>
                          </div>

                      </div>
                      <div class="mb-3" >
                          <label class="form-label"><?=_t('Descrição')?></label>
                          <textarea class="form-control" id="descricao" rows="5"
                              onchange="showGravarPalavra()"  placeholder="<?=_t('Ex.: Palavras que indicam ação ou estado')?>"></textarea>
                      </div>

                      <div class="col-sm-12">
                          <div class="form-group"  id="btnMais">
                          </div>
                      </div>












                    
                  </div>
                </div>
            </div>


            </div>
          </div>
        </div>


<script>
function gravarPalavra(){
    if ($('#nome').val()=='') return;
    if ($('#gloss').val()=='') return;

    $.post("?action=ajaxGravarClasse"
        +"&cid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>", 
    { nome:$('#nome').val(),
    gloss:$('#gloss').val(),
    proto_tipo:$('#proto_tipo').val(),
    paradigma:$('#paradigma').val(),
    superior:$('#superior').val(),
    descricao:$('#descricao').val()
    }, function (data){
        if ($.trim(data) > 0){
            $('#idPalavra').val($.trim(data));
            $("#partsTable").load("?action=listParts&iid=<?=$id_idioma?>");
        }else{
            alert(data);
        };
    });
}; 

function abrirPalavra(pid){
	$(".list-group-item").removeClass("card card-active bg-primary-lt");        
	$("#row_"+pid).addClass("card card-active bg-primary-lt");
    $('#idPalavra').val(pid); 
    $.getJSON( "?action=getDetalhesClasse&cid=" +pid , function(data){ 
    $.each( data, function( key, val ) {
        $('#nome').val(data[0].nome); 
        $('#descricao').val(data[0].descricao); 
        $('#gloss').val(data[0].id_gloss); 
        updateTablerSelect('gloss',data[0].id_gloss);
        $('#proto_tipo').val(data[0].proto_tipo); 
        $('#paradigma').val(data[0].paradigma); 
        $('#superior').val(data[0].superior); 
        updateTablerSelect('superior',data[0].superior);
        if(data[0].superior==0)
            $('#btnMais').html(`<a class="btn btn-primary" href="?page=editinflections&iid=<?=$id_idioma?>&k=`+pid+`"><?=_t('Editar formas/flexões')?></a> <a class="btn btn-primary" title="<?=_t('Ajuda a separar diferentes padrões para a mesma tabela de declensões')?>" href="?page=editgenders&iid=<?=$id_idioma?>&k=`+pid+`"><?=_t('Editar classes/gêneros')?></a>`);
        else
            $('#btnMais').html(`<a class="btn btn-primary" href="?page=editforms&iid=<?=$id_idioma?>&k=`+data[0].superior+`"><?=_t('Editar formas/flexões')?></a>`);

            $('#btnSalvar').hide();
    }); 

});
};

function showGravarPalavra(){
    $('#btnSalvar').show(); 
};

function novaPalavra(){
	//$("#tabelaPalavras tbody tr").removeClass("card card-active bg-primary-lt");   
    $('#idPalavra').val(0); 
    $('#btnMais').html('');
    $('#nome').val(''); 
    $('#descricao').val(''); 

    $('#gloss').val(''); 
    updateTablerSelect('gloss','');
    
    $('#proto_tipo').val(0); 
    $('#paradigma').val(0); 

    $('#superior').val(0); 
    updateTablerSelect('superior',0);

    $('#btnSalvar').hide(); 
    $("#partsTable").load("?action=listParts&iid=<?=$id_idioma?>");
};

function delPart(pid){ 
  if (confirm("<?=_t('Apagar esta classe de palavras?')?>"))
    $.get("?action=ajaxDelClasse&id="+pid, function (data){
        if ($.trim(data) == 'ok'){
            $("#partsTable").load("?action=listParts&iid=<?=$id_idioma?>");
        }else{
            alert(data);
        };
    });
};

$(document).ready(function(){
    //$("#partsTable").load("?action=listParts&iid=<?=$id_idioma?>");
    novaPalavra();
}); 
formatarTablerSelect('gloss');
formatarTablerSelect('superior');
</script>
