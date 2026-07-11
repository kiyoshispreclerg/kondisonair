        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Bancos de palavras')?></a></li>
                    </ol>
                </h2>
              </div>
              <?php if ($_SESSION['KondisonairUzatorIDX'] > 0){ ?>
              <div class="col-auto ms-auto">
                <a href="?page=editwordbank&id=0" class="btn btn-primary">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                  <?=_t('Novo banco')?>
                </a>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deck row-cards">

            <?php
              $iidParam = $_GET['iid']>0 ? '&iid='.$_GET['iid'] : '';
              $uid = (int)$_SESSION['KondisonairUzatorIDX'];

              $query = "SELECT l.*, u.username,
                  (SELECT COUNT(*) FROM listas_referentes r WHERE r.id_lista = l.id) as numRefs
                FROM wordbanks l
                LEFT JOIN usuarios u ON u.id = l.id_usuario
                WHERE l.id_usuario = $uid OR l.publico = 1
                ORDER BY l.id_usuario = $uid DESC, l.data_modificacao DESC;";
              $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
              $rows = [];
              while($r = mysqli_fetch_assoc($result)) $rows[] = $r;

              $myBanks = array_filter($rows, fn($r) => $r['id_usuario'] == $uid);
              $pubBanks = array_filter($rows, fn($r) => $r['id_usuario'] != $uid && $r['publico']);

              function renderBankList($banks, $iidParam, $uid, $showOwner = false) {
                foreach ($banks as $r) {
                  $isOwner = $r['id_usuario'] == $uid;
                  $pubBadge = $r['publico'] ? '<span class="badge bg-green-lt ms-1">'._t('Público').'</span>' : '';
                  $ownerLine = $showOwner ? '<div class="text-secondary text-truncate mt-n1">@'.$r['username'].'</div>' : '';
                  $descLine = $r['descricao'] ? '<div class="text-secondary text-truncate mt-n1">'.htmlspecialchars($r['descricao']).'</div>' : '';
                  $actionsOwner = $isOwner
                    ? '<a class="btn btn-sm" href="?page=editwordbank&id='.$r['id'].$iidParam.'">'._t('Editar').'</a>'.
                      '<a class="btn btn-sm btn-danger" onclick="delWordbank(\''.$r['id'].'\')">'._t('Apagar').'</a>'
                    : '';
                  echo '<div class="list-group-item"><div class="row align-items-center">
                    <div class="col">
                      <a href="?page=wordbank&id='.$r['id'].$iidParam.'">'.htmlspecialchars($r['titulo']).$pubBadge.'</a>
                      '.$descLine.$ownerLine.'
                      <div class="text-secondary text-truncate mt-n1">'.$r['numRefs'].' '._t('referentes').'</div>
                    </div>
                    <div class="col-auto">
                      '.($uid > 0 ? '<a class="btn btn-primary btn-sm" href="?page=wordbank&id='.$r['id'].$iidParam.'">'._t('Gerador de Palavras').'</a>' : '').'
                      <a class="btn btn-sm" href="?page=wordcompare&id='.$r['id'].$iidParam.'">'._t('Comparador de Palavras').'</a>
                      '.$actionsOwner.'
                    </div>
                  </div></div>';
                }
              }
            ?>

            <?php if ($uid > 0 && count($myBanks) > 0): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Meus bancos')?></h3>
                    </div>
                    <div class="list-group list-group-flush overflow-auto" style="max-height: 35rem">
                        <?php renderBankList($myBanks, $iidParam, $uid); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($pubBanks) > 0): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Bancos públicos')?></h3>
                    </div>
                    <div class="list-group list-group-flush overflow-auto" style="max-height: 35rem">
                        <?php renderBankList($pubBanks, $iidParam, $uid, true); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($rows) == 0): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-secondary"><?=_t('Nenhum banco de palavras ainda.')?> <?= $uid > 0 ? '<a href="?page=editwordbank&id=0">'._t('Criar um banco').'</a>' : '' ?></div>
                </div>
            </div>
            <?php endif; ?>

            </div>
          </div>
        </div>

<script>
function delWordbank(id){
  if (confirm("<?=_t('Apagar mesmo este banco?')?>")) {
        $.get("api.php?action=ajaxApagarBanco&id="+id, function(data){
            if ($.trim(data)=='ok') location.reload(true);
            else alert(data);
        });
    }
}
</script>