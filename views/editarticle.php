
<!-- PANEL START -->
<?php 
    $iid = -1;
    if ($_GET['id'] > 0) {

      $query = 'SELECT * FROM artygs WHERE id_usuario = '.$_SESSION['KondisonairUzatorIDX'].' AND id = '.$_GET['id'].';';
      $result = mysqli_query($GLOBALS['dblink'],$query) or die(mysqli_error($GLOBALS['dblink'])); 
      $r = mysqli_fetch_assoc($result);

      $aid = $r['id'];
      $iid = $r['id_idioma'];
      
    } else $aid = 0;

    if ($_GET['iid']>0){
      $iid = $_GET['iid'];

      $result = mysqli_query($GLOBALS['dblink'],"SELECT i.*,
        (SELECT es.id FROM escritas es where es.id_idioma = i.id AND es.padrao = 1 LIMIT 1) as eid,
        (SELECT es.nome FROM escritas es where es.id_idioma = i.id AND es.padrao = 1 LIMIT 1) as eidNome
        FROM idiomas i
          WHERE i.id = '".$iid."';") or die(mysqli_error($GLOBALS['dblink']));

      $riid = mysqli_fetch_assoc($result);
      if ($riid['eid']>0){
        $eidNome = $riid['eidNome'];
        $eid = $riid['eid'];
      }
    }
   
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

                      <?php if($iid>0){ ?>
                        <li class="breadcrumb-item"><a href="index.php?page=editlanguage&iid=<?=$iid?>"><?=$riid['nome_legivel']?></a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=myarticles&iid=<?=$iid?>"><?=_t('Artigos')?></a></li>
                      <?php }else{ ?>

                      <li class="breadcrumb-item"><a href="index.php?page=myarticles"><?=_t('Meus artigos')?></a></li>
                      <?php } ?>
                      
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
                
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?=_t('Artigo')?></h3>
                    </div>
                    <div class="card-bodyx">
						<div id="editor"><?=$r['texto']?></div>
                    </div>
                </div>
            </div>


            <div class="col-md-3">
                <div class="card">
                    <div class="card-body"> 

                        <div class="mb-3">
                          <div class="form-label"><?=_t('Título')?></div>
                            <input type="text" class="form-control" id="nome" value="<?=$r['nome']?>" onchange="$('#btnSalvar').show()">
                        </div>

                        <div class="mb-3" >
                            <label class="form-label"><?=_t('Publicação')?></label>
                            <select id="publico" class="chosen-select form-control " onchange="$('#btnSalvar').show()" >
                                <option value="0" <?=$r['publico']=='0'?'selected':''?>><?=_t('Privado')?></option>
                                <option value="1" <?=$r['publico']=='1'?'selected':''?>><?=_t('Publicado')?></option>
                            </select>
                        </div>

                        <div class="mb-3" >
                            <label class="form-label"><?=_t('Artigo pai')?></label>
                            <select id="art_pai" class="chosen-select form-control " onchange="$('#btnSalvar').show()" >
                                <option value="0" selected><?=_t('Nenhum')?></option>
                                <?php 
                                $langs = mysqli_query($GLOBALS['dblink'],
                                    "SELECT a.* FROM artygs a WHERE a.id_idioma = ".$iid.";") or die(mysqli_error($GLOBALS['dblink'])); //id_usuario = ".$_SESSION['KondisonairUzatorIDX']." AND 
                                while ($lang = mysqli_fetch_assoc($langs)){
                                    echo '<option value="'.$lang['id'].'"';
                                    if ($r['id_pap'] == $lang['id']) echo ' selected';
                                    echo '>'.$lang['nome'].'</option>'; // ('.$lang['nome_legivel'].')
                                }
                                ?>
                            </select>
                        </div>

                        <!--div class="mb-3">
                            <div class="form-label"><?=_t('Ligacao')?></div>
                            <select class="form-select" id="links" title="Ligacao" type="text" value="" onchange="$('#btnSalvar').show()">
                                <option value="0">Nenhuma</option>
                                <option disabled>Textos/Aulas</option>
                                <?php 
                                $langs = mysqli_query($GLOBALS['dblink'],
                                  "SELECT t.*, ad.id_artyg FROM studason_tests t 
                                    LEFT JOIN idiomas i ON i.id = t.id_idioma
                                    LEFT JOIN artyg_dest ad ON ad.id_dest = t.id AND tipo_dest = 'text';") or die(mysqli_error($GLOBALS['dblink']));
                                while ($l = mysqli_fetch_assoc($langs)){
                                    echo '<option value="'.$l['id'].'"';
                                    if($aid>0 && $aid==$l['id_artyg']) echo ' selected';
                                    echo '>Texto: '.$l['titulo'].'</option>';
                                }
                                ?>


                            </select>
                        </div-->
                        <a class="btn btn-primary w-100 mt-2" id="btnSalvar" onclick="salvarArtigo()"><?=_t('Salvar')?></a>


                    </div>
                </div>
            </div>

            </div>
          </div>
        </div>

<script>

$(document).ready(function(){
    $('#btnSalvar').hide();
    let options = {
      selector: '#editor',
      height: 400,
      menubar: false,
      statusbar: true,
      plugins: [
        'advlist', 'autolink', 'lists', 'link', 'paste', 'table', 'print', 'preview', 'fullscreen', 'wordcount'
        //'image charmap anchor',
        //'searchreplace visualblocks code',
        //'insertdatetime media paste help wordcount'
      ],
      setup: function(editor) {
        editor.on('keyup', (e) => {
          $('#btnSalvar').show();
        });
        <?php if($eid > -1){ ?>
        editor.ui.registry.addButton("custom_font", {
          tooltip: "<?=$eidNome?>",
          icon: "permanent-pen", // look editor-icon-identifiers page
          onAction: function(font) {
            var element = tinymce.activeEditor.selection.getNode();
            tinymce.activeEditor.dom.setAttrib(element, "class", "custom-font-<?=$eid?>");
          }
        });
        <?php } ?>

      },

      toolbar: 'undo redo formatselect ' +
        'bold italic bullist numlist removeformat link unlink table preview custom_font',
      content_style: `body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; } <?=$globalcustomfonts?>`
    }
    if (localStorage.getItem("tabler-theme") === 'dark') {
      options.skin = 'oxide-dark';
      options.content_css = 'dark';
    }
    tinyMCE.init(options);

    //createTablerSelect('links');
    createTablerSelect('art_pai');
}); 

function salvarArtigo(){ 
    
    if ($('#nome').val() == '') return;
    if ( /*$('#texto').summernote('code') == ''*/ tinymce.get('editor').getContent() == '') return;
    
    $.post("api.php?action=ajaxSalvArtyg&aid="+ $('#aid').val(), {
        n: $('#nome').val(),
        tp: $('#typ').val(),
        p: $('#publico').val(),
        ap: $('#art_pai').val(),
        //l: $('#links').val(),
        iid: <?=$iid?>,
        t: tinymce.get('editor').getContent()// $('#texto').summernote('code')
    }, function (data){
        if($.trim(data)>0){
            //$("#id").empty().append( $.trim(data) );
            $("#aid").val( $.trim(data) );
            $('#btnSalvar').hide();
        }
        else
            alert(data);
	});

}

</script>