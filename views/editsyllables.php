<!-- PANEL START -->
<?php 
    $id_idioma = $_GET['iid'];
    $idioma = array();   
    $result = mysqli_query($GLOBALS['dblink'],"SELECT *,
    (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i
                WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
                // (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    while($r = mysqli_fetch_assoc($result)) { 
        $idioma  = $r;
    };

    if ($idioma['nome_legivel']=='' || ($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 )) {
        echo '<script>window.location = "index.php";</script>';
		exit;
    }

?>

<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />
<input type="hidden" id="tmpx" value="0" />
<input type="hidden" id="tmpy" value="0" />
<input type="hidden" id="tmpz" value="0" />

<style>

	.panelpal {
		/*border-radius: 10px;*/
		/*background-color: black;*/
		padding: 8px;
		margin-bottom: 4px;
	}
	.panelpal div {
		margin-bottom: 0px !important;
	}
    
    /*
    .catSons {
        display:none;
    }
    */
    
</style>


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Sílabas')?></a></li>
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
                            <h3 class="card-title"><?=_t('Categorias')?></h3>
                            <div class="card-actions">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" checked onchange="$('.catSons').toggle()">
                                    <span class="form-check-label"><?=_t('Mostrar sons')?></span>
                                </label>
                            </div>

                        </div>
                        <div class="card-bodyx list-group list-group-flush overflow-auto"  id="divListaDeCategorias"></div>
                        <div class="card-body"> 
                            <a class="btn btn-primary pull" id="btnAddClasse"  onClick="showAdicionarClasse()"><?=_t('Adicionar Classe')?></a>
                            <div id="addClasse" style="display:none" class="input-group">
                                <input type="text" class="form-control" id="nomeClasse"  placeholder="<?=_t('Nome (ex. Consoante)')?>">
                                <input type="text" class="form-control" id="simboloClasse" placeholder="<?=_t('Símbolo (ex. C)')?>">
                                <input type="hidden" id="idClasse">
                                <a class="btn btn-primary"  onClick="salvarClasse()"><?=_t('Salvar')?></a>
                            </div>
                        </div> 
                  </div>
              </div>

              <div class="col-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><?=_t('Estrutura')?></h3>

                            <div class="card-actions">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sck" onchange="changeMultiSilabas()">
                                        <span class="form-check-label"><?=_t('Múltiplos tipos')?></span>
                                    </label>
                                </div>
                        </div>
                        <div class="card-bodyx">
                                <div class="list-group list-group-flush overflow-auto" id="divEstruturaSilabica"> </div>

                                <!--input type="hidden" id="idCompEdit">
                                <input type="hidden" id="idFormaEdit">
                                <div id="addComp" style="display:none" >
                                    <select id="compAdicionar" class="chosen-select form-control " onchange="salvarComp()" >
                                        <option value="0" selected>Selecione...</option>
                                        <?php 
                                            $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM classesSom WHERE id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
                                            while ($lang = mysqli_fetch_assoc($langs)){
                                                echo '<option value="'.$lang['id'].'" title="'.$lang['nome'].'">'.$lang['simbolo'].' - '.$lang['nome'].'</option>';
                                            }
                                        ?>
                                        <option value="-1" title="Remover">Remover</option>
                                    </select>
                                    <select id="compOb" class="chosen-select form-control "  onchange="salvarComp()" >
                                        <option value="0" selected>Opcional (onset, coda)</option>
                                        <option value="1">Obrigatório (núcleo)</option>
                                    </select>
                                    <a class="btn btn-sm btn-info btn-rounded col-sm-3"  onClick="salvarComp()">Salvar</a>
                                    <a class="btn btn-sm btn-info btn-rounded col-sm-3"  onClick="loadEstrutura()">Voltar</a>
                                </div-->

                                
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <label class="form-label"><?=_t('Quantidade máxima de sílabas por palavra')?></label>
                            <input type="number" class="form-control" id="silabas" value="<?=$idioma['silabas']?>" onchange="updateSilabas()">
                            <label class="text-secondary"><?=_t('Deixe 0 para ilimitado')?></label>
                        </div>
                    </div>
              </div>
            </div>
          </div>
        </div>




<script>
function remCat(id){
    if(confirm("<?=_t('Remover esta categoria de sons?')?>")){
        $.get("api.php?action=ajaxRemoverCatSom&iid=<?=$id_idioma?>&id="+id, function (data){
            if ($.trim(data) =='ok'){
                $("#divListaDeCategorias").load("api.php?action=listarCategoriasSom&iid=<?=$id_idioma?>");
            }else{
                alert(data);
            };
        });
    }
};
function showAdicionarClasse(){
    $('#idClasse').val(0); 
    $('#nomeClasse').val(''); 
    $('#simboloClasse').val(''); 
    $('#addClasse').show(); 
    $('#btnAddClasse').hide(); 
};
function showAdicionarComp(idComp = 0,idForma = 0, ob = 1){
    //$('#compAdicionar').val(0); 
    // selecionar no combo a info
    //if id>0 show btnApagarComp

    $('#compAdicionar').val(idComp); 
    $('#idCompEdit').val(idComp); 
    $('#idFormaEdit').val(idForma); 
    $('#compOb').val(ob); 
    $('#addComp').show(); 
    $(".chosen-select").trigger("chosen:updated");
    $('.btnAddComp').hide(); 
};

function salvarComp(){
    if ($('#compAdicionar').val()=='0') return;
    if ($('#compAdicionar').val()=='-1') {
        apagarComp();
        return;
    }
    $.post("api.php?action=adicionarComponenteSilaba&iid=<?=$id_idioma?>", 
    { idc:$('#compAdicionar').val(),
      id:$('#idCompEdit').val(),
      f:$('#idFormaEdit').val(),
      ob:$('#compOb').val()
    }, function (data){
        if ($.trim(data) =='ok'){
            loadEstrutura();
        }else{
            alert(data);
        };
    });
}
function apagarComp(){
    $.post("api.php?action=removerComponenteSilaba&iid=<?=$id_idioma?>", 
    { idc:$('#compAdicionar').val(),
      id:$('#idCompEdit').val(),
      //f:$('#idFormaEdit').val(),
      //ob:0//$('#compOb').val()
    }, function (data){
        if ($.trim(data) =='ok'){
            loadEstrutura();
        }else{
            alert(data);
        };
    });
}
function salvarClasse(){
    if ($('#simboloClasse').val()=='') {
        $('#addClasse').hide(); 
        $('#btnAddClasse').show();
        return;
    };
    $.post("api.php?action=adicionarCategoriaSom&id="+ $('#idClasse').val()+"&iid=<?=$id_idioma?>", 
    { nome:$('#nomeClasse').val(),
        simbolo:$('#simboloClasse').val()
    }, function (data){
        if ($.trim(data) > 0){
            $('#addClasse').hide(); 
            $('#btnAddClasse').show(); 
            $("#divListaDeCategorias").load("api.php?action=listarCategoriasSom&iid=<?=$id_idioma?>");
        }else{
            alert(data);
        };
    });
}
function mostrarSonsAdicionaveis(idb){
    $.get("api.php?action=listarSonsAdicionaveis&iid=<?=$id_idioma?>&id="+idb,function (data){
        $("#btnAddSom"+idb).html(data);
    });
}
function toggleSom(id,t,c){
    $.get("api.php?action=toggleSonsAdicionaveis&iid=<?=$id_idioma?>&id="+id+"&t="+t+"&c="+c,function (data){
        $("#divListaDeCategorias").load("api.php?action=listarCategoriasSom&iid=<?=$id_idioma?>");
    });
}
function loadEstrutura(){ //$("#divEstruturaSilabica").html('Em construção');return;
    //$("#addComp").hide();
    //$('.btnAddComp').show(); 
    //$('#compAdicionar').val(0);
    $("#divEstruturaSilabica").load("api.php?action=ajaxEstruturaSilabica&iid=<?=$id_idioma?>");
}
$(document).ready(function(){
    $("#divListaDeCategorias").load("api.php?action=listarCategoriasSom&iid=<?=$id_idioma?>");
    loadEstrutura();
    $(".chosen-select").chosen();
    //novaPalavra();
}); 

function changeMultiSilabas(){
    $.get("api.php?action=ajaxToggleEstruturaSilabica&iid=<?=$id_idioma?>",function (data){
        if ($.trim(data)=='ok')
            $("#divEstruturaSilabica").load("api.php?action=ajaxEstruturaSilabica&iid=<?=$id_idioma?>");
        else
            alert(data);
    });
}
function sCk(x){
    $("#sck").attr("checked",x==1?true:false);
}
function rmC(id){  //toggle obrigatorio
    $.post("api.php?action=ajaxSetComponenteSilaba&id="+id, function (data){
        if ($.trim(data)=='ok')
            $("#divEstruturaSilabica").load("api.php?action=ajaxEstruturaSilabica&iid=<?=$id_idioma?>");
        else
            alert(data);
    });
}
function clrS(id){ // removerComponente
    if(confirm("<?=_t('Remover último da estrutura silábica?')?>"))
    $.get("api.php?action=removerComponenteSilaba&id="+id, function (data){
        if ($.trim(data)=='ok')
            $("#divEstruturaSilabica").load("api.php?action=ajaxEstruturaSilabica&iid=<?=$id_idioma?>");
        else
            alert(data);
    });
}
function updateSilabas(){
    $.get("api.php?action=updateMaxSilabas&i=<?=$id_idioma?>&n="+$("#silabas").val(), function (data){
        if ($.trim(data)=='ok')
            console.log('silabas ok');
        else
            alert(data);
    });
}





function dragstartHandler(ev) {
  ev.dataTransfer.setData("text", ev.target.id);
} 
function dragoverHandler(ev) {
  ev.preventDefault();
}
function dropHandler(ev) {
    ev.preventDefault();
    const data = ev.dataTransfer.getData("text");

    //alert("from "+data+" to "+ev.target.id); return;
    // colocar qdo arrasta das estruturas, mostrar lixeirinha, e dropar nela deleta o som
    // o clique fica pro toggle obrigatorio

    // ajaxSetComponenteSilaba com id > faz o toggle
    
    $.post("api.php?action=ajaxSetComponenteSilaba&iid=<?=$id_idioma?>", 
        {from: data, to: ev.target.id}, function (data){
        if ($.trim(data)=='ok')
            $("#divEstruturaSilabica").load("api.php?action=ajaxEstruturaSilabica&iid=<?=$id_idioma?>");
        else
            alert(data);
    });
    

}
</script>

<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />


<div class="modal modal-blur" id="modal-renomeardim" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Renomear dimensão')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Nome')?></label>
            <input type="text" placeholder="<?=_t('Nome (Ex.: Glottal)')?>" id="nomeDim" class="form-control" value="">
            <input type="hidden"id="idDim">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execRenomearDim()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>