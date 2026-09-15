
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
<input type="hidden" id="idRegra" value="0" />


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Sintaxe')?></a></li>
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

            <div class="col-12">
                <div class="alert alert-info">
                  <?=_t('Cadastre aqui as regras de sintaxe do idioma: combine duas classes de palavras (ou regras já criadas) para formar um novo símbolo — por exemplo, "Artigo + Substantivo = Sintagma nominal". Esta tela ainda serve apenas para preparar as regras; a análise automática de frases vem depois.')?>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                  <div class="card-header">
                      <h3 class="card-title"><?=_t('Regras')?></h3>
                      <div class="card-actions">
                        <a onclick="novaRegra()" class="btn btn-primary d-none d-sm-inline-block">
                          <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                          <?=_t('Nova regra')?>
                        </a>
                      </div>
                    </div>
                  <div class="card-bodyx">
                    <div class="overflow-auto" id="regrasTable" style="max-height: 35rem">

                    </div>
                  </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                  <div class="card-header">
                      <h3 class="card-title"><?=_t('Regra')?></h3>
                      <div class="card-actions">
                        <a id="btnSalvar" onclick="gravarRegra()" class="btn btn-primary">
                          <?=_t('Salvar')?>
                        </a>
                      </div>
                  </div>
                  <div class="card-body">

                      <div class="mb-3">
                        <label class="form-label"><?=_t('Nome do símbolo')?></label>
                        <input type="text" class="form-control" id="nome"
                          onchange="editarRegra()" placeholder="<?=_t('Ex.: Sintagma nominal')?>">
                      </div>

                      <div class="mb-3" >
                          <label class="form-label"><?=_t('Gloss')?></label>
                          <select id="gloss" onchange="editarRegra()" type="text" class="form-select" value="">
                              <option value="0" selected><?=_t('Não especificado')?></option>
                              <?php
                              $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses ;") or die(mysqli_error($GLOBALS['dblink']));
                              while ($lang = mysqli_fetch_assoc($langs)){
                                  echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'"';
                                  echo '>'.$lang['gloss'].' - '.$lang['descricao'].'</option>';
                              }
                              ?>
                          </select>
                      </div>

                      <hr>

                      <div class="row mb-3">
                          <div class="col-6">
                              <label class="form-label"><?=_t('Núcleo')?></label>
                              <select id="id_n" class="form-select" onchange="editarRegra()"></select>
                          </div>
                          <div class="col-6">
                              <label class="form-label"><?=_t('Dependente')?></label>
                              <select id="id_d" class="form-select" onchange="editarRegra()"></select>
                          </div>
                      </div>

                      <div class="mb-3">
                          <label class="form-label"><?=_t('Ordem')?></label>
                          <div class="form-selectgroup">
                              <label class="form-selectgroup-item">
                                  <input type="radio" name="lado" value="0" class="form-selectgroup-input" onchange="editarRegra()">
                                  <span class="form-selectgroup-label"><?=_t('Núcleo + Dependente')?></span>
                              </label>
                              <label class="form-selectgroup-item">
                                  <input type="radio" name="lado" value="1" class="form-selectgroup-input" checked onchange="editarRegra()">
                                  <span class="form-selectgroup-label"><?=_t('Dependente + Núcleo')?></span>
                              </label>
                          </div>
                      </div>

                      <div class="mb-3" >
                          <label class="form-label"><?=_t('Descrição')?></label>
                          <textarea class="form-control" id="descricao" rows="4"
                              onchange="editarRegra()"  placeholder="<?=_t('Ex.: Um substantivo precedido de um artigo')?>"></textarea>
                      </div>

                  </div>
                </div>
            </div>


            </div>
          </div>
        </div>


<script>
function gravarRegra(){
    if ($('#nome').val()=='') return;
    var nuc = ($('#id_n').val()||'').split(':');
    var dep = ($('#id_d').val()||'').split(':');
    if (nuc.length<2 || dep.length<2) {
        alert("<?=_t('Selecione o núcleo e o dependente.')?>");
        return;
    }

    $.post("?action=ajaxGravarRegra"
        +"&id="+ $('#idRegra').val()+"&iid=<?=$id_idioma?>",
    { nome:$('#nome').val(),
    gloss:$('#gloss').val(),
    tn:nuc[0],
    n:nuc[1],
    td:dep[0],
    d:dep[1],
    lado:$('input[name=lado]:checked').val(),
    separador:0,
    descricao:$('#descricao').val()
    }, function (data){
        if ($.trim(data) > 0){
            $('#idRegra').val($.trim(data));
            $("#regrasTable").load("?action=listarRegras&iid=<?=$id_idioma?>");
            $('#btnSalvar').hide();
        }else{
            alert(data);
        };
    });
};

function carregarSelectRegra(elId, selecionado, excluir){
    var url = "?action=ajaxSelectRegras&iid=<?=$id_idioma?>";
    if (excluir) url += "&excluir="+excluir;
    if (selecionado) url += "&selecionado="+encodeURIComponent(selecionado);
    $('#'+elId).load(url, function(){
        formatarTablerSelect(elId);
    });
};

function abrirRegra(rid){
	$("#regrasTable tr").removeClass("table-active");
    $("#row_"+rid).addClass("table-active");
    $('#idRegra').val(rid);
    $.getJSON( "?action=getDetalhesRegra&id=" +rid , function(data){
        var d = data[0];
        if (!d) return;
        $('#nome').val(d.nome);
        $('#descricao').val(d.descricao);
        $('#gloss').val(d.id_gloss);
        updateTablerSelect('gloss',d.id_gloss);

        carregarSelectRegra('id_n', d.tipo_nucleo+':'+d.id_nucleo, rid);
        carregarSelectRegra('id_d', d.tipo_dependente+':'+d.id_dependente, rid);

        $('input[name=lado][value="'+d.lado+'"]').prop('checked', true);

        $('#btnSalvar').hide();
    });
};

function editarRegra(){
    $('#btnSalvar').show();
};

function novaRegra(){
    $('#idRegra').val(0);
    $('#nome').val('');
    $('#descricao').val('');

    $('#gloss').val(0);
    updateTablerSelect('gloss',0);

    carregarSelectRegra('id_n', null, 0);
    carregarSelectRegra('id_d', null, 0);

    $('input[name=lado][value="1"]').prop('checked', true);

    $('#btnSalvar').hide();
    $("#regrasTable").load("?action=listarRegras&iid=<?=$id_idioma?>");
};

function apagarRegra(rid){
  if (confirm("<?=_t('Apagar esta regra?')?>"))
    $.get("?action=ajaxApagarRegra&id="+rid, function (data){
        $("#regrasTable").load("?action=listarRegras&iid=<?=$id_idioma?>");
        novaRegra();
    });
};

function moverAcima(rid){
    $.get("?action=ajaxRegraAcima&id="+rid, function (data){
        $("#regrasTable").load("?action=listarRegras&iid=<?=$id_idioma?>");
    });
};

function moverAbaixo(rid){
    $.get("?action=ajaxRegraAbaixo&id="+rid, function (data){
        $("#regrasTable").load("?action=listarRegras&iid=<?=$id_idioma?>");
    });
};

$(document).ready(function(){
    novaRegra();
});
formatarTablerSelect('gloss');
</script>
