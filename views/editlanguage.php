
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];

$collab = false;

if ($id_idioma>0){

    $idioma = array();  
    $result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.tamanho, e.id_fonte,
                (SELECT COUNT(*) FROM studason_tests where id_idioma = i.id AND num_palavras > 0) as numPublishedTexts,
                (SELECT COUNT(*) FROM tests_importasons where id_texto IN(SELECT id FROM studason_tests where id_idioma = i.id)) as numUsersTexts,
                (SELECT COUNT(*) FROM soundChanges where id_idioma = i.id) as numChangesList,
                (SELECT COUNT(*) FROM palavras where id_idioma = i.id AND id_forma_dicionario = 0) as numBaseWords,
                (SELECT COUNT(*) FROM classes where id_idioma = i.id) as numParts,
                (SELECT COUNT(*) FROM palavras where id_idioma = i.id) as numTotalWords,
                (SELECT COUNT(*) FROM escritas where id_idioma = i.id) as numWritingSysts,
                (SELECT COUNT(*) FROM artygs where id_idioma = i.id) as numArtigos,
                (SELECT COUNT(*) FROM frases where id_idioma = i.id) as numFrases,
                (SELECT id FROM escritas where id_idioma = i.id AND padrao = 1) as eid,
                (SELECT COUNT(*) FROM glifos where id_escrita IN(SELECT id FROM escritas where id_idioma = i.id)) as numCharsTotal,
                (SELECT COUNT(*) FROM inventarios where id_idioma = i.id) as numTotalSounds,
                (SELECT COUNT(*) FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
                FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                WHERE i.id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)) { 
    $idioma  = $r;
    };

    if ( $idioma['nome_legivel']==''){
        echo '<script>window.location = "index.php?page=mylanguages";</script>';
        exit;
    }else if($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0) {
        echo '<script>window.location = "index.php";</script>';
        exit;
    }
    if($idioma['collab'] > 0) $collab = true;
    if(! $idioma['id_fonte'] > 0) $idioma['id_fonte'] = 0;
}else{
    // NOVO IDIOMA:
    $idioma['nome_legivel'] = '';
    $idioma['id_fonte'] = 0;
}
// $idioma['collab']>0 significa não pode mexer em tudo, precisa permissão, mas pode ter acesso

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
                      <li class="breadcrumb-item active"><a><?=$id_idioma>0 ? $idioma['nome_legivel'] : _t('Novo idioma')?></a></li>
                    </ol>
                </h2>
              </div>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body appLoad">
          <div class="container-xl">
            <div class="row row-deckx row-cards">



            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?=_t('Sobre o idioma')?></h4>
                        <div class="card-actions">
                            <a href="#" id="btnSalvar" onclick="gravarDados()" style="display:none" class="btn btn-primary">
                                <?=_t('Salvar')?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-5">
                            <div class="col-12">


                                <div class="mb-3">
                                    <label class="form-label"><?=_t('Nome legível')?></label>
                                    <input type="text" class="form-control" id="nome_legivel" value="<?=$idioma['nome_legivel']?>" onchange="showGravarDados()">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><?=_t('Descrição')?> </label>
                                    <textarea id="descricao"><?=$idioma['descricao']?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Ascendente')?></label>
                                            <select type="text" class="form-select" id="id_ascendente" value="" onchange="showGravarDados()">
                                                <option value="0" selected><?=_t('Nenhuma')?></option>
                                                <?php 
                                                $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM idiomas WHERE buscavel = 1;") or die(mysqli_error($GLOBALS['dblink'])); //xxxxx AND buscavel = 1?
                                                while ($lang = mysqli_fetch_assoc($langs)){
                                                    echo '<option value="'.$lang['id'].'"';
                                                    if ($idioma['id_ascendente'] == $lang['id']) echo ' selected';
                                                    echo '>'.$lang['nome_legivel'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Grupo/Família')?></label>
                                            <select type="text" class="form-select" id="id_familia" value="" onchange="selectFamilia()">
                                                <option value="0" selected><?=_t('Nenhuma')?></option>
                                                <?php 
                                                $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM grupos_idiomas;") or die(mysqli_error($GLOBALS['dblink'])); //xxxxx AND buscavel = 1?
                                                while ($lang = mysqli_fetch_assoc($langs)){
                                                    echo '<option value="'.$lang['id'].'"';
                                                    if ($idioma['id_familia'] == $lang['id']) echo ' selected';
                                                    echo '>'.$lang['nome'].'</option>';
                                                }
                                                ?>
                                                <option value="-1"><?=_t('NOVO')?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Momento/Realidade')?></label>
                                            <select type="text" class="form-select" id="id_momento" value="" onchange="showGravarDados()">
                                                <option value="0" selected><?=_t('Não especificado')?></option>
                                                <?php
                                                $momentos = mysqli_query($GLOBALS['dblink'], 
                                                    "SELECT m.id, m.nome, m.time_value, m.data_calendario, r.titulo as realidade 
                                                    FROM momentos m 
                                                        LEFT JOIN realidades r ON r.id = m.id_realidade
                                                    WHERE r.id_usuario = ".$_SESSION['KondisonairUzatorIDX']."
                                                        OR m.id_usuario = ".$_SESSION['KondisonairUzatorIDX']."
                                                    ORDER BY m.time_value, m.ordem;") or die(mysqli_error($GLOBALS['dblink']));
                                                while ($m = mysqli_fetch_assoc($momentos)) {
                                                    echo '<option value="'.$m['id'].'" data-date="'.$m['realidade'].' - '.$m['data_calendario'].'" '.(
                                                        $m['id']==$idioma['id_momento']?'selected':''
                                                    ).'>'.$m['nome'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Nome nativo')?></label>
                                            <!-- USAR CACHE -->
                                            <select type="text" class="form-select" id="id_nome_nativo" value="" onchange="showGravarDados()">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Visibilidade')?></div>
                                            <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" <?php if ($idioma['publico'] == '1') echo 'checked'; ?> id="publico" onchange="showGravarDados()">
                                            <span class="form-check-label"><?=_t('Deixar público')?></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-2">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Checar pronúncia')?></div>
                                            <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" <?php if ($idioma['checar_sons'] == '1') echo 'checked'; ?> id="checar_sons" onchange="showGravarDados()">
                                            <span class="form-check-label"><?=_t('Checar pronúncia ao adicionar palavras')?></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Romanização')?></div>
                                            <select class="form-select" id="romanizacao" onchange="showGravarDados()">
                                                <option value="0" selected><?=_t('Não')?></option>
                                                <option value="1" <?php if($idioma['romanizacao']=='1') echo 'selected'; ?> ><?=_t('Sim')?></option>
                                                <option value="2" <?php if($idioma['romanizacao']=='2') echo 'selected'; ?> ><?=_t('Utilizar como principal em lugar da pronúncia')?></option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="row">

                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Idioma da descrição')?></label>
                                            <?php
                                            if($idioma['id_idioma_descricao']>0) $l = $idioma['id_idioma_descricao'];
                                            else $l = $_SESSION['KondisonairUzatorDiom'];
                                            echo gerarSelectIdiomas('idioma_descricao', $l, 'showGravarDados()', false);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Status')?></label>
                                            <select type="text" class="form-select" id="status" value="" onchange="showGravarDados()">
                                                <option value="0" <?php if ($idioma['status']==0) echo 'selected'; ?> ><?=_t('Rascunho')?></option>
                                                <option value="1" <?php if ($idioma['status']==1) echo 'selected'; ?> ><?=_t('Em construção')?></option>
                                                <option value="3" <?php if ($idioma['status']==3) echo 'selected'; ?> ><?=_t('Básica')?></option>
                                                <option value="7" <?php if ($idioma['status']==7) echo 'selected'; ?> ><?=_t('Funcional')?></option>
                                                <option value="8" <?php if ($idioma['status']==8) echo 'selected'; ?> ><?=_t('Quase completa')?></option>
                                                <option value="9" <?php if ($idioma['status']==9) echo 'selected'; ?> ><?=_t('Usável no dia a dia')?></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-4">
                                        <div class="mb-3">
                                            <div class="form-label"><?=_t('Sigla')?></div>
                                            <input type="text" class="form-control" id="sigla" value="<?=$idioma['sigla']?>" onchange="checarSigla()">
                                            </label>
                                        </div>
                                    </div>


                                    <?php if(true || !$collab){  
                                        if ($idioma['id_usuario'] == $_SESSION['KondisonairUzatorIDX']) $edC = true; ?>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label"><?=_t('Colaboradores')?></label>
                                            <select type="text" multiple class="form-select" id="collabs" value="" <?php if($edC) echo 'onchange="showGravarDados()"'; else echo ' disabled readonly '; ?> >
                                            <?php 
                                                $cs = mysqli_query($GLOBALS['dblink'],"SELECT u.username FROM collabs c LEFT JOIN usuarios u ON u.id = c.id_usuario WHERE c.id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink'])); //xxxxx AND buscavel = 1?
                                                while ($s = mysqli_fetch_assoc($cs)){
                                                    if($s['id'] == $_SESSION['KondisonairUzatorIDX']) continue;
                                                    echo '<option value="'.$s['username'].'" selected >'.$s['username'].'</option>';
                                                }
                                            ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    
                                </div>

                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Mais detalhes')?></h3>
                        <?php if ($id_idioma>0){ ?>
                        <div class="card-actions">
                            <a href="#" onclick="renameParts()" class="btn btn-primary">
                                <?=_t('Configurações')?>
                            </a>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <div class="list-group list-group-flush list-group-hoverable">
                        <?php if ($id_idioma>0){ ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=editsounds&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Sons')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numTotalSounds']?> <?=_t('sons')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=editsyllables&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Sílabas')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=editwriting&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Escrita')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numWritingSysts']?> <?=_t('sistemas de escrita')?>, <?=$idioma['numCharsTotal']?> <?=_t('caracteres')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=editparts&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Classes de palavras')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numParts']?> <?=_t('classes')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=editlexicon&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Léxico')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numBaseWords']?> <?=_t('palavras de dicionário')?>, <?=$idioma['numTotalWords']?> <?=_t('palavras no total')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=phrases&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Frases')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numFrases']?> <?=_t('frases')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=myarticles&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Artigos')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numArtigos']?> <?=_t('artigos publicados')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=texts&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Textos')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numPublishedTexts']?> <?=_t('textos publicados')?>, <?=$idioma['numUsersTexts']?> <?=_t('usuários estudando')?></div>
                                </div>
                            </div>
                        </div> 

                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <!--div class="col-auto"><span class="badge bg-red"></span></div-->
                                <div class="col text-truncate">
                                    <a href="?page=changer&iid=<?=$id_idioma?>" class="text-reset d-block"><?=_t('Transformar')?></a>
                                    <div class="d-block text-secondary text-truncate mt-n1"><?=$idioma['numChangesList']?> <?=_t('listas de mudanças salvas')?></div>
                                </div>
                            </div>
                        </div> 

                    
                        <?php }else{ ?>
                            <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="d-block text-secondary mt-n1"><?=_t('Aqui estarão mais detalhes que você poderá incluir após salvar o seu idioma')?></div>
                                </div> 
                            </div>
                        </div> 
                        <?php } ?>
                    </div>
                    
                </div>
            </div>


            </div>
          </div>
        </div>

<script>

function checarSigla(){
    showGravarDados();
}

<?php if(true || !$collab){ ?>
function showGravarDados(){
    // btn gravar show
    $("#btnSalvar").show();
};
function gravarDados(){
    $.post("?action=ajaxGravarIdioma"
        +"&id="+ $('#codigo').val(), 
        {   nome: $('#nome').val(),
            nome_legivel:$('#nome_legivel').val(),
            publico:document.getElementById('publico').checked ? 1 : 0, //$('#publico').val(),
            descricao:tinymce.get('descricao').getContent(), //$('#descricao').val(),
            checar_sons: document.getElementById('checar_sons').checked ? 1 : 0, //$('#checar_sons').val(),
            romanizacao: $('#romanizacao').val(), //document.getElementById('romanizacao').checked ? 1 : 0, //$('#romanizacao').val(),
            id_familia:$('#id_familia').val(),
            status:$('#status').val(),
            id_nome_nativo:$('#id_nome_nativo').val(),
            sigla:$('#sigla').val(),
            idioma_desc:$('#idioma_descricao').val(),
            collabs:$('#collabs').val(),
            id_ascendente:$('#id_ascendente').val(),
            id_momento:$('#id_momento').val()
        }, function (data){
        if ($.trim(data) > 0){
            if ( $('#codigo').val() == 0 ) window.location = "?page=editlanguage&iid="+$.trim(data);
            $("#btnSalvar").hide();
            localStorage.setItem("k_opwords_<?=$id_idioma?>_updated", '0');
        }else{
            alert(data);
        }
    });
};
<?php }; ?>

function selectFamilia(){ alert('A fazer'); return;
    if($('#id_familia').val()=='-1'){
        //nova familia
        $.confirm({
            title: 'Novo grupo',
            type: 'green', 
            typeAnimated: true,
            content: '<input type="text" class="form-control" id="nome_grupo" placeholder="Nome do Grupo">'  ,
            containerFluid: true, 
            buttons: {
                "Salvar": function () {
                    var idl = this.$content.find('#nome_grupo').val();
                    if(idl==''){
                        $.alert('Nome vazio!');
                        return false;
                    };
                    $.get("?action=ajaxNovoGrupoIdiomas&nome="+idl, function (data){
                        window.location = "?page=editlanguage&iid=<?=$id_idioma?>";
                    });
                },
                Cancelar: function () {
                        
                } 
            }
        });
    }else gravarDados();
}

function loadNativeWords(){
    let data = <?=getLastChange('lexicon',$id_idioma)?>;
    if (data > localStorage.getItem("k_opwords_<?=$id_idioma?>_updated")){
        console.log('local words outdated > update');
        $.get("api.php?action=getOptionsListWords&iid=<?=$id_idioma?>&eid=<?=$idioma['eid']?>&selected=<?=$idioma['id_nome_nativo']?>", function (lex){

            $("#id_nome_nativo").html(lex);

            localStorage.setItem("k_opwords_<?=$id_idioma?>", lex);
            localStorage.setItem("k_opwords_<?=$id_idioma?>_updated", data);
            //createTablerSelectDrawcharWords
            createTablerSelectNativeWords('id_nome_nativo','<?=$idioma['id_fonte']?>','<?=$idioma['tamanho']?>');
            updateTablerSelect("id_nome_nativo",'<?=$idioma['id_nome_nativo']?>');
        });
    }else{
        console.log('local words load');
        $("#id_nome_nativo").html( localStorage.getItem("k_opwords_<?=$id_idioma?>") );
        //createTablerSelectDrawcharWords
        createTablerSelectNativeWords('id_nome_nativo','<?=$idioma['id_fonte']?>','<?=$idioma['tamanho']?>');
        updateTablerSelect("id_nome_nativo",'<?=$idioma['id_nome_nativo']?>');
    }
}

$(document).ready(function(){
    loadNativeWords();
    appLoad();
    //createTablerSelectNativeWords('id_nome_nativo');
}); 

formatarTablerSelect('id_ascendente');
formatarTablerSelect('id_familia');
formatarTablerMomentsSelect('id_momento');
formatarTablerSelect('idioma_descricao');
formatarTablerSelect('status'); 
formatarTablerSelect('collabs','body',true); 

function renameParts(){
    //xxxxx renomear partes, exportar conlang, tbm apagar completamente
    $("#modal-configs").modal('show');
}

document.addEventListener("DOMContentLoaded", function () {
    let options = {
    selector: '#descricao',
    height: 300,
    menubar: false,
    statusbar: false,
    setup: (editor) => {
        editor.on('keyup', (e) => {
            showGravarDados();
        });
    },
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'paste', 'image'
        //'image charmap print preview anchor',
        //'searchreplace visualblocks code fullscreen',
        //'insertdatetime media table code help wordcount'
    ],
    toolbar: 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | link unlink | image |' +
        'removeformat',
    automatic_uploads: true,
    file_picker_types: 'image',
    images_file_types: 'jpg,jpeg,png,webp',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
    }
    if (localStorage.getItem("tabler-theme") === 'dark') {
        options.skin = 'oxide-dark';
        options.content_css = 'dark';
    }
    tinyMCE.init(options);
})

