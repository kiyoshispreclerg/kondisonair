
<!-- PANEL START -->
<?php 
    $iid = -1;
    if ($_GET['id'] > 0) {

      $query = 'SELECT a.*, i.nome_legivel FROM artygs a LEFT JOIN idiomas i ON i.id = a.id_idioma WHERE a.id = '.$_GET['id'].';';
      $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
      $r = mysqli_fetch_assoc($result);

      $aid = $r['id'];
      $iid = $r['id_idioma'];
      $idioma = $r['nome_legivel'];
      
    } else echo '<script>window.location = "index.php";</script>';
   
?>
<input type="hidden" id="aid" value="<?=$aid?>" />
        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="index.php?page=language&iid=<?=$iid?>"><?=$idioma?></a></li>
                      <li class="breadcrumb-item"><a href="index.php?page=myarticles&iid=<?=$iid?>"><?=_t('Artigos')?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Artigo')?></a></li>
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
                        <h3 class="card-title"><?=$r['nome']?></h3>
                    </div>
                    <div class="card-body"><?=$r['texto']?></div>
                </div>
            </div>
            <!--div class="col-3">
                <div class="card">
                    <div class="card-body">
                      <div class="mb-3">info do artigo: nome, autor, likes/deslikes, última atualização, relação link, idioma</div>
                    <?php 
                    $query = 'SELECT a.* FROM artygs a 
                        WHERE a.id_pap = '.$aid.';';
                    $result2 = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));

                    if(mysqli_num_rows($result2)>0){
                      echo '<h4>Artigos relacionados</h4><div class="list-group-item">';
                      while($r2 = mysqli_fetch_assoc($result2)){
                          echo '<a href="?page=article&id='.$r2['id'].'">'.$r2['nome'].'</a>';
                          
                      }; 
                      echo '</div>';
                    };
                    ?>
                    
                  </div>
                </div>
            </div-->

            </div>
          </div>
        </div>