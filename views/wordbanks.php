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
                        <h3 class="card-title"><?=_t('Bancos')?></h3>
                    </div>
                    <div class="card-bodyx">
                    <div class="list-group list-group-flush overflow-auto" style="max-height: 35rem">


                    <?php 
                        $query = "SELECT l.*, (SELECT COUNT(*) FROM listas_referentes r WHERE r.id_lista = l.id) as numRefs
                            FROM wordbanks l
                          WHERE (SELECT COUNT(*) FROM listas_referentes r WHERE r.id_referente = l.id) > 0 OR id_usuario = '".$_SESSION['KondisonairUzatorIDX']."';";//." AND s.num_palavras > 0;";
                        //echo $query;
                        $result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
                        while($r = mysqli_fetch_assoc($result)){
 

                            echo '<div class="list-group-item"><div class="row align-items-center">
                                    <div class="col">
                                        <a href="?page=wordbank&id='.$r['id'].($_GET['iid']>0 ? '&iid='.$_GET['iid'] : '').'">'.$r['titulo'].'</a>
                                        <div class="text-secondary text-truncate mt-n1">'.$r['numRefs'].' '._t('referentes').' </div>
                                    </div><div class="col-auto">
                                      <a class="btn btn-primary" href="?page=wordbank&id='.$r['id'].($_GET['iid']>0 ? '&iid='.$_GET['iid'] : '').'">'._t('Gerador de Palavras').'</a>
                                      <a class="btn btn-primary" href="?page=wordcompare&id='.$r['id'].($_GET['iid']>0 ? '&iid='.$_GET['iid'] : '').'">'._t('Comparador de Palavras').'</a>'.
                                        ($_SESSION['KondisonairUzatorIDX']>0&&$_SESSION['KondisonairUzatorIDX']==$r['id_usuario']?
                                          '<a class="btn btn-sm" href="?page=editwordbank&id='.$r['id'].($_GET['iid']>0 ? '&iid='.$_GET['iid'] : '').'">'._t('Editar').'</a>'.
                                          '<a class="btn btn-sm" onclick="delWordbank(\''.$r['id'].'\')">'._t('Apagar').'</a>'
                                        :'').'
                                    </div></div></div>';
                        };

                    ?>

                    </div>
                    </div>
                </div>
            </div>

            </div>
          </div>
        </div>

<script>
function delWordbank(id){
  if (confirm("Apagar mesmo este banco?")) {
        
        $.get("api.php?action=ajaxApagarBanco&id="+id, function(data){
            if ($.trim(data)=='ok') location.reload(true);
            else alert(data);
        });

    }
}
</script>