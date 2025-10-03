
<?php
if ( ! $_SESSION['KondisonairUzatorIDX'] > 1) {echo '<script>window.location = "index.php";</script>';
    exit;
}

// INSERT INTO `opcoes_sistema` (`id`, `opcao`, `valor`) VALUES (NULL, 'fonts_usuario', '5'); 

$usuario = array();  
$result = mysqli_query($GLOBALS['dblink'],
    "SELECT *
    FROM usuarios WHERE id = ".$_SESSION['KondisonairUzatorIDX'].";") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
    $usuario  = $r;
};
?>

<div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <?=_t('Configurações')?>
                </h2>
              </div>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="card">
              <div class="row g-0">
                <div class="col-12 col-md-3 border-end">
                  <div class="card-body">
                    <h4 class="subheader"><?=_t('Geral')?></h4>
                    <div class="list-group list-group-transparent">
                      <a href="#" class="list-group-item list-group-item-action d-flex align-items-center active"><?=_t('Minha conta')?></a>
                    </div>
                    
                  </div>
                </div>

                <div class="col-12 col-md-9 d-flex flex-column">
                  <div class="card-body">
                    <h2 class="mb-4"><?=_t('Minha conta')?></h2>
                    <!--h3 class="card-title">Profile Details</h3>
                    <div class="row align-items-center">
                      <div class="col-auto"><span class="avatar avatar-xl" style="background-image: url(./static/avatars/000m.jpg)"></span>
                      </div>
                      <div class="col-auto"><a href="#" class="btn">
                          Change avatar
                        </a></div>
                      <div class="col-auto"><a href="#" class="btn btn-ghost-danger">
                          Delete avatar
                        </a></div>
                    </div-->
                    <h3 class="card-title mt-4"><?=_t('Perfil')?></h3>
                    <div class="row g-3">
                      <div class="col-md">
                        <div class="form-label"><?=_t('Nome')?></div>
                        <input type="text" class="form-control" id="nome_completo"  value="<?=$usuario['nome_completo']?>" onchange="$('#btnSalvar').show()">
                      </div>
                      <div class="col-md">
                        <div class="form-label"><?=_t('Usuário')?></div>
                        <input type="text" class="form-control" id="usuario" value="<?=$usuario['username']?>" onchange="$('#btnSalvar').show()">
                      </div>
                      <div class="col-md">
                        <div class="form-label"><?=_t('Idioma')?></div>
                        <?php
                          echo gerarSelectIdiomas('nativo', $usuario['id_idioma_nativo'], '$(\'#btnSalvar\').show()"', true);
                        ?>
                      </div>
                    </div>

                    <div class="col-md mt-4">
                      <div class="form-label"><?=_t('Sobre')?></div>
                      <input type="text" class="form-control" id="descricao"  value="<?=$usuario['descricao']?>" onchange="$('#btnSalvar').show()">
                    </div>

                    <h3 class="card-title mt-4"><?=_t('Senha')?></h3>
                    <div>
                      <a href="#" class="btn" onClick='$("#modalPassword").modal("show")'>
                        <?=_t('Mudar minha senha')?>
                      </a>
                    </div>
                    <h3 class="card-title mt-4"><?=_t('Perfil público')?></h3>
                    <div>
                      <label class="form-check form-switch form-switch-lg">
                        <input id="publico" class="form-check-input" type="checkbox" <?php if ($usuario['publico']==1) echo 'checked'; ?> onchange="$('#btnSalvar').show()">
                        <span class="form-check-label form-check-label-on"><?=_t('Visível')?></span>
                        <span class="form-check-label form-check-label-off"><?=_t('Invisível')?></span>
                      </label>
                    </div>
                  </div>
                  <div class="card-footer bg-transparent mt-auto">
                    <div class="btn-list justify-content-end">
                      <a href="#" class="btn btn-primary" id="btnSalvar" onclick="gravarPerfil()">
                        <?=_t('Salvar')?>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>


            <?php if($_SESSION['KondisonairUzatorNivle']==100){ 
                $resop = mysqli_query($GLOBALS['dblink'],"SELECT * FROM opcoes_sistema;") or die(mysqli_error($GLOBALS['dblink']));
                while($ro = mysqli_fetch_assoc($resop)) { 
                  $op[$ro['opcao']]  = $ro['valor'];
                };
            ?>

            <div class="card mt-3">
              <div class="card-header">
                <h3 class="card-title"><?=_t('Administração')?></h3>
              </div>
              <div class="card-body row">
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Idiomas por usuário')?></label>
                    <input type="number" class="form-control" id="limite_langs" value="<?=$op['limite_langs']?>" onchange="gravarOpsons('limite_langs')">
                </div>  
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Palavras base por idioma')?></label>
                    <input type="number" class="form-control" id="palavras_base_lang" value="<?=$op['palavras_base_lang']?>" onchange="gravarOpsons('palavras_base_lang')">       
                </div>  
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Palavras total por idioma')?></label>
                    <input type="number" class="form-control" id="palavras_lang" value="<?=$op['palavras_lang']?>" onchange="gravarOpsons('palavras_lang')">       
                </div>  
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Fontes por usuario')?></label>
                    <input type="number" class="form-control" id="fonts_usuario" value="<?=$op['fonts_usuario']?>" onchange="gravarOpsons('fonts_usuario')">  
                </div> 
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Classes por idioma')?></label>
                    <input type="number" class="form-control" id="lim_lang_parts" value="<?=$op['lim_lang_parts']?>" onchange="gravarOpsons('lim_lang_parts')">  
                </div> 
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Concordâncias por idioma')?></label>
                    <input type="number" class="form-control" id="lim_conc_lang" value="<?=$op['lim_conc_lang']?>" onchange="gravarOpsons('lim_conc_lang')">  
                </div> 
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Itens por concordância')?></label>
                    <input type="number" class="form-control" id="lim_itens_conc" value="<?=$op['lim_itens_conc']?>" onchange="gravarOpsons('lim_itens_conc')">  
                </div> 
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Sons por idioma')?></label>
                    <input type="number" class="form-control" id="lim_sons_lang" value="<?=$op['lim_sons_lang']?>" onchange="gravarOpsons('lim_sons_lang')">  
                </div> 
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Sistemas de escrita por idioma')?></label>
                    <input type="number" class="form-control" id="limite_escritas_l" value="<?=$op['limite_escritas_l']?>" onchange="gravarOpsons('limite_escritas_l')">  
                </div> 

                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Listas de alteração sonora por idioma')?></label>
                    <input type="number" class="form-control" id="limite_scs_lang" value="<?=$op['limite_scs_lang']?>" onchange="gravarOpsons('limite_scs_lang')">  
                </div> 
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Listas de alteração sonora por usuário')?></label>
                    <input type="number" class="form-control" id="limite_scs_user" value="<?=$op['limite_scs_user']?>" onchange="gravarOpsons('limite_scs_user')">  
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label"><?=_t('Likes/Deslikes por usuário')?></label>
                    <input type="number" class="form-control" id="limite_likes_user" value="<?=$op['limite_likes_user']?>" onchange="gravarOpsons('limite_likes_user')">  
                </div> 
                
                <div class="mb-3 col-md-3">
                      <label class="form-label"><?=_t('Aberto para novos usuários?')?></label>
                      <select id="inscr_aberta" class="chosen-select form-control" onchange="gravarOpsons('inscr_aberta')">
                          <option value="0" <?php if ($op['inscr_aberta']==0) echo 'selected'; ?> >Não</option>
                          <option value="1" <?php if ($op['inscr_aberta']==1) echo 'selected'; ?> >Sim</option>
                      </select>
                </div>
                <div class="mb-3 col-md-3">
                      <label class="form-label"><?=_t('Idioma padrão do sistema')?></label>
                      <?php
                        echo gerarSelectIdiomas('def_lang', $op['def_lang'], 'gravarOpsons(\'def_lang\')', false);
                      ?>
                </div>
              </div>
              <div class="card-body">

                <a href="index.php?page=ipa" class="btn btn-primary"><?=_t('IPA')?></a>
                <a href="index.php?page=glosses" class="btn btn-primary"><?=_t('Glosses')?></a>
                <a href="index.php?page=referents" class="btn btn-primary"><?=_t('Referentes')?></a>
                <a href="index.php?page=users" class="btn btn-primary"><?=_t('Usuários')?></a>
              </div>

            </div>
            <script>
              function gravarOpsons(param){
                  $.get("api.php?action=ajaxGravarOption&param="+param+"&value="+$('#'+param).val(), 
                      function (data){
                      if ($.trim(data) == 'ok'){
                      }else{
                          alert(data);
                      }
                  });
              };
            </script>
            <?php } ?>

          </div>
        </div>



