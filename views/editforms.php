
<!-- PANEL START -->
<?php 
if (!$_GET['pid']>0) $_GET['pid'] = 0;
if (!$_GET['iid']>0) $_GET['iid'] = '';
if (!$_GET['k']>0) $_GET['k'] = ''; // xx
if (!$_GET['d']>0) $_GET['d'] = ''; // xx
if (!$_GET['c']>0) $_GET['c'] = '';
$id_idioma = $_GET['iid'];
$id_classe = $_GET['k'];
$id_depende = 0;


if ($_GET['pid']>0) {
    $result = mysqli_query($GLOBALS['dblink'],"SELECT * from palavras
                   WHERE id = ".$_GET['pid'].";") or die(mysqli_error($GLOBALS['dblink']));
    $palavraa = mysqli_fetch_assoc($result);             
    $id_classe = $palavraa['id_classe'];
    $id_idioma = $palavraa['id_idioma'];
    if ($palavraa['id_forma_dicionario']>0)  $_GET['pid'] = $palavraa['id_forma_dicionario'];
}

if($_GET['d']>0) $id_depende = $_GET['d'] ; // apenas para criação na primeira vez
$id_subclasse = $_GET['c']; //esse é o id

$idioma = array();   
$result = mysqli_query($GLOBALS['dblink'],"SELECT i.*, c.nome AS nomeClasse,
        (SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab
        FROM classes c LEFT JOIN idiomas i ON c.id_idioma = i.id 
               WHERE c.id = ".$id_classe.";") or die(mysqli_error($GLOBALS['dblink']));
while($r = mysqli_fetch_assoc($result)) { 
    $idioma  = $r;
};
if ($idioma['romanizacao']=='1') $romanizacao = 1;
$motor = 'ksc';

$title = '';
$id_subclasse = $id_depende;
if (!$id_subclasse>0) { 
    $id_subclasse = $id_classe;
}else{ 

    $idk = $id_subclasse;
    $i = 0;
    while($i<10){ // max level os deps
        $dep1 = mysqli_query($GLOBALS['dblink'],"SELECT c2.id_classe, ic.id_concordancia, c.nome, c2.depende  FROM concordancias c 
            LEFT JOIN itensConcordancias ic ON ic.id = c.depende 
            LEFT JOIN concordancias c2 ON c2.id = ic.id_concordancia 
            WHERE c.id = ".$idk.";") or die(mysqli_error($GLOBALS['dblink']));
        $d1 = mysqli_fetch_assoc($dep1);
        if ($d1['depende']==0) break; $i++;
        $idk = $d1['id_classe'];
        $title = ' > <a >'.$d1['nome'].'</a>'.$title;

    };
}

if ($idioma['nome_legivel']=='' || ( $idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 )) {
    echo '<script>window.location = "index.php";</script>';
		exit;
}

$inputsNativos = '';
$escrita = 0;
$scriptAutoSubstituicao = '';
$autoSubsLoadScript = '';
$fonte = 'notosans';
$langs = mysqli_query($GLOBALS['dblink'],"SELECT e.*, e.padrao as padr, f.arquivo as fonte FROM escritas e 
    LEFT JOIN fontes f ON f.id = e.id_fonte
    WHERE id_idioma = ".$id_idioma." ORDER BY e.padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));
while($e = mysqli_fetch_assoc($langs)){
    $autoon = '';
    $autoSubsLoadScript .= 'soundsChanged= '.getLastChange('autosubstituicoes',$e['id']).';if ( soundsChanged > localStorage.getItem("k_autosubs_updated_'.$e['id'].'") ) loadAutoSubstituicoes(\''.$e['id'].'\',soundsChanged, true);';
    if($e['id_fonte']== 3){

        if($e['substituicao']==1){
            $autoon = ' ('._t('Automático').')';
        }
        
        $inputsNativos .= '<div class="mb-3">
                <label class="form-label">'.$e['nome'].$autoon.'</label>
                <input type="hidden" class="escrita_nativa" id="escrita_nativa_'.$e['id'].'" />
                <div class="form-control editable-drawchar" id="drawchar_editable_'.$e['id'].'" contenteditable="true" data-eid="'.$e['id'].'" data-fonte="'.$e['id_fonte'].'" data-tamanho="'.$e['tamanho'].'"></div>
            </div>';
    }else{
        if($e['substituicao']==1){

            $scriptAutoSubstituicao .= 'let data2 = getAutoSubstituicao("'.$e['id'].'",data);
                if (data2 == "-1") $("#escrita_nativa_'.$e['id'].'").val( "" );
                else if(data2.length > 0) $("#escrita_nativa_'.$e['id'].'").val( data2 );';

            $autoon = ' ('._t('Automático').')';
        }

        $inputsNativos .= '<div class="mb-3">
                    <label class="form-label">'.$e['nome'].'</label>
                    <input type="text" class="form-control escrita_nativa custom-font-'.$e['id'].'" id="escrita_nativa_'.$e['id'].'" ';
        if($e['checar_glifos']==1) $inputsNativos .= ' onchange="checarNativo(this,\''.$e['id'].'\')"';
        
        $inputsNativos .= ' placeholder=""></div>';
        if($e['padr']==1) {
            $escrita = $e['id'];
            $fonte = $e['fonte'];
        }
    }
    
}

// ver se tem mais de 2 dimensões, pra mostrar combos de seleções

$result = mysqli_query($GLOBALS['dblink'],"SELECT nome, id, 
        (SELECT GROUP_CONCAT(i.nome, ',', i.id, ',', i.padrao ORDER BY i.ordem SEPARATOR '|') FROM itensConcordancias i WHERE id_concordancia = concordancias.id) as itensNomes,
        (SELECT COUNT(*) FROM itensConcordancias WHERE id_concordancia = concordancias.id) as listaItens 
        FROM concordancias 
        WHERE id_idioma = ".$id_idioma." AND id_classe = ".$id_classe." 
        AND depende = ".($id_depende>0?$id_depende:0)." ORDER BY obrigatorio DESC, listaItens DESC;") or die('1868'.mysqli_error($GLOBALS['dblink']));

if (mysqli_num_rows($result)>2) {

    $extraCombos = '<div class="card mb-3" id="extrass"><div class="card-body">';
    $extraDropzone = '<div class="card mb-3" style="display:none" id="extraDropzone" onclick="$(\'#extraDropzone\').hide();$(\'#extrass\').show()"><div class="list-group list-group-flush">';
    $x=0;
    while ($r = mysqli_fetch_assoc($result)) {

        if($x > 1){
            //echo $r['itensNomes'];
            $extraCombos .= '<h4>'._t('Mais dimensões').'</h4>'.$r['nome'].'<select class="form-select dimExtra" id="dimExtra'.$r['id'].'" onchange="carregaTabela()">';
            //$extraDropzone .= '<div   >';

            $opts = explode("|",$r['itensNomes']);
            foreach ($opts as $opt){
                $op = explode(",",$opt);
                $extraCombos .= '<option value="'.$op[1].'" '.($op[2]=='1'?'selected':'').'>'.$op[0].($op[2]=='1'?' ('._t('Padrão').')':'').'</option>';
                $extraDropzone .= '<span class="list-group-item list-group-item-action" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)" id="'.$op[1].'">'.$op[0].($op[2]=='1'?' ('._t('Padrão').')':'').'</span>';
            }


            $extraCombos .= '</select>';
            //$extraDropzone .= '</div>';
        }

        $x++;
    };

    $extraCombos .= '</div></div>';
    $extraDropzone .= '</div></div>';
};

?>
<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="<?=$_GET['pid']>0?'0':'-1'?>" />
<input type="hidden" id="idFormaDicionario" value="0" />
<input type="hidden" id="idOpcao" value="0" />
<input type="hidden" id="selAlterado" value="0" />
<input type="hidden" id="c1" value="0" />
<input type="hidden" id="c2" value="0" />
<input type="hidden" id="i1" value="0" />
<input type="hidden" id="i2" value="0" />
<input type="hidden" id="gen" value="0" />





<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
    <div class="row g-2 align-items-center">
        <div class="col">
        <h2 class="page-title">
            <ol class="breadcrumb breadcrumb-arrows">
                <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                <?php if ($_GET['pid']>0) { ?>
                <li class="breadcrumb-item"><a href="?page=editlexicon&iid=<?=$id_idioma?>">Léxico</a></li>
                <li class="breadcrumb-item"><a href="?page=editword&iid=<?=$id_idioma?>&pid=<?=$_GET['pid']?>">Palavra</a></li>
                <?php }else{ ?>
                <li class="breadcrumb-item"><a href="?page=editparts&iid=<?=$id_idioma?>"><?=$idioma['nomeClasse']?></a></li>
                <li class="breadcrumb-item"><a href="?page=editinflections&iid=<?=$id_idioma?>&k=<?=$_GET['k']?>">Flexões</a></li>
                <?php } ?>
                <li class="breadcrumb-item active"><a><?=_t('Formas')?></a></li>
            </ol>
        </h2>
        </div>
        
    </div>
    </div>
</div>
<!-- Page body -->
<div class="page-body">

    <div class="container-xl appholder">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card placeholder-glow">
                    <div class="card-body">
                        <div class="placeholder col-9 mb-3"></div>
                        <div class="placeholder placeholder-xs col-10"></div>
                        <div class="placeholder placeholder-xs col-11"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xl appLoad">
    <div class="row row-deckx row-cards">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?=_t('Tabela de formas')?></h3>
                    <div class="card-actions">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="lpc" onchange="toggleListaLonga()">
                            <span class="form-check-label"><?=_t('Página longa')?></span>
                        </label>

                        <?php if (false){ // se tiver generos, e se der pra colocar na tabela ?>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="lpc" onchange="toggleListaLonga()">
                            <span class="form-check-label"><?=_t('Página longa')?></span>
                        </label>
                        <?php }; ?>

                    </div>
                </div>
                <div class="card-bodyx listaLonga" id="tabelaFlexoes" style="overflow: auto;">
                </div> 
            </div>
        </div>

        <?php if ($_GET['pid']>0) { ?>

        <div class="col-md-3"><div class="sticky-top">
            <?=$extraDropzone?>
            <?=$extraCombos?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><?=_t('Detalhes')?></h3>
                </div>
                <div class="card-body" id="detalhesPalavra" style="display:none">
                    <div>
                        
                        <div class="mb-3">
                                <label class="form-label"><?=_t('Pronúncia')?>* <a class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" href="#offcanvasPronBtns" role="button" aria-controls="offcanvasEnd" onclick="loadPronDiv()"> (inserir sons) </a></label>
                                <input type="text" class="form-control" id="pronuncia" 
                                onchange="checarPronuncia(this,'<?=$id_idioma?>')"
                                placeholder="Palavra no próprio idioma">
                        </div>
                        <?php if ($romanizacao){ ?>
                        <div class="mb-3">
                                <label class="form-label"><?=_t('Romanização')?></label>
                                <input type="text" class="form-control" id="romanizacao" 
                                onchange="checarRomanizacao(this,'<?=$id_idioma?>')"  placeholder="Palavra no alfabeto latino">
                        </div>
                        <?php } 
                            echo $inputsNativos;
                        ?>
                        
                        <div class="mb-3">
                                <label class="form-label"><?=_t('Significado')?></label>
                                <input type="text" class="form-control" id="significado" 
                                onchange="showGravarPalavra()">
                        </div>
                        <div class="mb-3">
                            <select id="irregular" class="form-select" onchange="showGravarPalavra()"  >
                                <option value="0" selected><?=_t('Regular')?></option>
                                <option value="1" ><?=_t('Irregular')?></option>
                            </select>
                        </div>
                        <div class="mb-3" >
                            <a class="btn btn-primary pull-right"  id="btnGravarPalavra" onClick="gravarPalavra()"><?=_t('Salvar')?></a>
                        </div>
                
                    </div>


                    
                </div> 
            </div>


            <div class="card mb-3">
                <div class="card-header nvt">
                    <h3 class="card-title"><?=_t('Formas órfãs')?></h3>
                    <div class="card-actions">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" onchange="$('.nao-vazio').toggle();">
                            <span class="form-check-label"><?=_t('Ver todas')?></span>
                        </label>
                    </div>
                </div>
                <div class="card-header ntr" style="display:none" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)" id="lixo">
                    <div class="card-actions">
                        <a class="btn btn-danger d-none d-sm-inline-block" >
                            <?=_t('Drope aqui para remover da tabela')?>
                        </a>
                    </div>
                </div>
                <div class="card-body" id="divOrfans">
                    
                </div> 
            </div>


        </div></div>

        <?php }else{ ?>

        <div class="col-md-3"><div class="sticky-top">
            <?=$extraDropzone?>
            <?=$extraCombos?>
            <div class="card" >
                <div class="card-header">
                    <h3 class="card-title"><?=_t('Detalhes')?></h3>
                    <div class="card-actions">
                        <a class="btn btn-primary" data-bs-toggle="offcanvas" href="#offcanvasExtras" role="button" aria-controls="offcanvasExtras" onclick="loadExtras()">
                        Mais
                        </a>
                    </div>
                </div>
                <div class="card-body" id="detalhesPalavra" style="display:none">

                        <div class="mb-3">
                                <label class="control-label"><?=_t('Regras')?></label> 
                                <textarea class="form-control mb-3" rows=10 id="regra_pronuncia" onkeyup="showGravarFlexao()"></textarea>
                                <span id="extraPanel"><?php echo 'Categorias:<br>'.formatCategoriesAsButtons(getSCHeader('ksc',$id_idioma,'cats')); ?></span>
                        </div> 

                        <div class="mb-3">
                                <a class="btn btn-primary pull-right"  id="btnGravarFlexao" onClick="gravarFlexao()"><?=_t('Salvar')?></a>
                        </div>
                    
                </div> 
            </div>
        </div></div>

        <?php } ?>

    </div>
    </div>
</div>
<input type="hidden" id="tmpInput">
<span style="display:none" id="tmpSpan"></span>
<style>
.listaLonga {
    max-height: 35rem
}
.nao-vazio {
    display:none;
}
</style>
<script> 
var defCats = `<?=getSCHeader('ksc',$id_idioma,'cats')?>`;

function reloadDimensoes(l,este){ alert('to do reload dimensoes'); return;

    alert( 'a fazer '+$(este).val() );
}

function gravarFlexao(){
    if ($('#regra').val()=='') return;

    $.post("api.php?action=salvarFlexao&id="+$('#idPalavra').val(), 
    {   nome:'',//$('#nome').val(),
        motor:'ksc', //$('#sel_motor_sc').val(),
        regra_romanizacao:'',//$('#regra_romanizacao').val(),
        regra_pronuncia:$('#regra_pronuncia').val()
    }, function (data){
        if ($.trim(data) > 0){
            //$('#idPalavra').val($.trim(data));
            $("#btnGravarPalavra").hide();
            carregaTabela();
        }else{
            alert(data);
        };
    });
}; 

function gravarPalavra(){ 
    <?php if ($romanizacao){ ?>
    if ($('#romanizacao').val()==''){
        $("#romanizacao").addClass( 'is-invalid' ); return;
    };
    <?php }; ?>
    if ($('#pronuncia').val()==''){
        $("#pronuncia").addClass( 'is-invalid' ); return;
    };
    if ($('#significado').val()==''){
        $("#significado").addClass( 'is-invalid' ); return;
    };
    if ($('#pronuncia').val().includes("'") || $('#romanizacao').val().includes("'")) {
        alert('Caractere inválido!'); return;
    }

	var nativos = [];
	$('.escrita_nativa').each( function() {
		nativos.push(this.value);
	});
    var cex = new Array();
    $(".dimExtra").each(function() {
        cex.push( { val : $(this).val(), did : $(this).attr('id').replace("dimExtra","")} ); 
    });

    $.post("api.php?action=salvarPalavraFlexionada&dic=<?=$_GET['pid']?>&iid=<?=$id_idioma?>", 
    {   <?php if ($romanizacao){ ?> romanizacao:$('#romanizacao').val(), <?php } ?>
        pronuncia:$('#pronuncia').val(),
        significado:$('#significado').val(),
        irregular:$('#irregular').val(),
        pid:$('#idPalavra').val(),
        c1:$('#c1').val(),
        c2:$('#c2').val(),
        i1:$('#i1').val(),
        i2:$('#i2').val(),
        extras:cex,
        nativo: nativos
    }, function (data){
        if ($.trim(data) > 0){
            $('#idPalavra').val($.trim(data));
            $("#btnGravarPalavra").hide();
            $("#detalhesPalavra").hide(); //xxxxx limpar
            carregaTabela();
        }else{
            alert(data);
        };
    });
}; 

function showGravarPalavra(){
    $("#btnGravarPalavra").show();
}
function showGravarFlexao(){
    if ($('#idPalavra').val()<0) return;
    $("#btnGravarFlexao").show();
}

function carregaTabela(){ 
    $("#btnGravarPalavra").hide();
    $("#btnGravarFlexao").hide();

    var cex = new Array();
    $(".dimExtra").each(function() {
        cex.push( { val : $(this).val(), did : $(this).attr('id').replace("dimExtra","")} ); 
    });

    $("#detalhesPalavra").hide();
    
    $.post("api.php?action=carregarTabelaFlexoes&iid=<?=$id_idioma?>&<?php 
            if ($_GET['pid']>0) echo 'pid='.$_GET['pid'];
            else echo 'k='.$_GET['k'];
        ?>&d=<?=$_GET['d']?>&c=<?=$_GET['c']?>", {
            cex
        }, function (data){ 

        <?php if($_GET['pid']>0){ ?>
            
            var res = data.split("%%%");
            var tb = res[0]; //.replaceAll("%%a%%","");
            $("#tabelaFlexoes").html( tb.replaceAll("%%a%%","") );
            var orfas = res[2];

            var autogenlist = res[1]; //data.substring( data.indexOf("") );
            var linhas = autogenlist.split("\n");

            var raiz = '';
            var regras = '0';

            linhas.forEach(function(currentValue, index, arr){
                if (index+1 == linhas.length) {
                    raiz = currentValue; // pid da forma dic / root
                }else{
                    // tb = tb.replace("%%"+index+"%%","asd");
                    var vals = currentValue.split("-");
                    regras = regras + "," + vals[4];
                    // console.log(currentValue);
                }
            });

            /*
            processarFlexoes(regras, raiz, '<?=$motor?>', '<?=$id_idioma?>', defCats, tb)
                .then(() => console.log('Flexões processadas com sucesso'))
                .catch(error => console.error('Erro ao processar flexões:', error));
            */

            multiProcessarFlexoes(regras, raiz, '<?=$id_idioma?>', defCats, tb)
                .then(() => console.log('Flexões processadas com sucesso'))
                .catch(error => console.error('Erro ao processar flexões:', error));

            if (orfas.length > 0) {
                $("#divOrfans").html(orfas);
                $("#divOrfans").show();
            }else{
                $("#divOrfans").html("");
                $("#divOrfans").hide();
            }

        <?php }else{ ?>

            $("#tabelaFlexoes").html( data );
            appLoad();

        <?php } ?>
    });
};

async function processarFlexoes(regras, raiz, motor, idIdioma, defCats, tb) {
    const formData = new FormData();
    formData.append('id', regras);
    //console.log(motor);

    try {
        const response = await fetch(`api.php?action=getDetalhesFlexao&id=${regras}`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.length === 0) {
            for (let i = 0; i < data.length; i++) {
                tb = tb.replaceAll(`%%${i}%%`, '');
            }
        } else {
            const promises = data.map(async (val, key) => {
                const tmp = await sonalMdason(
                    val.regra_pronuncia,
                    raiz,
                    motor,
                    '#tmpSpan',
                    idIdioma,
                    defCats
                );
                let result = tmp === undefined || tmp === null ? raiz : tmp;
                let teclas = checarDigitacao(idIdioma, result);
                tb = tb.replaceAll(`%%${key}%%`, result);
                tb = tb.replaceAll(`%%r${key}%%`, result ? teclas + ' /' + result + '/' : teclas);
                tb = tb.replaceAll(`%%an${key}%%`, getAutoSubstituicao('<?=$escrita?>',teclas[0]) ?? '');
            });

            await Promise.all(promises);
        }

        $('#tabelaFlexoes').html(tb);

        appLoad();
    } catch (error) {
        console.error('Erro:', error);
    }
}

async function multiProcessarFlexoes(regras, raiz, idIdioma, defCats, tb) {
    const formData = new FormData();
    formData.append('id', regras);

    try {
        const response = await fetch(`api.php?action=getDetalhesFlexao&id=${regras}`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.length === 0) {
            for (let i = 0; i < data.length; i++) {
                tb = tb.replaceAll(`%%${i}%%`, '');
            }
            $('#tabelaFlexoes').html(tb);
            appLoad();
            return;
        }

        const palavras = Array(data.length).fill(raiz); // Repetir a raiz para cada grupo
        const lista_regras = data.map(val => val.regra_pronuncia); // Extrair regras de pronúncia

        const results = await multiSonalMdason(lista_regras, palavras, idIdioma, defCats);

        results.forEach((result, key) => { 
            if (!result) result = raiz;
            let teclas = checarDigitacao(idIdioma, result);
            let autoSub = getAutoSubstituicao('<?=$escrita?>', teclas[0])
            tb = tb.replaceAll(`%%${key}%%`, result);
            tb = tb.replaceAll(`%%r${key}%%`, result ? teclas + ' <span class="nowrap">/' + result + '/</span>' : teclas);
            tb = tb.replaceAll(`%%an${key}%%`, autoSub?autoSub+'<br>': '');
        });

        $('#tabelaFlexoes').html(tb);
        appLoad();
    } catch (error) {
        console.error('Erro:', error);
    }
}

function carregaRegra(id,lin,col,i1,i2,text,gen){ 
    $("#detalhesPalavra").hide();
    $('#idPalavra').val(id);
    $("#c1").val(lin);
    $("#c2").val(col);
    $("#i1").val(i1);
    $("#i2").val(i2);
    $("#gen").val(gen);
    $(".cell_selected").removeClass("cell_selected bg-primary-lt");   // card
    $(".cell-"+lin+'-'+col+'-'+i1+'-'+i2+'-'+gen).addClass("cell_selected bg-primary-lt"); // card


    if (id < 0) {
        //padrão desmarcado 
        //alert('Esta é a forma de dicionário!');
        // carregar os exemplos ?
    }else{
        //alert(id+'-'+lin+'-'+col+'-'+i1+'-'+i2);
        $("#flexGloss").html(text);


        //$('#sel_motor_sc').val('manual');  


        if (id>0){
            $.post( "api.php?action=getDetalhesFlexao&id=" +id ,{id: id}, function(data){ 
                data = JSON.parse(data);
                $.each( data, function( key, val ) {
                    
                        $('#nome').val(data[0].nome); 
                        $('#regra_pronuncia').val(data[0].regra_pronuncia); 
                        $('#regra_romanizacao').val(data[0].regra_romanizacao);  
                        //$('#sel_motor_sc').val(data[0].motor);  
                        /*
                        $('.escrita_nativa').val(''); 
                        data[0].escrita_nativa.forEach(function(e){
                            $('#escrita_nativa_'+e['id']).val(e['palavra']);
                        })
                        */
                        $("#detalhesPalavra").show(); //xxxxx carregar
                        //$('#extraPanel').html(data[0].extra);  
                        
                }); 
            });
        }else{
            alert('erro');
        }
        return;
    }
};

function loadCharDiv(eid,destDiv = "divInserirChars", forceReload = false, fonte = 0){
    $('#lateralEid').val(eid);
    $('#tempNat').val($('#escrita_nativa_'+eid).val());

    forceReload = true;

    $.get("api.php?action=getLastChange&data=writing&eid="+eid, function (data){
        if (forceReload || data > localStorage.getItem("k_chars"+eid+"_updated")){
            console.log('local chars outdated > update');
            $.get("api.php?action=ajaxGetDivLateralWriting2&eid="+eid, function (lex){
                $("#"+destDiv).html(lex);
                localStorage.setItem("k_chars"+eid, lex);
                localStorage.setItem("k_chars"+eid+"_updated", data);
                if(fonte == 3) addNatDraw(''); else $('#tempNat').addClass('custom-font-'+eid);
            })
        }else{
            console.log('local chars load');
            $("#"+destDiv).html( localStorage.getItem("k_chars"+eid) );
            if(fonte == 3) addNatDraw(''); else $('#tempNat').addClass('custom-font-'+eid);
        }
    });

}

function loadPronDiv(forceReload = false){
    $('#tempPron').val($('#pronuncia').val());

    let data = <?=getLastChange('sounds',$id_idioma)?>;
    if (forceReload || data > localStorage.getItem("k_sounds<?=$id_idioma?>_updated")){
        console.log('local sounds outdated > update');
        $.get("api.php?action=ajaxGetDivLateralSons&iid=<?=$id_idioma?>", function (lex){
            $("#divInserirSons").html(lex);
            localStorage.setItem("k_sounds<?=$id_idioma?>", lex);
            localStorage.setItem("k_sounds<?=$id_idioma?>_updated", data);
        })
    }else{
        console.log('local sounds load');
        $("#divInserirSons").html( localStorage.getItem("k_sounds<?=$id_idioma?>") );
    }
}

<?php if($_GET['pid']>0){ ?>

function abrirPalavra(pid,l,c,i1,i2,text,dic=0,autogen=""){ 
	$(".cell_selected").removeClass("cell_selected bg-primary-lt");   //card
	$(".cell-"+l+'-'+c+'-'+i1+'-'+i2).addClass("cell_selected bg-primary-lt"); //card
    $(".is-invalid").removeClass( 'is-invalid' );

    $("#btnGravarPalavra").hide();

    $("#flexGloss").html(text);
    $("#c1").val(l);
    $("#c2").val(c);
    $("#i1").val(i1);
    $("#i2").val(i2);

    $('#irregular').val(0); 
    if (dic==0 || dic==pid){
        $('#irregular').attr('disabled',true); 
    }else{
        $('#irregular').attr('disabled',false); 
    }
    

    if (pid>0){
	    $('#idPalavra').val(pid); 
        $.getJSON( "api.php?action=getDetalhesPalavra&pid=" +pid , function(data){ 
            $.each( data, function( key, val ) {
                    $('#romanizacao').val(data[0].romanizacao); 
                    $('#pronuncia').val(data[0].pronuncia); 
                    $('#irregular').val(data[0].irregular); 
	                $('#idFormaDicionario').val(data[0].id_forma_dicionario);
                    
                    $('.escrita_nativa').val(''); 
                    data[0].escrita_nativa.forEach(function(e){
                        exibirNativa(e['id'],e['palavra'],e['fonte'],e['tamanho']);//$('#escrita_nativa_'+e['id']).val(e['palavra']); // exibirNativa
                    })
                    $('#significado').val(data[0].significado); 
            }); 
            $("#detalhesPalavra").show();
        });
    }else{

        // opcao de abrir forma dic pra editar/salvar

        // alert(dic);
        $('#idPalavra').val(0); 
        $(".escrita_nativa").each(function(){
            let eid = $(this).attr('id').replace("escrita_nativa_",'');
            exibirNativa(eid,'',null,null);
        });
        $.getJSON( "api.php?action=getDetalhesPalavra&pid=" +dic , function(data){ 
            $.each( data, function( key, val ) {

                if (autogen == ""){
                    $('#romanizacao').val(data[0].romanizacao); 
                    $('#pronuncia').val(data[0].pronuncia); 
                    
                    data[0].escrita_nativa.forEach(function(e){
                        exibirNativa(e['id'],e['palavra'],e['fonte'],e['tamanho']);
                    })
                }else{
                    //$('#romanizacao').val( checarDigitacao('<?=$id_idioma?>', autogen) );  // ""
                    $('#pronuncia').val(checarDigitacao('<?=$id_idioma?>', autogen)); 

                    $('.escrita_nativa')/*.val(''); */.each(function(e){
                        let eid = $(this).attr('id').replace('escrita_nativa_','');
                        $(this).val( getAutoSubstituicao(eid, $('#romanizacao').val() ) );
                    })
                }
                $('#idFormaDicionario').val(dic);
                $('#irregular').val('0'); 
                $('#significado').val(data[0].significado); 
            }); 
            $("#detalhesPalavra").show();
            showGravarPalavra();
            checarPronuncia("#pronuncia", '<?=$id_idioma?>', 0);
        });
    }
    $("#detalhesPalavra").show();
};

<?php } ?>

function checarRomanizacao(este,idioma){ 
	 $(este).removeClass( 'is-invalid' );
	//alert( $(este).val() );
		<?php 
		// se estiver romanizacao como principal entrada, echo preencherPronuncia e foreach native
		?>
		showGravarPalavra();
};
<?php if($idioma['checar_sons']==1){ ?>
function checarPronuncia(este = "#pronuncia",idioma=<?=$id_idioma?>,checar = 1){ 
    $(este).removeClass( 'is-invalid' );
    showGravarPalavra();
    var tmpPron = $(este).val();
    let data = getChecarPronuncia(idioma, tmpPron, checar);
    if(data=='-1'){ 
        $(este).addClass( 'is-invalid' );
    }else{
        $(este).val( data );
		<?php if ($romanizacao) echo '$("#romanizacao").val(tmpPron);'; ?>
        data = tmpPron; // checarDigitacao('<?=$id_idioma?>', data);
        <?=$scriptAutoSubstituicao?> // data deve ser a escrita
    };
};
<?php }else{
	echo 'function checarPronuncia(este,idioma){$(este).removeClass("is-invalid");showGravarPalavra();}';
}; ?>

function editarPalavra() {showGravarPalavra()};
function checarNativo(este,eid){ 
	//checar se caracteres estão na lista de caracteres apenas
    $(este).removeClass( 'is-invalid' );
    showGravarPalavra();
	$.post('api.php?action=getChecarNativo&eid='+eid, {
		p: $(este).val()
	}, function (data){ 
		if(data=='-1'){ 
			$(este).addClass( 'is-invalid' );
		}else{
            if (data.lenght > 0)
			    $(este).val( data );
			//salvarNativo(eid);

		}
	});
};

$(document).ready(function(){
    carregaTabela();    
    if (localStorage.getItem("k_forms_long") == "1"){
		$("#lpc").attr("checked",true); $('#tabelaFlexoes').removeClass('listaLonga');
    }    
});

function toggleListaLonga(){
    $('#tabelaFlexoes').toggleClass('listaLonga');
    
    if( $("#lpc").attr("checked") ) {
        $("#lpc").attr("checked",false);
        $('#tabelaFlexoes').addClass('listaLonga');
        localStorage.setItem("k_forms_long", "0");
    }else{
        $("#lpc").attr("checked",true);
        $('#tabelaFlexoes').removeClass('listaLonga');
        localStorage.setItem("k_forms_long", "1");
    }
    
}

function loadExtras(){ 
    $("#offcanvasStartLabel").html('<?=_t('Mais')?>');
    $("#extraCanvas").load('api.php?action=loadFormExamples&k=<?=$id_classe?>&c1='+$('#c1').val()+'&c2='+$('#c2').val()+'&i1='+$('#i1').val()+'&i2='+$('#i2').val()+'&gen='+$('#gen').val() );
}

function addIpaPronuncia(char){
    $("#tempPron").val($("#tempPron").val() + char);
    //$("#pronuncia").trigger("change");
}
function okIpaPronuncia(){
    $("#pronuncia").val($("#tempPron").val());
    $("#pronuncia").trigger("change");
}

function dragstartHandler(ev) {
  ev.dataTransfer.setData("text", ev.target.id);
  //ev.target.classList.add("dragging");
  $("#extraDropzone").show();
  $("#extrass").hide();
  $("#detalhesPalavra").hide();

  $(".nvt").hide();
  $(".ntr").show();
  //$("#detalhesPalavra").hide();
  //$(".cell").addClass("tempTarget");
} 
function dragoverHandler(ev) {
  ev.preventDefault();
}
function dropHandler(ev) {

    $(".nvt").show();
    $(".ntr").hide();
    
    $("#extraDropzone").hide();
    $("#extrass").show();
    //ev.target.classList.remove("dragging");
    //$(".cell").removeClass("tempTarget");
    ev.preventDefault();
    const data = ev.dataTransfer.getData("text");
    
    // alert("from "+data+" to "+ev.target.id); return;

    var cex = new Array();
    $(".dimExtra").each(function() {
        cex.push( { val : $(this).val(), did : $(this).attr('id').replace("dimExtra","")} ); 
    });

    $.post("api.php?action=ajaxMoverFormaPalavra&iid=<?=$id_idioma?>", 
        {from: data, to: ev.target.id, cex: cex}, function (data){
            if ($.trim(data)=='ok') carregaTabela();
            else if ($.trim(data)=='0') return;
            else alert(data);
    });
}

let soundsChanged = <?=getLastChange('sounds',$id_idioma)?>;
if ( soundsChanged > localStorage.getItem("k_pronuncias_updated_<?=$id_idioma?>")) loadPronuncias('<?=$id_idioma?>',soundsChanged,true);
<?=$autoSubsLoadScript?>;
</script>

<style>
.tempTarget:hover{
    border: 2px solid yellow;
}
</style>

<input type="hidden" id="lateralEid" />

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExtras" aria-labelledby="offcanvasStartLabel">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel"></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body" id="extraCanvas">
	</div>
</div>



<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPronBtns" aria-labelledby="offcanvasStartLabel2">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel2"><?=_t('Pronúncia')?></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3">
		<?php 
			$is = mysqli_query($GLOBALS['dblink'],"SELECT s.nome, p.nome as nome2, s.ipa, p.ipa as ipa2, t.tecla FROM inventarios i
				LEFT JOIN teclas t ON t.id_inventario = i.id
				LEFT JOIN sons s ON (i.id_som = s.id AND i.id_tipoSom > 0)
				LEFT JOIN sonsPersonalizados p ON ( p.id = i.id_som AND i.id_tipoSom = 0 )
				WHERE i.id_idioma = ".$id_idioma.";") or die(mysqli_error($GLOBALS['dblink']));
			while($r = mysqli_fetch_assoc($is)) { 
				if ($r['tecla']!='') $btn = $r['tecla'].' /'.$r['ipa'].$r['ipa2'].'/';
				else $btn = $r['ipa'].$r['ipa2'];
				echo '<a title="'.$r['nome'].$r['nome2'].'" class="btn btn-primary mb-3 mx-2" onclick=\'addIpaPronuncia(`'.$r['ipa'].$r['ipa2'].'`)\'>'.$btn.'</a> ';
			};
		?>

		</div>
		<div>
			<input type="text" class="form-control" id="tempPron">
			<button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="okIpaPronuncia()">
			Ok
			</button>
		</div>
	</div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasPronBtns" aria-labelledby="offcanvasStartLabel">
	<div class="offcanvas-header">
		<h2 class="offcanvas-title" id="offcanvasStartLabel"><?=_t('Pronúncia')?></h2>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<div class="mb-3" id="divInserirSons">
		</div>
		<div>
			<input type="text" class="form-control" id="tempPron">
			<button class="btn btn-primary" type="button" data-bs-dismiss="offcanvas" onclick="okIpaPronuncia()">
			Ok
			</button>
		</div>
	</div>
</div>
