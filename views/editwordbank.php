


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
                            <input type="text" class="form-control" id="titulo" value="<?=$bancoDados['titulo']?>">
                        </div>

                        <select multiple class="form-select" id="filtro" title="Lista de referentes..." type="text" value="">
                        <?php 

                            $query = "SELECT r.id, b.id_lista, d.descricao 
                            FROM referentes r
                            LEFT JOIN listas_referentes b  ON r.id = b.id_referente
                                LEFT JOIN referentes_descricoes d ON d.id_referente = r.id AND id_idioma = '".$_SESSION['KondisonairUzatorDiom']."'
                            ORDER BY b.ordem;"; // palavras na lingua     WHERE b.id_lista = ".$banco."

                            // iid pra referenciar as palavras, não referentes
                            //echo $query;

                            $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 

                            while($r = mysqli_fetch_assoc($result)){
                              echo '<option value="'.$r['id'].'" '.($r['id_lista']==$banco?'selected':'').' >'.$r['descricao'].'</option>';
                              /*
                                echo '<div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto"><input type="checkbox" class="form-check-input"></div>
                                        <div class="col-auto">
                                            <a href="#">'.$r['descricao'].'
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="#" class="text-reset d-block">'.$r['descricao'].'</a>
                                            <div class="d-block text-secondary mt-n1">'.$r['descricao'].'</div>
                                        </div>
                                    </div>
                                </div>';
                                */
                            };

                        ?>
                        </select>
                    </div>
                </div>
            </div>


            </div>
          </div>
        </div>

<script>

    function salvarLista(){

        $.post('api.php?action=saveWordbank&id=<?=$banco?>', {
          refs: $("#filtro").val(),
          titulo: $("#titulo").val()
        }, function (data){ 
          if($.trim(data) == 'ok'){ 
                    window.location.reload(true);
          }else{
                    alert(data);
          }
        });
    };


$(document).ready(function(){
    createTablerSelect('filtro');
}); 

</script>