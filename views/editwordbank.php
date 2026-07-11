


<?php 
$banco = $_GET['id'];
if (!$banco>0) $banco = 0;
$id_idioma = $_SESSION['KondisonairUzatorDiom'];
if ($_GET['iid']>0) $id_idioma=$_GET['iid'];
/*
$_SESSION['KondisonairUzatorDiom']




	$id_idioma = $_GET['iid'];
	$filtro = 'dici';
	if (isset($_GET['t']) && $_GET['t']!='') $filtro = $_GET['t'];

	if (!$_GET['pid']>0) $_GET['pid'] = 0;
	$idioma = array();   
	$romanizacao = 0;
	$result = mysqli_query($GLOBALS['dblink'],"SELECT *,
		(SELECT COUNT(*) FROM studason_palavrs WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as numPal FROM idiomas
						WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
	while($r = mysqli_fetch_assoc($result)) { 
	$idioma  = $r;
	};
	$romanizacao = $idioma['romanizacao'];

	$fonts = '';

	$stats = ''; //$idioma['numPal'].' palavras estudando e conhecidas';
    */
   
    $query = "SELECT * FROM wordbanks l
        WHERE id = ".$banco.";";
    $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
    $bancoDados = mysqli_fetch_assoc($result);
?>


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=wordbanks"><?=_t('Bancos de palavras')?></a></li>
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Editar banco')?></a></li>
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



            <div class="col-12">
                <div class="card">

                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Banco de referentes')?></h3>

                        <div class="card-actions">
                                        <div class="row">
                                            <div class="col">
                                                    <a href="#" class="btn btn-primary d-none d-sm-inline-block" onclick="salvarLista()">
                                                    <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                                    <?=_t('Salvar')?>
                                                </a>
                                            </div>
                                        </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Nome')?></label>
                            <input type="text" class="form-control" id="titulo" value="<?=htmlspecialchars($bancoDados['titulo'])?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?=_t('Descrição')?> <span class="text-secondary">(<?=_t('tema, uso, etc.')?>)</span></label>
                            <input type="text" class="form-control" id="descricao" maxlength="200" value="<?=htmlspecialchars($bancoDados['descricao'])?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" id="publico" value="1" <?=$bancoDados['publico'] ? 'checked' : ''?>>
                                <span class="form-check-label"><?=_t('Banco público')?> <span class="text-secondary"><?=_t('(visível para todos os usuários)')?></span></span>
                            </label>
                        </div>

                        <?php
                            $idIdioma = (int)$_SESSION['KondisonairUzatorDiom'];

                            $bankRefs = [];
                            if ($banco > 0) {
                                $res = mysqli_query($GLOBALS['dblink'],
                                    "SELECT r.id, COALESCE(d.descricao, CONCAT('#', r.id)) as descricao
                                     FROM listas_referentes lr
                                     JOIN referentes r ON r.id = lr.id_referente
                                     LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND d.id_idioma = $idIdioma
                                     WHERE lr.id_lista = $banco
                                     ORDER BY lr.ordem") or die(mysqli_error($GLOBALS['dblink']));
                                while ($r = mysqli_fetch_assoc($res)) $bankRefs[] = $r;
                            }

                            $bankIds = array_column($bankRefs, 'id');
                            $notInClause = count($bankIds) > 0 ? 'AND r.id NOT IN ('.implode(',', $bankIds).')' : '';
                            $availRefs = [];
                            $res = mysqli_query($GLOBALS['dblink'],
                                "SELECT r.id, COALESCE(d.descricao, CONCAT('#', r.id)) as descricao
                                 FROM referentes r
                                 LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND d.id_idioma = $idIdioma
                                 WHERE 1=1 $notInClause
                                 ORDER BY d.descricao") or die(mysqli_error($GLOBALS['dblink']));
                            while ($r = mysqli_fetch_assoc($res)) $availRefs[] = $r;
                        ?>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label"><?=_t('Referentes disponíveis')?> <span class="text-secondary" id="avail-count">(<?=count($availRefs)?>)</span></label>
                                <input type="text" class="form-control mb-2" id="search-avail" placeholder="<?=_t('Buscar...')?>">
                                <div class="list-group list-group-flush border rounded overflow-auto" id="avail-list" style="min-height:12rem;max-height:28rem;">
                                    <?php foreach ($availRefs as $r): ?>
                                    <div class="list-group-item list-group-item-action ref-avail py-1 px-2" data-id="<?=$r['id']?>" data-label="<?=htmlspecialchars($r['descricao'])?>" onclick="addToBank(this)" style="cursor:pointer;">
                                        <?=htmlspecialchars($r['descricao'])?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label"><?=_t('No banco')?> <span class="text-secondary" id="bank-count">(<?=count($bankRefs)?>)</span></label>
                                <input type="text" class="form-control mb-2" id="search-bank" placeholder="<?=_t('Buscar...')?>">
                                <div class="list-group list-group-flush border rounded overflow-auto" id="bank-list" style="min-height:12rem;max-height:28rem;">
                                    <?php foreach ($bankRefs as $r): ?>
                                    <div class="list-group-item ref-bank py-1 px-2 d-flex align-items-center gap-2"
                                         data-id="<?=$r['id']?>" data-label="<?=htmlspecialchars($r['descricao'])?>"
                                         draggable="true" ondragstart="bankDragStart(event)" ondragover="bankDragOver(event)" ondrop="bankDrop(event)">
                                        <span class="text-secondary" style="cursor:grab;">⠿</span>
                                        <span class="flex-fill"><?=htmlspecialchars($r['descricao'])?></span>
                                        <a href="#" class="text-danger ms-auto" onclick="removeFromBank(this.closest('.ref-bank'));return false;">✕</a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <select multiple class="d-none" id="filtro">
                            <?php foreach ($bankRefs as $r): ?>
                            <option value="<?=$r['id']?>" selected><?=htmlspecialchars($r['descricao'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>


            </div>
          </div>
        </div>

<script>

    function syncHiddenSelect() {
        const sel = document.getElementById('filtro');
        sel.innerHTML = '';
        document.querySelectorAll('#bank-list .ref-bank').forEach(el => {
            const opt = document.createElement('option');
            opt.value = el.dataset.id;
            opt.selected = true;
            sel.appendChild(opt);
        });
        document.getElementById('bank-count').textContent = '(' + sel.options.length + ')';
        document.getElementById('avail-count').textContent = '(' + document.querySelectorAll('#avail-list .ref-avail').length + ')';
    }

    function addToBank(el) {
        const bankList = document.getElementById('bank-list');
        const div = document.createElement('div');
        div.className = 'list-group-item ref-bank py-1 px-2 d-flex align-items-center gap-2';
        div.dataset.id = el.dataset.id;
        div.dataset.label = el.dataset.label;
        div.draggable = true;
        div.setAttribute('ondragstart', 'bankDragStart(event)');
        div.setAttribute('ondragover', 'bankDragOver(event)');
        div.setAttribute('ondrop', 'bankDrop(event)');
        div.innerHTML = '<span class="text-secondary" style="cursor:grab;">⠿</span><span class="flex-fill">' +
            el.dataset.label.replace(/</g,'&lt;') + '</span><a href="#" class="text-danger ms-auto" onclick="removeFromBank(this.closest(\'.ref-bank\'));return false;">✕</a>';
        bankList.appendChild(div);
        el.remove();
        syncHiddenSelect();
    }

    function removeFromBank(el) {
        const availList = document.getElementById('avail-list');
        const div = document.createElement('div');
        div.className = 'list-group-item list-group-item-action ref-avail py-1 px-2';
        div.dataset.id = el.dataset.id;
        div.dataset.label = el.dataset.label;
        div.setAttribute('onclick', 'addToBank(this)');
        div.style.cursor = 'pointer';
        div.textContent = el.dataset.label;
        // insert alphabetically
        const items = availList.querySelectorAll('.ref-avail');
        let inserted = false;
        for (let item of items) {
            if (item.dataset.label.localeCompare(el.dataset.label) > 0) {
                availList.insertBefore(div, item);
                inserted = true;
                break;
            }
        }
        if (!inserted) availList.appendChild(div);
        el.remove();
        filterAvail();
        syncHiddenSelect();
    }

    // search filtering
    document.getElementById('search-avail').addEventListener('input', filterAvail);
    function filterAvail() {
        const q = document.getElementById('search-avail').value.toLowerCase();
        document.querySelectorAll('#avail-list .ref-avail').forEach(el => {
            el.style.display = el.dataset.label.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    document.getElementById('search-bank').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#bank-list .ref-bank').forEach(el => {
            el.style.display = el.dataset.label.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // drag-to-reorder within bank list
    let dragSrc = null;
    function bankDragStart(ev) {
        dragSrc = ev.currentTarget;
        ev.dataTransfer.effectAllowed = 'move';
    }
    function bankDragOver(ev) {
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
    }
    function bankDrop(ev) {
        ev.preventDefault();
        const target = ev.currentTarget;
        if (dragSrc && dragSrc !== target) {
            const list = document.getElementById('bank-list');
            const children = [...list.children];
            const srcIdx = children.indexOf(dragSrc);
            const tgtIdx = children.indexOf(target);
            if (srcIdx < tgtIdx) list.insertBefore(dragSrc, target.nextSibling);
            else list.insertBefore(dragSrc, target);
            syncHiddenSelect();
        }
    }

    function salvarLista(){
        $.post('api.php?action=saveWordbank&id=<?=$banco?>', {
          refs: $("#filtro").val(),
          titulo: $("#titulo").val(),
          descricao: $("#descricao").val(),
          publico: $("#publico").is(':checked') ? '1' : '0'
        }, function (data){
          if($.trim(data) == 'ok'){
            <?php if (!($banco>0)): ?>
            window.location.href = '?page=wordbanks';
            <?php else: ?>
            window.location.reload(true);
            <?php endif; ?>
          }else{
            alert(data);
          }
        });
    };

</script>