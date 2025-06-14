
<!-- PANEL START -->
<?php 
$id_idioma = $_GET['iid'];
$id_classe = $_GET['k'];
$id_depende = 0;
if($_GET['d']>0) $id_depende = $_GET['d'] ; // apenas para criação na primeira vez
$id_subclasse = $_GET['c']; //esse é o id

if ($id_subclasse=='x') {

    //se id_depende já existe, não dar insert
    //$dep1 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM concordancias WHERE depende = ".$id_depende." AND padrao = 2;") or die(mysqli_error($GLOBALS['dblink']));


    $dep1 = mysqli_query($GLOBALS['dblink'],"SELECT * FROM itensConcordancias WHERE id = ".$id_depende." AND padrao = 2;") or die(mysqli_error($GLOBALS['dblink']));
    $d1 = mysqli_fetch_assoc($dep1);
    // id_gloss = $d1['id_gloss']
    $novoId = generateId();
    mysqli_query($GLOBALS['dblink'],"INSERT INTO concordancias SET 
        id_gloss='0', 
        id = ".$novoId.",
        nome='".$d1['nome']."',
        descricao='',
        id_idioma=".$id_idioma.",
        id_classe=".$id_classe.",
        depende=".$_GET['d'].";") or die(mysqli_error($GLOBALS['dblink']));
    // set id_subclasse pelo insertid
    $id_subclasse = $novoId;

    //header('Location: api.php?action=klazmdason&iid='.$id_idioma.'&k='.$id_classe.'&c='.$id_subclasse);  editinflections&iid=23&k=66&c=82&d=300
    echo '<script>window.location.replace("index.php?page=editinflections&iid='.$id_idioma.'&k='.$id_classe.'&c='.$id_subclasse.'&d='.$_GET['d'].'");</script>';
    // ?action=editinflections&iid=23&k=66&c=84&d=302 -> fica em branco
    // ?page=editinflections&iid=23&k=66&c=84&d=302 -> ok
    die();

}

$idioma = array();   
$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, c.nome AS nomeClasse,
    (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
    FROM classes c LEFT JOIN idiomas i ON c.id_idioma = i.id 
               WHERE c.id = ".$id_classe.";") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
    $idioma  = $r;
};

$title = '';
$id_subclasse = $id_depende;
if (!$id_subclasse>0) { 
    $id_subclasse = $id_classe;
}else{ 
    $idk = $id_subclasse;
    $i = 0;
    while($i<10){ // max nesting levels of deps
        $dep1 = mysqli_query($GLOBALS['dblink'],"SELECT c2.id_classe, ic.id_concordancia, ic.nome, c2.depende  FROM concordancias c 
            LEFT JOIN itensConcordancias ic ON ic.id = c.depende 
            LEFT JOIN concordancias c2 ON c2.id = ic.id_concordancia 
            WHERE ic.id = ".$idk.";") or die(mysqli_error($GLOBALS['dblink']));
        $d1 = mysqli_fetch_assoc($dep1); // print_r($d1);
        //$title .= '<li class="breadcrumb-item"><a href="?page=editinflections&iid='.$id_idioma.'&k='.$_GET['k'].'&c='.$_GET['c'].'&d='.$_GET['d'].'">'.$d1['nome'].'</a></li>';
        $title .= '<li class="breadcrumb-item"><a href="?page=editinflections&iid='.$id_idioma.'&k='.$_GET['k'].'">'.$d1['nome'].'</a></li>';

        if ($d1['depende']==0) break; $i++;
        $idk = $d1['id_classe'];
    };
}

if ($idioma['nome_legivel']=='' || ( $idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 ) ) {
    echo '<script>window.location = "index.php";</script>';
		exit;
}


?>
<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />
<input type="hidden" id="idOpcao" value="0" />
<input type="hidden" id="selAlterado" value="0" />




        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
					    
                <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editparts&iid=<?=$id_idioma?>"><?=$idioma['nomeClasse']?></a></li>
                      <?=$title?>
                      <li class="breadcrumb-item active"><a><?=_t('Flexões')?></a></li>
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
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Flexões')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                        <a href="#" class="btn btn-primary d-none d-sm-inline-block" onclick="novaPalavra(0)">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        <?=_t('Novo')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div id="listaDePalavras" class="list-group list-group-flush overflow-auto" style="max-height: 35rem"> </div>  
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Detalhes')?></h3>
						<div class="card-actions">
                            <div class="row">
                                <div class="col">
                                        <a href="#" id="saveBtn" class="btn btn-primary d-none d-sm-inline-block" onclick="execGuardarPalavra()">
                                        <?=_t('Salvar')?>
                                    </a>
                                </div>
                            </div>
						</div>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-3 row">
                            <div class="col-4">
                                    <label class="control-label"><?=_t('Nome')?></label>
                                    <input type="text" class="form-control" id="nome" 
                                    onchange="gravarPalavra()">
                            </div>

                            <div class="col-4">
                                    <label class="control-label"><?=_t('Gloss')?></label>
                                        <select id="gloss" class="form-select" onchange="gravarPalavra()" >
                                            <option value="0" selected><?=_t('Não especificado')?></option>
                                            <?php 
                                            $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses ;") or die(mysqli_error($GLOBALS['dblink']));
                                            while ($lang = mysqli_fetch_assoc($langs)){
                                                echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'"';
                                                //if ($idioma['id_classe'] == $lang['id']) echo ' selected';
                                                echo '>'.$lang['gloss'].' - '.$lang['descricao'].'</option>';
                                            }
                                            ?>
                                        </select>
                            </div>
                            <div class="col-4">
                                    <label class="control-label"><?=_t('Na tabela')?></label>
                                    <select id="obrigatorio" class="form-select" onchange="gravarPalavra()" >
                                        <option value="0" title="<?=_t('Deixar esta concordância nas caixas')?>"><?=_t('Não')?></option>
                                        <option value="1" title="<?=_t('Forçar esta concordância numa das duas')?>"><?=_t('Sim')?></option>
                                        <!--option value="0" title="As palavras podem ser de um ou outro tipo, ou nenhum">Não</option>
                                        <option value="1" title="As palavras são obrigatoriamente de um ou outro tipo">Sim</option-->
                                    </select>
                            </div>
                        </div>

                        <div class="mb-3">
                                <label class="control-label"><?=_t('Descrição')?></label>
                                <textarea class="form-control" id="descricao" rows="3"
                                        onchange="gravarPalavra()"></textarea>

                        </div>



                        <div class="mb-3" id="divBtnFormas">
                        <div class="form-group" >
                                <a class='btn btn-primary' href='index.php?page=editforms&iid=<?=$id_idioma?>&k=<?=$_GET['k']?>&d=<?=$_GET['d']?>&c=<?=$_GET['d']?>'><?=_t('Editar formas')?></a>
                        </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-group" >
                                <label class="control-label"><?=_t('Possíveis valores')?></label>
                                    <div id="listaOpcoes">
                                        
                                    </div>
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
function execGuardarPalavra(){
    // depende = 0 apenas para classes
    // se é subopções, depende = id da opcao superior
    $.post("api.php?action=ajaxGravarConcordancia"
        +"&cid="+ $('#idPalavra').val()+"&iid=<?=$id_idioma?>&k=<?=$id_classe?>", 
    { nome:$('#nome').val(),
        gloss:$('#gloss').val(),
        depende:'<?=$id_depende?>',
        descricao:$('#descricao').val(),
        obrigatorio:$('#obrigatorio').val()
    }, function (data){
        if ($.trim(data) > 0){
            $('#idPalavra').val($.trim(data));
            $('#selAlterado').val('0');
            //$("#listaDePalavras").load("api.php?action=listarConcordancias&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>");

            $.get("api.php?action=listarConcordancias&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>",function(d2){
                $("#listaDePalavras").html(d2);
                abrirPalavra($.trim(data));
            });
            $('#btnSave').hide();
            //abrirPalavra($.trim(data));
        }else{
            alert(data);
        };
    });
}
function gravarPalavra(){
    $('#btnSave').show();
}; 

function abrirPalavra(pid){
    $('#btnSave').hide();
	$(".divWord").removeClass("card card-active bg-primary-lt");        
	$("#row_"+pid).addClass("card card-active bg-primary-lt");
    $('#idPalavra').val(pid); 
    $('#selAlterado').val('1');
    $('#idOpcao').val(0);
    $("#opcaoEdit").modal('hide');
    $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+pid);
    $.getJSON( "api.php?action=getDetalhesConcordancia&cid=" +pid , function(data){ 
        $.each( data, function( key, val ) {
            $('#nome').val(data[0].nome); 
            $('#descricao').val(data[0].descricao); 
            $('#obrigatorio').val(data[0].obrigatorio); 
            updateTablerSelect('gloss',data[0].id_gloss);//$('#gloss').val(data[0].id_gloss); 
            //$(".chosen-select").chosen();
            //$(".chosen-select").trigger("chosen:updated");
        }); 

    });
};

function novaPalavra(){
    $('#btnSave').hide();
    $('#lastInflection').val(0);
	$(".divWord").removeClass("card card-active bg-primary-lt");   
    $('#idPalavra').val(0); 
    $('#selAlterado').val('0');
    $('#obrigatorio').val(0); 
    $('#idOpcao').val(0);
    $("#opcaoEdit").modal('hide');
    $('#listaOpcoes').html('<?=_t('Nada selecionado')?>');
    $('#nome').val(''); 
    $('#descricao').val(''); 
    updateTablerSelect('gloss','0');//$('#gloss').val(''); 
    //$(".chosen-select").chosen();
    //$(".chosen-select").trigger("chosen:updated");
    $("#listaDePalavras").load("api.php?action=listarConcordancias&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>");
};

function apagarConcordancia(pid){ 

    if (confirm("<?=_t('Apagar esta concordância e todas as suas dependências?')?>"))
    $.get("api.php?action=ajaxApagarConcordancia&id="+pid, function (data){
                    if ($.trim(data)=='ok'){
						novaPalavra();
                    }else if ($.trim(data)>0){
                        alert("Há "+$.trim(data)+" palavras com essa concodância. Não deletada.");
                    }else alert(data);
                });
};

function descerOpcao(id){
    $.get("api.php?action=ajaxMoverOpcao&id="+id+"&dir=d", function (data){
        if ($.trim(data) > 0){
            //$("#opcaoEdit").modal('hide');
            $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
        }else{
            alert(data);
        };
    });
}
function subirOpcao(id){
    $.get("api.php?action=ajaxMoverOpcao&id="+id+"&dir=u", function (data){
        if ($.trim(data) > 0){
            //$("#opcaoEdit").modal('hide');
            $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
        }else{
            alert(data);
        };
    });
}

function novaOpcao(def){
    $("#opcaoEdit").modal('show');
    $('#idOpcao').val(0);
    $('#selAlterado').val('0');
    $('#nomeOpcao').val('');
    updateTablerSelect('glossOpcao',null);//$('#glossOpcao').val(null);
    $('#padrao').val(def); 
    //$(".chosen-select").trigger("chosen:updated");
};

function editarOpcao(id,nome,gloss,padrao){
    //$('#glossOpcao').val(null);
    //if (data[0].referentes.length > 0){
        //$.each(gloss.split(","), function(i,e){
            //$("#glossOpcao option[value='" + e + "']").prop("selected", true);
        //});
    //}
    updateTablerSelect('glossOpcao',gloss.split(","));//

    $('#idOpcao').val(id);
    $('#nomeOpcao').val(nome);
    $('#padrao').val(padrao);
    $('#selAlterado').val('0');
    //$(".chosen-select").trigger("chosen:updated");
    $("#opcaoEdit").modal('show');
}

function setarOpcaoPadrao(opcao){
    if ( $('#selAlterado').val() == 1){

        $.get("api.php?action=ajaxGravarOpcaoPadrao"
            +"&op="+opcao+"&k="+$('#idPalavra').val(), function (data){
            if ($.trim(data) == 'ok'){
                $("#opcaoEdit").modal('hide');
                $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
            }else{
                alert(data);
            };
        });
    }else{

        $.get("api.php?action=ajaxGravarOpcaoPadrao"
            +"&op="+opcao+"&k="+$('#idPalavra').val(), function (data){
            if ($.trim(data) == 'ok'){
                $("#opcaoEdit").modal('hide');
                $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
            }else{
                alert(data);
            };
        });
    }


};

function apagarOpcao(id){
    if(confirm("<?=_t('Apagar esta opção e, caso tenha subformas, todas elas?')?>"))
        $.get("api.php?action=ajaxApagarOpcao&id="+id, function (data){
            if ($.trim(data) == 'ok'){
                $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
            }else{
                alert(data);
            };
        });
}

function salvarOpcao(){

    if ( $('#selAlterado').val() == 1){

        $.post("api.php?action=ajaxGravarOpcao"
                        +"&op="+ $('#idOpcao').val()+"&iid=<?=$id_idioma?>&k=<?=$id_subclasse?>", 
                    {   nome:$('#nomeOpcao').val(),
                        gloss:$('#glossOpcao').val(),
                        padrao:$('#padrao').val(),
                        conc:$('#idPalavra').val()
                    }, function (data){
                        if ($.trim(data) > 0){
                            $('#idOpcao').val($.trim(data));
                            $("#opcaoEdit").modal('hide');
                            $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
                        }else{
                            alert(data);
                        };
                    });
    }else{

        $.post("api.php?action=ajaxGravarOpcao"
            +"&op="+ $('#idOpcao').val()+"&iid=<?=$id_idioma?>&k=<?=$id_subclasse?>", 
        {   nome:$('#nomeOpcao').val(),
            gloss:$('#glossOpcao').val(),
            padrao:$('#padrao').val(),
            conc:$('#idPalavra').val()
        }, function (data){
            if ($.trim(data) > 0){
                $('#idOpcao').val($.trim(data));
                $("#opcaoEdit").modal('hide');
                $('#listaOpcoes').load("api.php?action=listarValores&iid=<?=$id_idioma?>&k=<?=$id_classe?>&d=<?=$id_depende?>&op="+ $('#idPalavra').val() );
            }else{
                alert(data);
            };
        });
    }

    
};

$(document).ready(function(){
    //$("#listaDePalavras").load("api.php?action=listarConcordancias&iid=<?=$id_idioma?>&k=<?=$id_subclasse?>&d=<?=$id_depende?>");
    //atualizarReferentes();
    createTablerSelect('gloss');
    createTablerSelect('glossOpcao',null);
    novaPalavra();
}); 
</script>

<input type="hidden" id="lastInflection" value="0">

<div class="modal modal-blur" id="opcaoEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=_t('Editar flexão')?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label"><?=_t('Nome')?></label>
                    <input type="text" class="form-control" id="nomeOpcao">
                </div>
                <div class="mb-3" >
                    <label class="form-label"><?=_t('Tipo')?></label> 
                    <select id="padrao" class="form-select">
                        <option value="1" selected><?=_t('Padrão (desmarcado)')?></option>
                        <option value="0"><?=_t('Plano (forma única)')?></option>
                        <option value="2"><?=_t('Flexionar')?></option>
                    </select>
                </div>
                <div class="mb-3" >
                    <label class="form-label"><?=_t('Gloss')?></label>
                    <select id="glossOpcao" multiple class="form-select" >
                        <option value="0" selected>
                        <?php 
                        $langs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM glosses ;") or die(mysqli_error($GLOBALS['dblink']));
                        while ($lang = mysqli_fetch_assoc($langs)){
                            echo '<option value="'.$lang['id'].'" title="'.$lang['descricao'].'"';
                            //if ($idioma['id_classe'] == $lang['id']) echo ' selected';
                            echo '>'.$lang['gloss'].' - '.$lang['descricao'].'</option>';
                        }
                        ?>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onClick="salvarOpcao()"><?=_t('Salvar')?></button>
            </div>
        </div>
    </div>
</div>