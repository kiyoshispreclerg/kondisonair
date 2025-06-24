<?php 
$frase = $_GET['id'];
if (!$frase>0) $frase = 0;

$query = "SELECT * FROM frases f
    WHERE id = ".$frase.";";
$result = mysqli_query($GLOBALS['dblink'], $query) or die(mysqli_error($GLOBALS['dblink']));
$dadosFrase = mysqli_fetch_assoc($result);

$id_idioma = $dadosFrase['id_idioma'] ?: $_GET['iid'] ?: 0;
$idioma = array();   
$result = mysqli_query($GLOBALS['dblink'],"SELECT *, (SELECT nome_legivel FROM idiomas d WHERE d.id = i.id_idioma_descricao LIMIT 1) as desc_idioma,
          (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i
          WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
    $idioma  = $r;
};

if($id_idioma > 0){
    // apenas listagem de top frases do idioma
    $breadcrumb = '<li class="breadcrumb-item"><a href="?page=language&iid='.$id_idioma.'">'.$idioma['nome_legivel'].'</a></li>';
}else if($id_usuario > 0){
    // apenas listagem de frases do usuario - edit se é o logado
    $breadcrumb = '<li class="breadcrumb-item"><a href="?page=user&uid='.$id_usuario.'">'.$usuario['nome'].'</a></li>';
}else {
    // especificar o idioma primeiro
}

// $nome_idioma
// id_idioma
$idioma_usuario = $_SESSION['KondisonairUzatorDiom'];

$id_original = $dadosFrase['id_original'] ?: $_GET['original'] ?: 0;
?>


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                    <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li><?=$breadcrumb?>
                      <li class="breadcrumb-item"><a href="?page=phrases"><?=_t('Frases')?></a></li>
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
                          <div class="col-sm-12 custom-font-<?=$eid?>" style="white-space:preserve;" id="textoMarcado"></div> 
                            <label class="form-label"><?=_t('Nome')?></label>
                            <input type="text" class="form-control" id="titulo" value="<?=$bancoDados['titulo']?>">
                        </div>
 
                    </div>
                </div>
            </div>


            </div>
          </div>
        </div>
