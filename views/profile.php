

<?php 
if($_SESSION['KondisonairUzatorID']>0){
	//logado
}else{
	//deslogado
}

if (isset($_GET['uid']) && $_GET['uid']>0)
  $query = "SELECT *, DATE_FORMAT( data_cadastro,'%m/%Y') as cadastro,
    (SELECT COUNT(*) FROM sosail_sgisons WHERE id_seguido = ".$id_usuario.") as seguidores, 
    (SELECT COUNT(*) FROM sosail_sgisons WHERE id_usuario = ".$id_usuario.") as seguidos
    FROM usuarios u
      WHERE id = '".$_GET['uid']."';";
else if (isset($_GET['user']) && strlen($_GET['user'])>1)

    $query = "SELECT *, DATE_FORMAT( data_cadastro,'%m/%Y') as cadastro,
    (SELECT COUNT(*) FROM sosail_sgisons WHERE id_seguido = u.id) as seguidores, 
    (SELECT COUNT(*) FROM sosail_sgisons WHERE id_usuario = u.id) as seguidos
    FROM usuarios u
      WHERE username = '".$_GET['user']."';";
else 

  $query = "SELECT *, DATE_FORMAT( data_cadastro,'%m/%Y') as cadastro,
  (SELECT COUNT(*) FROM sosail_sgisons WHERE id_seguido = ".$_SESSION['KondisonairUzatorIDX'].") as seguidores, 
  (SELECT COUNT(*) FROM sosail_sgisons WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX'].") as seguidos
  FROM usuarios u
    WHERE id = '".$_SESSION['KondisonairUzatorIDX']."';";

    //echo $query;  

$usuario = array();   
$result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
	$usuario  = $r;
};

$id_usuario = $usuario['id'];

if (! $id_usuario > 0 || $usuario['publico'] == '0') {
    
  echo '<script>window.location = "index.php";</script>';
  exit;
}

?>


        <div class="page-header">
          <div class="container">
            <div class="row align-items-center">
              <!--div class="col-auto">
                <span class="avatar avatar-lg rounded" style="background-image: url(./static/avatars/003m.jpg)"></span>
              </div-->
              <div class="col">
                <h1 class="fw-bold"><?=$usuario['nome_completo']?></h1>
                <div class="my-2"><?=$usuario['username']?>
                </div>
                <div class="list-inline list-inline-dots text-secondary">
                  <div class="list-inline-item">
                    <!-- Download SVG icon from http://tabler-icons.io/i/map -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7l6 -3l6 3l6 -3v13l-6 3l-6 -3l-6 3v-13" /><path d="M9 4v13" /><path d="M15 7v13" /></svg>
                    <?=_t('Usuário desde')?> <?=$usuario['cadastro']?>
                  </div>
                </div>
              </div>

              <div class="col-auto ms-auto">
                <div class="btn-list" id="sgisonDiv">
                </div>
              </div>


            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row g-3">
              <div class="col">
                <ul class="timeline">

                  <li class="timeline-event">
                    <div class="timeline-event-icon"><!-- Download SVG icon from http://tabler-icons.io/i/brand-twitter  bg-twitter-lt -->
                      
                    </div>
                    <div class="card timeline-event-card">
                      <div class="card-body">
                        <h4><?=_t('Idiomas publicados')?></h4>
                        
                        <?php
                          $res2 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM idiomas WHERE id_usuario = ".$id_usuario." AND publico = 1;") or die(mysqli_error($GLOBALS['dblink']));
                          $lista = '';
                          while($r2 = mysqli_fetch_assoc($res2)){
                            $lista .= '<a class="btn btn-md btn-default" href="?page=language&iid='.$r2['id'].'">'.$r2['nome_legivel'].'</a> ';
                          }
                          echo $lista;
                        ?>

                      </div>
                    </div>
                  </li>

                </ul>
              </div>
              <div class="col-lg-4">
                <div class="row row-cards">

                  <div class="col-12">
                    <div class="card">
                      <div class="card-body">
                        <div class="card-title"><?=_t('Informações')?></div>
                        <div class="mb-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0" /><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0" /><path d="M3 6l0 13" /><path d="M12 6l0 13" /><path d="M21 6l0 13" /></svg>
                          <strong><?=$usuario['seguidores']?></strong> <?=_t('seguidores')?>
                        </div>
                        <div class="mb-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" /><path d="M12 12l0 .01" /><path d="M3 13a20 20 0 0 0 18 0" /></svg>
                          <strong><?=$usuario['seguidos']?></strong> <?=_t('seguidos')?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="card">
                      <div class="card-body">
                        <h2 class="card-title"><?=_t('Sobre')?></h2>
                        <div>
                          <p><?=$usuario['descricao']?></p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

<script>
	function sgison(val){
		$.get("?action=getLikeButton&u=<?=$id_usuario?>&val="+val,function(data){
			$("#sgisonDiv").html(data);
		})
	};
  sgison(0);
</script>