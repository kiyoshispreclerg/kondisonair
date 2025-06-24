<?php
// Fetch parameters
$id_frase = $_GET['id'] ?: 0;
$id_idioma = $_GET['iid'] ?: 0;
$id_usuario = $_SESSION['KondisonairUzatorIDX'] ?: 0;
$id_idioma_original = 0;

// Initialize variables
$data = [];
$breadcrumb = '';
$inputsNativos = '';
$inseridorDrawchar = '';
$scriptAutoSubstituicao = '';
$autoloader = '';

if ($id_frase > 0) {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT f.*, e.id as eid, i.nome_legivel, e.tamanho, e.id_fonte as fonte, i.id_usuario as dono
        FROM frases f
        LEFT JOIN idiomas i ON f.id_idioma = i.id
        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
        WHERE f.id = '$id_frase';") or die(mysqli_error($GLOBALS['dblink']));
    $data = mysqli_fetch_assoc($result);
    $id_idioma = $data['id_idioma'];
} elseif ($id_idioma > 0) {
    $result = mysqli_query($GLOBALS['dblink'], "SELECT i.nome_legivel, e.id as eid, e.tamanho, e.id_fonte as fonte, i.id_usuario as dono
        FROM idiomas i
        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
        WHERE i.id = '$id_idioma';") or die(mysqli_error($GLOBALS['dblink']));
    $data = mysqli_fetch_assoc($result);
}

if ($id_frase > 0 && $data['dono'] != $_SESSION['KondisonairUzatorIDX']) {
    echo '<script>window.location = "?page=phrase&id=' . $id_frase . '&iid=' . $id_idioma . '";</script>';
    exit;
}

if ($id_frase > 0 || $id_idioma > 0) {
    $breadcrumb = '<li class="breadcrumb-item"><a href="?page=' . ($data['dono'] == $_SESSION['KondisonairUzatorIDX'] ? 'edit' : '') . 'language&iid=' . $id_idioma . '">' . htmlspecialchars($data['nome_legivel']) . '</a></li>
        <li class="breadcrumb-item"><a href="?page=phrases&iid=' . $id_idioma . '">' . _t('Frases') . '</a></li>';

    $changed = getLastChange('autosubstituicoes', $data['eid']);
    $autoloader .= 'if(' . $changed . ' > localStorage.getItem("k_autosubs_updated_' . $data['eid'] . '") ) loadAutoSubstituicoes(\'' . $data['eid'] . '\', ' . $changed . ', true);';

    if ($data['fonte'] == 3) {
        $autoon = $data['substituicao'] == 1 ? ' (' . _t('Automático') . ')' : '';
        $inputsNativos .= '<div class="mb-3">
                <label class="form-label">Texto nativo ' . $autoon . ' <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasDrawchar" role="button" aria-controls="offcanvasEnd" onclick="loadCharDiv(\'' . $data['eid'] . '\',\'drawcharlist' . $data['eid'] . '\',false,\'' . $data['fonte'] . '\')">' . _t('Inserir caractere') . '</a></label>
                <input type="hidden" class="escrita_nativa" id="escrita_nativa_' . $data['eid'] . '" />
                <div class="form-control editable-drawchar" id="drawchar_editable_' . $data['eid'] . '" contenteditable="true" data-eid="' . $data['eid'] . '" data-fonte="' . $data['fonte'] . '" data-tamanho="' . $data['tamanho'] . '"></div>
            </div>';

        $inseridorDrawchar .= '<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDrawchar" aria-labelledby="offcanvasEndLabel">
                <div class="offcanvas-header">
                    <h2 class="offcanvas-title" id="offcanvasEndLabel">Caracteres</h2>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <div class="mb-3" id="drawcharlist' . $data['id'] . '"></div>
                    <div></div>
                </div>
            </div>';
    } else {
        if ($data['substituicao'] == 1) {
            $scriptAutoSubstituicao .= 'let data2 = getAutoSubstituicao("' . $data['eid'] . '",data);
                if (data2 == "-1") exibirNativa("' . $data['eid'] . '","","' . $data['fonte'] . '","' . $data['tamanho'] . '");
                else if(data2.length > 0) exibirNativa("' . $data['eid'] . '",data2,"' . $data['fonte'] . '","' . $data['tamanho'] . '");';
            $autoon = ' (' . _t('Automático') . ')';
        }

        $inputsNativos .= '<div class="mb-3">
                <label class="form-label">Texto nativo ' . $autoon . ' <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasNativeBtns" role="button" aria-controls="offcanvasEnd" onclick="loadCharDiv(\'' . $data['eid'] . '\')">' . _t('Inserir caractere') . '</a></label>
                <input type="text" class="form-control escrita_nativa custom-font-' . $data['eid'] . '" id="escrita_nativa_' . $data['eid'] . '" ';

        $inputsNativos .= $data['checar_glifos'] == 1 ? ' onchange="checarNativo(this,\'' . $data['eid'] . '\')"' : ' onchange="editarPalavra()"';
        $inputsNativos .= ' value="' . htmlspecialchars($data['frase'] ?? '') . '"></div>';
    }

} else {
    // Fetch available languages
    $languages = [];
    $result = mysqli_query($GLOBALS['dblink'], "SELECT id, nome_legivel FROM idiomas ORDER BY nome_legivel") or die(mysqli_error($GLOBALS['dblink']));
    while ($row = mysqli_fetch_assoc($result)) {
        $languages[] = $row;
    }
}
    $id_original = $dadosFrase['id_original'] ?? $_GET['original'] ?? 0;
?>

<?php if ($id_frase > 0 || $id_idioma > 0) { ?>
<input type="hidden" id="idFrase" value="<?=$id_frase?>" />
<input type="hidden" id="original" value="<?=$id_idioma_original?>" />

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li><?=$breadcrumb?>
                        <li class="breadcrumb-item active"><a><?=_t('Frase')?></a></li>
                    </ol>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="?page=editphrase&iid=<?=$id_idioma?>" class="btn btn-primary d-none d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14" /><path d="M5 12l14 0" />
                        </svg>
                        <?=_t('Nova')?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-deckx row-cards">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Detalhes')?></h3>
                        <div class="card-actions">
                            <a href="#" onclick="gravarPalavra()" id="saveBtn" class="btn btn-primary"><?=_t('Salvar')?></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($id_original > 0) {
                            $dados_original = mysqli_query($GLOBALS['dblink'], "SELECT f.*, e.id as eid, i.nome_legivel, e.tamanho, e.id_fonte as fonte, i.id_usuario as dono
                                FROM frases f
                                LEFT JOIN idiomas i ON f.id_idioma = i.id
                                LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                WHERE f.id = '$id_original';") or die(mysqli_error($GLOBALS['dblink']));
                            $data_original = mysqli_fetch_assoc($dados_original); // Fixed: Use $dados_original for fetch
                            if ($data_original['fonte'] == 3) {
                                echo '<div class="mb-3">
                                        <label class="form-label">' . _t('Frase original em %1', [$data_original['nome_legivel']]) . '</label>
                                        <input readonly type="hidden" class="escrita_nativa" id="escrita_nativa_' . $data_original['eid'] . '" />
                                        <div class="form-control editable-drawchar" id="drawchar_editable_' . $data_original['eid'] . '" contenteditable="true" data-eid="' . $data_original['eid'] . '" data-fonte="' . $data_original['fonte'] . '" data-tamanho="' . $data_original['tamanho'] . '"></div>
                                    </div>';
                            } else {
                                echo '<div class="mb-3">
                                        <label class="form-label">' . _t('Frase original em %1', [$data_original['nome_legivel']]) . '</label>
                                        <input value="'.$data_original['frase'].'" readonly type="text" class="form-control escrita_nativa custom-font-' . $data_original['eid'] . '" id="escrita_nativa_' . $data_original['eid'] . '"></div>';
                            }
                        ?>
                        <?php } ?>
                        <?=$inputsNativos?>
                        <div class="mb-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label"><?=_t('Mais informações')?></label>
                                    <textarea id="info" class="form-control" rows="5" onkeyup="editarPalavra()"><?=htmlspecialchars($data['info'] ?? '')?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label"><?=_t('Informações privadas')?></label>
                                    <textarea class="form-control" id="privado" rows="3" onkeyup="editarPalavra()"><?=htmlspecialchars($data['privado'] ?? '')?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Tags')?></label>
                            <select id="id_tags" multiple type="text" value="" class="form-select" onchange="editarPalavra()">
                                <?php
                                $sql = "SELECT tag FROM tags WHERE tipo_dest = 'phrase' AND id_dest = '$id_frase';";
                                $result = mysqli_query($GLOBALS['dblink'], $sql) or die(mysqli_error($GLOBALS['dblink']));
                                while ($r = mysqli_fetch_assoc($result)) {
                                    echo '<option value="' . htmlspecialchars($r['tag']) . '" selected>' . htmlspecialchars($r['tag']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body" id="traducoes">
                        <h3 class="card-title"><?=_t('Traduções')?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } else { ?>
<div class="page-body">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                        <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li><?=$breadcrumb?>
                        <li class="breadcrumb-item active"><a><?=_t('Frase')?></a></li>
                    </ol>
                </h2>
            </div>
		</div>
        <div class="row row-deck">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Escolha o idioma para a nova frase')?></label>
                            <select id="selectLanguage" class="form-select" onchange="selectLanguage()">
                                <option value=""><?=_t('Selecione um idioma')?></option>
                                <?php foreach ($languages as $lang) { ?>
                                    <option value="<?=$lang['id']?>"><?=htmlspecialchars($lang['nome_legivel'])?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<script>
<?php if ($id_frase > 0 || $id_idioma > 0) { ?>
function editarPalavra() {
    if ($('#significado').val() == '') {
        $("#significado").addClass('is-invalid');
        return;
    }
    $("#significado").removeClass('is-invalid');
    $('#saveBtn').show();
}

function gravarPalavra(ignorar = '0') {
    if ($('#frase').val() == '') {
        $("#frase").addClass('is-invalid');
        return;
    }
    $("#frase").removeClass('is-invalid');
    $.post("api.php?action=ajaxSalvarFrase&id=" + $('#idFrase').val(), {
        frase: $('#escrita_nativa_<?=$data['eid']?>').val(),
        idioma: '<?=$id_idioma?>',
        original: '<?=$id_original?>',
        privado: $('#privado').val(),
        traducao: $('#traducao').val(),
        tags: $('#id_tags').val(),
        info: $('#info').val()
    }, function(data) {
        if ($.trim(data) > 0) {
            $('#idFrase').val($.trim(data));
            $('#saveBtn').hide();
        } else {
            alert(data);
        }
    });
}

function loadPronDiv(forceReload = false) {
    $('#tempPron').val($('#pronuncia').val());
    let data = <?=getLastChange('sounds', $id_idioma)?>;
    if (forceReload || data > localStorage.getItem("k_sounds<?=$id_idioma?>_updated")) {
        console.log('local sounds outdated > update');
        $.get("api.php?action=ajaxGetDivLateralSons&iid=<?=$id_idioma?>", function(lex) {
            $("#divInserirSons").html(lex);
            localStorage.setItem("k_sounds<?=$id_idioma?>", lex);
            localStorage.setItem("k_sounds<?=$id_idioma?>_updated", data);
        });
    } else {
        console.log('local sounds load');
        $("#divInserirSons").html(localStorage.getItem("k_sounds<?=$id_idioma?>"));
    }
}

function salvarGenPal() {
    var i = $('#idgp').val();
    $.get("api.php?action=ajaxGravarGenPal&pid=" + $('#idFrase').val() + "&iid=<?=$id_idioma?>&i=" + i, function(data) {
        if ($.trim(data) > 0) {
            $('#detalhesGramaticais').load("api.php?action=getDetMorfPalavra&pid=" + $('#idFrase').val());
        } else {
            alert(data);
        }
    });
}

function checarRomanizacao(este, idioma) {
    $("#romanizacao").removeClass('is-invalid');
    <?php if ($romanizacao == 2) { ?>
    // Preencher pronúncia e foreach native
    <?php } ?>
    editarPalavra();
}

<?php if ($idioma['checar_sons'] == 1) { ?>
function checarPronuncia(este, idioma) {
    editarPalavra();
    $(este).removeClass('is-invalid');
    var tmpPron = $(este).val();
    let data = getChecarPronuncia(idioma, tmpPron, 1);
    if (data == '-1') {
        $(este).addClass('is-invalid');
    } else {
        $(este).val(data);
        data = tmpPron;
        <?php if ($romanizacao) echo '$("#romanizacao").val(tmpPron);'; ?>
        <?=$scriptAutoSubstituicao?>
    }
}
<?php } else { ?>
function checarPronuncia(este, idioma) {
    $("#pronuncia").removeClass("is-invalid");
    editarPalavra();
}
<?php } ?>

function checarNativo(este, eid) {
    $(este).removeClass('is-invalid');
    editarPalavra();
    $.post('api.php?action=getChecarNativo&eid=' + eid, {
        p: $(este).val()
    }, function(data) {
        if (data == '-1') {
            $(este).addClass('is-invalid');
        } else {
            if (data.length > 0)
                $(este).val(data);
        }
    });
}

function addNatChar(char) {
    $("#tempNat").val($("#tempNat").val() + char);
}

function loadExtras(tipo) {
    $("#extrasCanvas").load('api.php?action=' + tipo + '&pid=<?=$_GET['pid']?>');
}

$(document).ready(function() {
    createTablerSelect('id_tags', null, true);
});

<?php echo $autoloader; ?>
<?php } else { ?>
function selectLanguage() {
    var iid = $('#selectLanguage').val();
    if (iid) {
        window.location = "?page=editphrase&original=<?=$id_original?>&iid=" + iid;
    } else {
        alert('<?=_t('Por favor, selecione um idioma')?>');
    }
}
<?php } ?>
</script>

<?php if ($id_frase > 0 || $id_idioma > 0) { ?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasPronBtns" aria-labelledby="offcanvasStartLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Pronúncia')?></h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3" id="divInserirSons"></div>
        <div class="input-group">
            <input type="text" class="form-control" id="tempPron">
            <button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="okIpaPronuncia()">Ok</button>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNativeBtns" aria-labelledby="offcanvasStartLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Caracteres')?></h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3" id="divInserirChars"></div>
        <div class="input-group">
            <input type="text" class="form-control" id="tempNat">
            <button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="okInsertNativo()">Ok</button>
        </div>
        <input type="hidden" id="lateralEid">
    </div>
</div>
<?php } ?>