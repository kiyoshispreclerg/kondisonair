<?php 
// Check for logged-in user
if (!isset($_SESSION['KondisonairUzatorIDX']) || $_SESSION['KondisonairUzatorNivle'] < 100) {
    echo '<script>window.location = "index.php";</script>';
    exit;
}
?>
<input type="hidden" id="idTipoSom" value="0" />
<input type="hidden" id="posX" value="0" />
<input type="hidden" id="posY" value="0" />

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                        <li class="breadcrumb-item active"><a><?=_t('Tabela IPA')?></a></li>
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
                <div class="card">
                    <div class="card-header">
                        <div class="col-4">
                            <select id="selTabela" class="form-select" onchange="carregaTabela()">
                                <?php
                                    $res = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom;") or die(mysqli_error($GLOBALS['dblink']));
                                    while($r = mysqli_fetch_assoc($res)) {
                                        echo '<option value="'.$r['id'].'">'.htmlspecialchars($r['titulo']).'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="card-actions">
                            <span class="card-title"><?=_t('Tabela IPA')?></span>
                        </div>
                    </div>
                    <div class="card-body" style="overflow-x: auto;">
                        <div id="tabelaView"></div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Editar Célula')?></h3>
                    </div>
                    <div class="card-body" id="editView">
                        <p><?=_t('Selecione uma célula na tabela')?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding/editing IPA -->
<div class="modal fade" id="ipaEditModal" tabindex="-1" aria-labelledby="ipaEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ipaEditModalLabel"><?=_t('Editar Som IPA')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?=_t('Nome')?></label>
                    <input type="text" class="form-control" id="ipaNome" placeholder="<?=_t('Ex.: Voiced velar nasal')?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?=_t('IPA')?></label>
                    <input type="text" class="form-control" id="ipaSimbolo" placeholder="<?=_t('Ex.: ŋ')?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=_t('Cancelar')?></button>
                <button type="button" class="btn btn-primary" id="btnSalvarIPA"><?=_t('Salvar')?></button>
            </div>
        </div>
    </div>
</div>

<script>
function carregaTabela(){
    var tipo = $('#selTabela').val();
    $('#idTipoSom').val(tipo);
    $.get("?action=carregarTabelaIPACompleta&ed=1&t="+tipo, function(data){
        $("#tabelaView").html(data);
        $("#editView").html('<p><?=_t("Selecione uma célula na tabela")?></p>');
        $('#posX').val(0);
        $('#posY').val(0);
    });
}

function editarCelula(x, y, z, a){
    var tipo = $('#selTabela').val();
    $('.cell__').removeClass('table-active');
    $('#cell_'+x+'_'+y).addClass('table-active');
    $('#posX').val(x);
    $('#posY').val(y);
    $.get("?action=carregarEdicaoIPACelula&x="+x+"&y="+y+"&z="+z+"&t="+tipo, function(data){
        $("#editView").html(data);
    });
}

function adicionarIPA(x, y, z){
    $('#ipaNome').val('');
    $('#ipaSimbolo').val('');
    $('#ipaEditModalLabel').text('<?=_t("Adicionar Som IPA")?>');
    $('#btnSalvarIPA').off('click').on('click', function(){
        var nome = $('#ipaNome').val();
        var ipa = $('#ipaSimbolo').val();
        if (!nome) {
            alert('<?=_t("Insira um nome!")?>');
            return;
        }
        if (!ipa) {
            alert('<?=_t("Insira o caractere IPA!")?>');
            return;
        }
        $.post("?action=ajaxEditarSomIPA&x="+x+"&y="+y+"&z="+z+"&t="+$('#idTipoSom').val(), 
            {ipa: ipa, nome: nome}, function(data){
                $('#ipaEditModal').modal('hide');
                carregaTabela();
                editarCelula(x, y, 0, 0);
            });
    });
    $('#ipaEditModal').modal('show');
}

function atualizarIPA(x, y, z, ipa, nome){
    $('#ipaNome').val(nome);
    $('#ipaSimbolo').val(ipa);
    $('#ipaEditModalLabel').text('<?=_t("Atualizar Som IPA")?>');
    $('#btnSalvarIPA').off('click').on('click', function(){
        var newNome = $('#ipaNome').val();
        var newIpa = $('#ipaSimbolo').val();
        if (!newNome) {
            alert('<?=_t("Insira um nome!")?>');
            return;
        }
        if (!newIpa) {
            alert('<?=_t("Insira o caractere IPA!")?>');
            return;
        }
        $.post("?action=ajaxEditarSomIPA&r=1&x="+x+"&y="+y+"&z="+z+"&t="+$('#idTipoSom').val(), 
            {ipa: newIpa, nome: newNome}, function(data){
                $('#ipaEditModal').modal('hide');
                carregaTabela();
                editarCelula(x, y, 0, 0);
            });
    });
    $('#ipaEditModal').modal('show');
}

function removerIPA(x, y, z){
    if (confirm('<?=_t("Remover este som IPA?")?>')) {
        $.get("?action=ajaxEditarSomIPA&x="+x+"&y="+y+"&z="+z+"&t="+$('#idTipoSom').val(), function(data){
            carregaTabela();
            editarCelula(x, y, 0, 0);
        });
    }
}

$(document).ready(function(){
    carregaTabela();
});
</script>
 