<script>
function gravarPerfil(){ 
    $.post("api.php?action=ajaxGravarPerfyl", 
        {   nome: $('#nome_completo').val(),
            usuario: $('#usuario').val(),
            descricao: $('#descricao').val(),
            iid: $('#nativo').val(),
            publico: document.getElementById('publico').checked ? 1 : 0,
            email: $('#email').val()
        }, function (data){
        if ($.trim(data) == 'ok'){
            $('#btnSalvar').hide();
        }else{
          if ($.trim(data)=='user') alert('<?=_t('Nome de usuário já existe')?>');
          else alert(data);
        }
    });
};

function changePassword(){
    var o = $('#velha').val();
    var n = $('#nova').val();
    if(n.length < 3){
        alert('<?=_t('Insira sua nova senha')?>');
        return false;
    };
    
    $.post("api.php?action=ajaxCavMdason",{
        o:o,
        n:n
    }, function (data){
        if(data=='ok') {
            alert('<?=_t('Senha alterada!')?>');
            $("#modalPassword").modal("hide");
        }
        else {
            alert(data);
            return false;
        }
    });
};

function gravarOpsons(param){
    $.get("api.php?action=ajaxGravarOption&param="+param+"&value="+$('#'+param).val(), 
        function (data){
        if ($.trim(data) == 'ok'){
            //alert('ok');//window.location = "dash.php?ason=opsons";
        }else{
            alert(data);
        }
    });
};

$('#btnSalvar').hide();
</script>


<div class="modal modal-blur" id="modalPassword" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered" role="document" >
		<div class="modal-content"  >
			<div class="modal-header">
				<h5 class="modal-title" id="modaltitle"><?=_t('Atualizar senha')?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body panel-body">
          <?=_t('Senha atual')?>:<input type="password" id="velha" class="form-control">
          <?=_t('Senha nova')?>:<input type="password" id="nova" class="form-control">
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onClick="changePassword();"><?=_t('Confirmar')?></button>
			</div>
		</div>
	</div>
</div>