function togglePasswordField() {
    const passwordContainer = document.getElementById('passwordContainer');
    const deleteStatus = document.getElementById('deleteStatus');
    passwordContainer.classList.toggle('d-none');
    deleteStatus.classList.add('d-none'); // Hide status on toggle
    document.getElementById('deletePassword').value = ''; // Clear password field
}
</script>

<div class="modal modal-blur" id="modal-configs" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mais configurações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <a href="#" class="btn link-secondary" onclick="limparCacheLocal('<?=$id_idioma?>')">
                    Apagar todo o cache local
                </a>
                <a href="?action=exportarIdioma&id_idioma=<?=$id_idioma?>" class="btn link-secondary" target="_blank">
                    Exportar para arquivo
                </a>
            </div>
            <div class="modal-footer">
                <div class="d-flex align-items-center">
                    <a href="#" class="btn link-danger" onclick="togglePasswordField()">Excluir idioma!</a>
                    <div id="passwordContainer" class="ms-3 d-none">
                        <input type="password" class="form-control d-inline-block" id="deletePassword" placeholder="Insira sua senha" style="width: 200px;">
                        <button class="btn btn-danger ms-2" onclick="excluirIdioma('<?=$id_idioma?>', document.getElementById('deletePassword').value)">Confirmar</button>
                    </div>
                </div>
                <a href="#" class="btn btn-primary ms-auto" data-bs-dismiss="modal">Fechar</a>
            </div>
            <div id="deleteStatus" class="modal-body d-none"></div>
        </div>
    </div>
</div>