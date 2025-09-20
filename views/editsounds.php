<!-- PANEL START -->
<?php 
    $id_idioma = $_GET['iid'];
    $idioma = array();   
    $result = mysqli_query($GLOBALS['dblink'],"SELECT *,
    (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i
                WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
                // (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    while($r = mysqli_fetch_assoc($result)) { 
        $idioma  = $r;
    };

    if ($idioma['nome_legivel']=='' || ($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 )) {
        echo '<script>window.location = "index.php";</script>';
		exit;
    }

?>

<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />
<input type="hidden" id="tmpx" value="0" />
<input type="hidden" id="tmpy" value="0" />
<input type="hidden" id="tmpz" value="0" />

<style>

	.panelpal {
		/*border-radius: 10px;*/
		/*background-color: black;*/
		padding: 8px;
		margin-bottom: 4px;
	}
	.panelpal div {
		margin-bottom: 0px !important;
	}
</style>


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Sons')?></a></li>
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
                          <h3 class="card-title"><?=_t('Sons')?></h3>
                          <div class="card-actions">

                                <?php
                                    //xxxxx test tiposSom 
                                    $res = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom;") or die(mysqli_error($GLOBALS['dblink']));
                                    //$res = mysqli_query($GLOBALS['dblink'],"SELECT * FROM tiposSom WHERE id_idioma = ".$id_idioma) or die(mysqli_error($GLOBALS['dblink']));

                                    if (mysqli_num_rows($res)>0){
                                      echo '<select id="selTabela" class="form-select"  onchange="carregaTabela()">';
                                      while($r = mysqli_fetch_assoc($res)) {
                                          echo '<option value="'.$r['id'];
                                          if ($r['id']==$_GET['tipo']) echo ' selected ';
                                          if ($r['id']>3) echo '">'.$r['titulo'].'</option>';
                                          else echo '">'._t($r['titulo']).'</option>';
                                      }
                                      //echo '<option value="0">Adicionar tabela</option>';
                                      echo '</select>';
                                    }else{
                                        //echo '<a class="btn btn-primary" onclick="addTabela()">Nova tabela</a>';
                                    }
                                ?>                                
                          </div>
                      </div>
                      <div class="card-bodyx">
                        <div class="form-group col-sm-12" id="tabelaView" style="overflow: auto;"></div>
                      </div>
                  </div>
              </div>

              <div class="col-3">
                  <div class="card">
                      <div class="card-header">
                          <h3 class="card-title"><?=_t('Opções')?></h3>

                          <div class="card-actions">
                          <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" onchange="$('.progress').toggle()">
                                    <span class="form-check-label"><?=_t('Mostrar pesos')?></span>
                                </label>
                                </div>
                      </div>
                      <div class="card-body">
                        <div class="form-group" id="editView">
                        
                        </div>
                      </div>
                  </div>
              </div>
            </div>
          </div>
        </div>




<script>
function addTabela(nome = '', cod = ''){
    // modal pra dar nome e tipo (cons, vogal, extras)
    $('#nomeTabela').val(nome);
    $("#modal-nometabela").modal('show');
};
function execSalvarTabela(){
    var nome = $('#nomeTabela').val();
    if (nome.length < 2){
        alert('<?=_t('Insira um nome (exemplo: Consoantes)')?>');
        return false;
    };

    $.get("api.php?action=ajaxSetTabelaSons&iid=<?=$id_idioma?>&id="+$('#selTabela').val()+
        "&nome="+nome+"&cod="+cod, function (data){
        // reload + tipo= trim data
        window.location.href = window.location.href + '&tipo=' + $.trim(data);
        $("#modal-tecla").modal('hide');
    });
}
function moverSom(id){
    $.get("api.php?action=carregarMoverSom&t="+$('#selTabela').val()+"&iid=<?=$id_idioma?>&id="+id, function (data){
        $("#editView").html(data);
    });
};
function moverSomPara(dim,ids){
    var pos = $('#sel_mpos_'+dim).val();
    alert(dim + ' ' + pos);

    return;
    $.get("api.php?action=ajaxExecMoverSom&id="+ids+"&dir="+dir, function (data){
        $("#editView").html(data);
        carregaTabela();
    });
}
function carregaTabela(){
    if ($('#selTabela').val()==0) {
      $("#editView").html('');
      addTabela();
    }else{
      $.get("api.php?action=carregarTabelaSons&t="+$('#selTabela').val()+"&iid=<?=$id_idioma?>", function (data){
          $("#tabelaView").html(data);
          $.get("api.php?action=carregarEdicaoSons&t="+$('#selTabela').val()+"&iid=<?=$id_idioma?>", function (data){
              $("#editView").html(data);
          });
      });
    }
};
function apagarDimensao(dim,pos){ 
    if (confirm("<?=_t('Remover esta dimensão da tabela?')?>"))
    $.get("api.php?action=ajaxApagarDimensao&iid=<?=$id_idioma?>&pos="+pos+"&dim="+dim+"&t="+$('#selTabela').val(), function (data){
        carregaTabela();
    });
}
function adicionarEmDimensao(dim){
    var nome = $("#sel_pos_"+dim+" option:selected").text();
    var pos = $('#sel_pos_'+dim).val();
    $.get("api.php?action=ajaxAdicionarDimensao&iid=<?=$id_idioma?>&pos="+pos+"&dim="+dim+"&nome="+nome, function (data){
        carregaTabela();
    });
}
function editarCelula(x,y,z,a){
    $('#tmpx').val(x);$('#tmpy').val(y);$('#tmpz').val(z);
    $.get("api.php?action=carregarEdicaoCelula&iid=<?=$id_idioma?>&x="+x+"&y="+y+"&z="+z+"&t="+$('#selTabela').val(), function (data){
        $("#editView").html(data);
        $(".cell__").removeClass("card card-active bg-primary-lt");        
        $("#cell_"+x+"_"+y).addClass("card card-active bg-primary-lt");
    });
}
function adicionarSom(){
    var id = $('#sel_i').val();
    if (id==0) {
        criarSom($('#tmpx').val(),$('#tmpy').val(),$('#tmpz').val()); //xxxxx
    }else if (id>0){
        $.post("api.php?action=ajaxEditarSom&iid=<?=$id_idioma?>&id="+id+"&t="+$('#selTabela').val()+"&ignorar="+$('#ignorar').val(), {
          textosAtualizar:$('#textosAtualizar').val(),
          textosIgnorar:$('#textosIgnorar').val()
        }, function (data){
            if ($.trim(data) > 0) {
              carregaTabela();
              $("#modalChecagem").modal("hide");
            }else{
                let resp = $.trim(data).split('|');

                if (resp[0] == 'palavras'){
                    $("#titleModalChecagem").html( '<?=_t('O que fazer com as palavras que contêm esse som?')?>' );
                    $('#textosAtualizar').val( '0' );
                    $('#textosIgnorar').val( '0' );
                    $("#listaTextos").val( resp[1] );
                    $("#divListaChecagem").html( resp[2] );
                    $("#modalChecagem").modal("show");
                    
                }else if (resp[0] == 'textos'){
                    $("#titleModalChecagem").html( '<?=_t('O que fazer com textos que contêm essa palavra?')?>' );
                    $('#textosAtualizar').val( '0' );
                    $('#textosIgnorar').val( '0' );
                    $("#listaTextos").val( resp[1] );
                    $("#divListaChecagem").html( resp[2] );
                    $("#modalChecagem").modal("show");
                }else if (resp[0] == 'frases'){
                    $("#titleModalChecagem").html( '<?=_t('O que fazer com frases que contêm essa palavra?')?>' );
                    $('#textosAtualizar').val( '0' );
                    $('#textosIgnorar').val( '0' );
                    $("#listaTextos").val( resp[1] );
                    $("#divListaChecagem").html( resp[2] );
                    $("#modalChecagem").modal("show");
                }else{
                    alert(data);
                };
            }
        })
    }else{
        $.get("api.php?action=ajaxCriarSomPersonalizado2&iid=<?=$id_idioma?>&x="+$('#tmpx').val()+"&y="
            +$('#tmpy').val()+"&z="+$('#tmpz').val()+"&t="+$('#selTabela').val()+"&ids="+id, function (data){
              if($.trim(data)>0){
                carregaTabela();
                editarCelula(x,y,z,'');//$("#ipaEdit").modal('hide');
              }else alert(data);
        });
    }
}

function removerSom(id,pers=0,skipConfirmation = false){
    $('#tempRemId').val(id); $('#tempRemPers').val(pers);
    var p = '';
    if (pers > 0) p = '&p=1';
    if (skipConfirmation || confirm("<?=_t('Remover este som?')?>"))
    $.post("api.php?action=ajaxEditarSom&iid=<?=$id_idioma?>&id="+id+"&r=1&t="+$('#selTabela').val()+p, {
      textosAtualizar:$('#textosAtualizar').val(),
      textosIgnorar:$('#textosIgnorar').val()
    }, function (data){
        if($.trim(data)=='deletado'||$.trim(data)=='removido') {
          carregaTabela();
          $("#modalChecagem").modal("hide");
        } else {
          let resp = $.trim(data).split('|');

          if (resp[0] < 0){ //xxxxx ?????????
              let rep = resp[0];
              rep = rep.substring(1);
              $('#resp').val(rep);
              $('#resp1').val(resp[1]);
              $('#resp2').val(resp[2]);
              $('#palRepText').html( 'Já existe uma palavra com a mesma pronúncia ou romanização: \n<br><strong>\\'+resp[1]+
                  '\\</strong> \n<br>'+resp[2]+'. \n<br><br>Deseja salvar mais uma nova palavra assim mesmo?' );
              $("#ignorar").val(ignorar);
              $("#modalPalRep").modal("show");
          
          }else if (resp[0] == 'palavras'){
              $("#titleModalChecagem").html( 'O que fazer com as palavras que contêm esse som?' );
              $('#textosAtualizar').val( '0' );
              $('#textosIgnorar').val( '0' );
              $("#listaTextos").val( resp[1] );
              $("#divListaChecagem").html( resp[2] );
              $("#modalChecagem").modal("show");
          
          }else if (resp[0] == 'textos'){
              $("#titleModalChecagem").html( 'O que fazer com textos que contêm essa palavra?' );
              $('#textosAtualizar').val( '0' );
              $('#textosIgnorar').val( '0' );
              $("#listaTextos").val( resp[1] );
              $("#divListaChecagem").html( resp[2] );
              $("#modalChecagem").modal("show");
          }else if (resp[0] == 'frases'){
              $("#titleModalChecagem").html( 'O que fazer com frases que contêm essa palavra?' );
              $('#textosAtualizar').val( '0' );
              $('#textosIgnorar').val( '0' );
              $("#listaTextos").val( resp[1] );
              $("#divListaChecagem").html( resp[2] );
              $("#modalChecagem").modal("show");
          }else{
              alert(data);
          };
        }
    });
}
function salvarCelula(){
    $.get("api.php?action=carregarEdicaoSons&t="+$('#selTabela').val()+"&iid=<?=$id_idioma?>", function (data){
        $("#editView").html(data);
        $(".cell__").removeClass("card card-active bg-primary-lt");  
    });
}
function salvarTecla(id,tecla,inventario,peso){
    if (tecla=="*") tecla = "'";
    
    $("#varidtecla").val(id);
    $("#varinventario").val(inventario);
    $("#pesot").val(peso);
    $("#nomet").val(tecla);
    $("#modal-tecla").modal('show');
}
function execSalvarTecla(){
    var inventario = $('#varinventario').val();
    var id = $('#varidtecla').val();
    var peso = $('#pesot').val();

    var tecla = $('#nomet').val();
    //alert(tecla.length); return false;
    if(!tecla){
        alert('Insira um ou dois caracteres!');
        return false;
    };
    /*
    if (tecla.length > 2){
        alert('Insira um ou dois caracteres!');
        return false;
    };
    */
    
    $.post("api.php?action=ajaxEditarTeclaIpa&id="+id+"&ipa="+inventario+"&k="+tecla,{k:tecla,p:peso}, function (data){

      //xxxxx CONFERIR SE ALTEROU PRA ROMANIZACAO

      if ($.trim(data) == '1'){
          carregaTabela(); 
          $("#modal-tecla").modal('hide');
      }else{
        alert(data);
      }
    });
}
function renomearDimensao(id,nome){ 
    $('#idDim').val(id);
    $('#nomeDim').val(nome);
    $("#modal-renomeardim").modal('show');
}
function execRenomearDim(){

    var id = $('#idDim').val();
    var nome = $('#nomeDim').val();

    if(!nome){
        alert('<?=_t('Insira um nome!')?>');
        return false;
    };
    if(!id){
        return false;
    };

    $.post("api.php?action=ajaxRenomearDimensao&id="+id, 
        {nome: nome}, function (data){
        carregaTabela();
        $("#modal-renomeardim").modal('hide');
    });
}
function criarSom(x,y,z){

    $('#varx').val(x);
    $('#vary').val(y);
    $('#varz').val(z);

    $("#modal-criarsom").modal('show');
}
function execCriarSom(){

    var x = $('#varx').val();
    var y = $('#vary').val();
    var z = $('#varz').val();

    var ipa = $('#ipa').val();
    var nome = $('#nome').val();
    if(!nome){
        $.alert('Insira um nome!');
        return false;
    };
    if(!ipa){
        $.alert('Insira o caractere IPA!');
        return false;
    };
    //confirm: pede nome e ipa
    $.post("api.php?action=ajaxCriarSomPersonalizado&iid=<?=$id_idioma?>&x="+x+"&y="+y+"&z="+z+"&t="+$('#selTabela').val(), 
        {ipa: ipa, nome: nome}, function (data){
          if($.trim(data)>0){
            carregaTabela();
            editarCelula(x,y,z,'');//$("#ipaEdit").modal('hide');
            $("#modal-criarsom").modal('hide');
          }else alert(data);
    });
}
$(document).ready(function(){
    carregaTabela();
});

function dragstartHandler(ev) {
  ev.dataTransfer.setData("text", ev.target.id);
} 
function dragoverHandler(ev) {
  ev.preventDefault();
}
function dropHandler(ev) {
    ev.preventDefault();
    const data = ev.dataTransfer.getData("text");

    // alert("from "+data+" to "+ev.target.id); return;

    $.post("api.php?action=ajaxMoverSom&iid=<?=$id_idioma?>&t="+$('#selTabela').val(), 
        {from: data, to: ev.target.id}, function (data){
        carregaTabela();
    });

}
</script>

<input type="hidden" id="varx" />
<input type="hidden" id="vary" />
<input type="hidden" id="varz" />
<input type="hidden" id="varidtecla" />
<input type="hidden" id="varinventario" />
<input type="hidden" id="varpeso" />

<div class="modal modal-blur" id="modal-criarsom" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Criar')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Nome')?></label>
            <input type="text" class="form-control" placeholder="<?=_t('Nome (Ex.: Voiced velar nasal)')?>" id="nome" />
        </div>
        <div class="mb-3">
            <label class="form-label">IPA</label>
            <input type="text" class="form-control" placeholder="<?=_t('IPA (Ex.: ŋ)')?>" id="ipa"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execCriarSom()"><?=_t('Criar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" id="modal-tecla" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Romanização')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Tecla(s)')?></label>
            <input type="text" class="form-control" placeholder="<?=_t('Entrada (Ex.: á)')?>" id="nomet" />
            <span class="text-secondary"><?=_t('Tecla(s) a ser(em) digitada(s) nos campos pronúncia para virar este fonema.')?></span>
            <span class="text-secondary"><?=_t('Para mais de uma tecla/combinação para o mesmo som, separe-os com espaços.')?></span>
            <span class="text-secondary"><?=_t('Este(s) será(ão) o(s) caractere(s) usado(s) também nos alteradores sonoros.')?></span>
            <span class="text-secondary"><?=_t('Não use teclas cujos símbolos já sejam algum som diferente na tabela!')?></span>
        </div>
        <div class="mb-3">
            <label class="form-label"><?=_t('Peso')?></label>
            <input type="text" class="form-control" id="pesot" />
            <span class="text-secondary"><?=_t('Um número para indicar sons mais e menos recorrentes no idioma.')?></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execSalvarTecla()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" id="modal-renomeardim" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Renomear dimensão')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Nome')?></label>
            <input type="text" placeholder="<?=_t('Nome (Ex.: Glottal)')?>" id="nomeDim" class="form-control" value="">
            <input type="hidden"id="idDim">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execRenomearDim()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" id="modal-nometabela" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Nome da tabela')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <input type="text" placeholder="<?=_t('Nome (Ex.: Consoantes)')?>" id="nomeTabela" class="form-control" value="">
            <input type="hidden"id="idDim">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execSalvarTabela()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" id="modalChecagem" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-md modal-dialog-centered" role="document" >
		<div class="modal-content"  >
			<div class="modal-header">
				<h5 class="modal-title" id="titleModalChecagem"></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<input type="hidden" id="tempRemId" value="0"/>
			<input type="hidden" id="tempRemPers" value="0"/>
			<input type="hidden" id="textosIgnorar" value="0"/>
			<input type="hidden" id="textosAtualizar" value="0"/>
			<input type="hidden" id="ignorarReps" value="0"/>
			<input type="hidden" id="listaTextos" value="0"/>
			<div class="modal-body panel-body" id="divListaChecagem"></div>
			<div class="modal-footer">
				<button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close"><?=_t('Cancelar')?></button>
				<button type="button" class="btn btn-primary" onClick="$('#textosAtualizar').val( $('#listaTextos').val() );removerSom( $('#tempRemId').val(), $('#tempRemPers').val(), true )"><?=_t('Atualizar')?></button>
				<button type="button" class="btn btn-primary" onClick="$('#textosIgnorar').val( $('#listaTextos').val() );removerSom( $('#tempRemId').val(), $('#tempRemPers').val(), true )"><?=_t('Ignorar')?></button>
			</div>
		</div>
	</div>
</div>