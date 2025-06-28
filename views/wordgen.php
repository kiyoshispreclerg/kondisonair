
<?php 
$banco = $_GET['id'];
$id_iidesc = $_SESSION['KondisonairUzatorDiom'];

if ($_GET['iid']>0) $id_idioma = $_GET['iid'];

$idioma = array();
$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho
            FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
               WHERE i.id = '".$id_iidesc."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};
$escritadesc = $idioma['eid'];
$fontedesc = $idioma['fonte'];
$tamanhodesc = $idioma['tamanho'];

$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, e.id as eid, e.id_fonte as fonte, e.tamanho, e.substituicao
            FROM idiomas i LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
               WHERE i.id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
$idioma  = $r;
};
$escrita = $idioma['eid'];
$fonte = $idioma['fonte'];
$tamanho = $idioma['tamanho'];
   
?>


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item active"><a href="#"><?=_t('Gerador de palavras')?></a></li>
                    </ol>
                </h2>
              </div>

            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-cards">

            <div class="col-4">
                  <div class="card sticky-top">
                    <div class="card-header">
                      <h3 class="card-title"><?=_t('Gerar palavras')?></h3>
                      <div class="card-actions">
                        <a href="#" class="btn btn-primary" onclick="aplicarGerar()">
                          <?=_t('Gerar')?>
                        </a>
                      </div>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            
                            <div>
                                <label class="form-label"><?=_t('Quantidade de palavras')?></label>
                                <input type="text" class="form-control" name="example-text-input" id="num_palavras" value="100">
                            </div>
                            <?php if (!$_GET['iid']>0){ ?>
                            <div class="mt-3">


                                  <div class="mb-3">
                                    <label class="form-check form-switch">
                                      <span class="form-check-label"><?=_t('Editar classes')?></span>
                                    </label>
                                    <textarea class="form-control nowrap" id="text_classes" spellcheck="false" style="height: 6rem !important;"><?="C = p, t, k, s, m, n\nV = a, i, u"?></textarea>
                                  </div>

                                  <div class="mb-3">
                                    <label class="form-check form-switch">
                                      <span class="form-check-label"><?=_t('Formas de sílabas')?></span>
                                    </label>
                                    <textarea class="form-control nowrap" id="text_silabas" spellcheck="false" style="height: 6rem !important;"><?="V\nCV"?></textarea>
                                  </div>
                                  
                            </div>
                            <?php }?>
                            <div class="mt-3">
                                <label class="form-label"><?=_t('Gerar para o meu idioma')?></label>
                                <select class="form-select" id="idsig" onchange="window.location.href='?page=wordgen&id=<?=$_GET['id']?>&iid='+$('#idsig').val()"><option value="0" selected><?=_t('Nenhum (manual)')?></option><?php 
                                        $oiids = mysqli_query($GLOBALS['dblink'],
                                        "SELECT i.nome_legivel, i.id as iid, e.id as eid FROM idiomas i
                                        LEFT JOIN escritas e ON e.id_idioma = i.id AND e.padrao = 1
                                        WHERE i.id_usuario = '".$_SESSION['KondisonairUzatorIDX']."';") or die(mysqli_error($GLOBALS['dblink'])); // AND buscavel = 1
                                    while($oid = mysqli_fetch_assoc($oiids)) {
                                        echo '<option value="'.$oid['iid'].'" data=e="'.$oid['eid'].'" data-n="'.$oid['nome_legivel'].'" '.($oid['iid']==$_GET['iid']?'selected':'').'>'.$oid['nome_legivel'].'</option>';
                                    };
                                    ?>
                                </select>
                                <label class="form-label text-secondary"><?=_t('Com classes, sílabas e pesos configurados na tela Sílabas.')?></label>
                            </div>
                        </div>
                    </div>
                  </div>
              </div> 

            <div class="col-8">
                  <div class="card sticky-top">
                    <div class="card-header">
                      <h3 class="card-title"><?=_t('Resultado')?></h3>
                      <div class="card-actions">
                        <a href="#" class="btn btn-primary" onclick="paraAlterador()">
                          <?=_t('Simular alterações')?>
                        </a>
                      </div>
                    </div>
                    <div class="card-body">

                        <div class="mb-3  " id="divnp">
                            
                        </div>
                    </div>
                  </div>
              </div> 


            </div>
          </div>
        </div>

<script>

var words;

function aplicarGerar(){
    words = '';
    $('.genPal').remove();

    $.post("?action=getKWG&iid=<?=$id_idioma?>&count="+$("#num_palavras").val(), {
      <?php if (!$_GET['iid']>0){ ?>
        classes: document.getElementById('text_classes').value,
        silabas: document.getElementById('text_silabas').value
        <?php } ?>
    }, function (data){
        words = data;
        $.trim(data).split("\n").forEach(el => {
            $("#divnp").append('<span class="genPal btn" id="'+el+'" draggable="true" ondragstart="dragstartHandler(event)">'+el+'</span>');
        });
	});
};

formatarTablerSelect('idsig',null);

function paraAlterador(){
    let classes = document.getElementById('text_classes').value;
    let rewrites = '';
    window.location.replace("index.php?page=changer&words=" + btoa(words) + "&classes=" + btoa(classes) + "&rewrites=" + btoa(rewrites) );
}
</script>