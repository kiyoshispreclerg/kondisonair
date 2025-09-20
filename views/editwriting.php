<!-- PANEL START -->
<?php 

    $id_idioma = $_GET['iid'];
    $idioma = array();   
    $result = mysqli_query($GLOBALS['dblink'],"SELECT *,(SELECT id FROM collabs WHERE id_idioma = i.id AND id_usuario = ".$_SESSION['KondisonairUzatorIDX']." LIMIT 1) as collab FROM idiomas i
                WHERE id = '".$id_idioma."';") or die(mysqli_error($GLOBALS['dblink']));
    while($r = mysqli_fetch_assoc($result)) { 
        $idioma  = $r;
    };

    if ($idioma['nome_legivel']=='' || ($idioma['id_usuario'] != $_SESSION['KondisonairUzatorIDX'] && !$idioma['collab'] > 0 )) {
        echo '<script>window.location = "index.php";</script>';
		exit;
    }

    $fonts = '';
    $tabs = '';
    $header = '';
    $contents = '';
    $fonte = 0;

    $escritas = mysqli_query($GLOBALS['dblink'],"SELECT e.*, (SELECT palavra FROM palavrasNativas WHERE id_palavra = e.id_nativo AND id_escrita = e.id LIMIT 1) as nativo,
                    (SELECT COUNT(id) FROM palavrasNativas WHERE id_escrita = e.id) as np,
                    e.padrao as epadrao, f.arquivo as fonte FROM escritas e 
                    LEFT JOIN fontes f ON f.id = e.id_fonte
                    WHERE e.id_idioma = ".$id_idioma." ORDER BY e.padrao DESC;") or die(mysqli_error($GLOBALS['dblink']));

    while($e = mysqli_fetch_assoc($escritas)){ 

        if ($e['padrao']==3) {
          $fonte = $e['id_fonte'];
          $tamanho = $e['tamanho'];
        }
        
        $script .= 'carregarTabelaEscrita("'.$e['id'].'");carregarTabelaAlfabeto("'.$e['id'].'");
        selectNativo("'.$e['id'].'","'.$e['id_nativo'].'"); '; 

        $contents .= '<div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <div class="card-title" onclick="window.history.replaceState({}, \'\', \'index.php?page=editwriting&iid='.$id_idioma.'&eid='.$e['id'].'\');$(\'.cb_'.$e['id'].'\').toggle()">'.$e['nome'].($e['padrao']==1?' ('._t('Padrão').')':'').'</div>
                        <div class="card-actions btn-actions">
                          <a href="#" onclick="apagarEscrita(\''.$e['id'].'\',\''.$e['np'].'\')" class="btn btn-danger">'._t('Apagar').'</a>
                          <a href="#" onclick="execSalvarEscrita(\''.$e['id'].'\')" id="btnSalvar'.$e['id'].'" class="btn btn-primary">'._t('Salvar').'</a>
                        </div>
                      </div>
                      <div class="card-body cb_'.$e['id'].'" '.($_GET['eid']==$e['id']?'':'style="display:none"').'>';

        $contents .= '<div class="row g-5">

                        <div class="col-xl-4">

                            <div class="mb-3">
                              <label class="form-label">'._t('Nome').'</label>
                              <input type="text" class="form-control" id="nome'.$e['id'].'" value="'.$e['nome'].'" onchange="salvarEscrita(\''.$e['id'].'\')">
                            </div>

                            <div class="mb-3">
                              <div class="row">
                                <div class="col-6">

                                    <div class="form-label">'._t('Tipo de Sistema').'</div>
                                    <select class="form-select" id="id_tipo'.$e['id'].'" onchange="salvarEscrita(\''.$e['id'].'\')" >
                                      <option value="1" title="" '.($e['id']==1?'selected':'').'>'._t('Alfabeto').'</option>
                                      <option value="2" title="" '.($e['id']==2?'selected':'').'>'._t('Silabário').'</option>
                                      <option value="3" title="" '.($e['id']==3?'selected':'').'>'._t('Consonantal').'</option>
                                      <option value="4" title="" '.($e['id']==4?'selected':'').'>'._t('Logográfico').'</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">'._t('Tamanho da fonte').'</label>
                                    <select type="text" class="form-select" id="tamanho'.$e['id'].'" value="'.$e['tamanho'].'" onchange="salvarEscrita(\''.$e['id'].'\')">';
                    if ($e['id_fonte']==3){
                        $contents .= '<option value="unset" '.($e['tamanho']=='unset'?'selected':'').'>'._t('Padrão').'</option>
                                  <option value="sm" '.($e['tamanho']=='sm'?'selected':'').'>'._t('Pequena').'</option>
                                  <option value="md" '.($e['tamanho']=='md'?'selected':'').'>'._t('Média').'</option>
                                  <option value="lg" '.($e['tamanho']=='lg'?'selected':'').'>'._t('Grande').'</option>
                                  <option value="xl" '.($e['tamanho']=='xl'?'selected':'').'>'._t('Maior').'</option>
                                  <option value="2xl" '.($e['tamanho']=='2xl'?'selected':'').'>'._t('Muito grande').'</option>';
                    }else{
                      $contents .= '<option value="unset" '.($e['tamanho']=='unset'?'selected':'').'>'._t('Padrão').'</option>
                            <option value="small" '.($e['tamanho']=='small'?'selected':'').'>'._t('Pequena').'</option>
                            <option value="medium" '.($e['tamanho']=='medium'?'selected':'').'>'._t('Média').'</option>
                            <option value="large" '.($e['tamanho']=='large'?'selected':'').'>'._t('Grande').'</option>
                            <option value="x-large" '.($e['tamanho']=='x-large'?'selected':'').'>'._t('Maior').'</option>
                            <option value="xx-large" '.($e['tamanho']=='xx-large'?'selected':'').'>'._t('Muito grande').'</option>
                            <option value="xxx-large" '.($e['tamanho']=='xxx-large'?'selected':'').'>'._t('Gigante').'</option>';
                    }

                    $contents .= '</select>
                                </div>
                              </div>
                            </div>



                            
                            <div class="mb-3">
                              <div class="row">
                                <div class="col-6">
                                  <label class="form-check form-switch">
                                    <input class="form-check-input wdef-check" id="wdef-check'.$e['id'].'" type="checkbox"  '.($e['padrao']==1?"checked":'').' onchange="setPadrao(\''.$e['id'].'\')">
                                    <span class="form-check-label">'._t('Sistema padrão').'</span>
                                  </label>
                                </div>
                                <div class="col-6">
                                  <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="publico'.$e['id'].'" '.($e['publico']==1?'checked':'').' onchange="salvarEscrita(\''.$e['id'].'\')">
                                    <span class="form-check-label">'._t('Público').'</span>
                                  </label>
                                </div>
                              </div>
                            </div>

                            

                            <div class="mb-3">
                            <div class="row">
                            <div class="col-6">
                              <label class="form-label">'._t('Nome nativo').'</label>
                              <select type="text" class="form-select" value="" id="id_nativo'.$e['id'].'" onchange="salvarEscrita(\''.$e['id'].'\')">
                                ';

                              $contents .= '</select>
                            </div>';

                            if($e['id_fonte'] == 3){
                              $editChar = 'drawCaractere(\'';
                              $editSubs = 'addSubstituicaoDraw';

                            }else{
                              $editChar = 'addCaractere(\'';
                              $editSubs = 'addSubstituicao';
                            $contents .= '<div class="col-6">
                                    <label class="form-label">'._t('Fonte').' <a class="btn btn-sm btn-primary" onclick="loadModalFontes()">'._t('Gerenciar').'</a></label>
                                    <select id="fonte'.$e['id'].'" class="form-select" type="text" onchange="salvarEscrita(\''.$e['id'].'\')" >
                                    <option value="3" selected>'._t('Desenhada').'</option>';
                                    $fts = mysqli_query($GLOBALS['dblink'],"SELECT * FROM fontes WHERE id_usuario = ".$_SESSION['KondisonairUzatorIDX']." OR publica = 1;") or die(mysqli_error($GLOBALS['dblink']));
                                        while ($tf = mysqli_fetch_assoc($fts)){
                                            $contents .=  '<option value="'.$tf['id'].'" title="'.$tf['nome'].'"';
                                            if ($tf['id']==$e['id_fonte']) $contents .=  ' selected ';
                                            $contents .= '>'.$tf['nome'].'</option>';
                                        }

                                    $contents .='</select>
                              </div>
                              </div>
                              </div>';
                            };


                            $contents .= '<div class="mb-3">
                              <div class="row">
                                <div class="col-6">
                                  <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="substituicao'.$e['id'].'" '.($e['substituicao']==1?'checked':'').' onchange="salvarEscrita(\''.$e['id'].'\')">
                                    <span class="form-check-label">'._t('Autosubstituição').'</span>
                                  </label>
                                </div>
                                <div class="col-6">
                                  <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="checar_glifos'.$e['id'].'" '.($e['checar_glifos']==1?'checked':'').' onchange="salvarEscrita(\''.$e['id'].'\')">
                                    <span class="form-check-label">'._t('Autochecar glifos').'</span>
                                  </label>
                                </div>
                                <div class="col-12">
                                  <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="bin'.$e['id'].'" '.($e['binario']==1?'checked':'').' onchange="salvarEscrita(\''.$e['id'].'\')">
                                    <span class="form-check-label">'._t('Diferenciar maiúsculas').'</span>
                                  </label>
                                </div>
                              </div>
                            </div>



                            <div class="mb-3">
                              <label class="form-label">'._t('Glifos que separam palavras').'</label>
                              <input type="text" class="form-control custom-font-'.$e['id'].'" id="separadores'.$e['id'].'" value=\''.$e['separadores'].'\' onchange="salvarEscrita(\''.$e['id'].'\')">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">'._t('Glifos que iniciam palavras').'</label>
                              <input type="text" class="form-control custom-font-'.$e['id'].'" id="iniciadores'.$e['id'].'" value="'.$e['iniciadores'].'" onchange="salvarEscrita(\''.$e['id'].'\')">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">'._t('Glifos que separam sentenças').'</label>
                              <input type="text" class="form-control custom-font-'.$e['id'].'" id="sep_sentencas'.$e['id'].'" value=\''.$e['sep_sentencas'].'\' onchange="salvarEscrita(\''.$e['id'].'\')">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">'._t('Glifos que iniciam sentenças').'</label>
                              <input type="text" class="form-control custom-font-'.$e['id'].'" id="inic_sentencas'.$e['id'].'" value="'.$e['inic_sentencas'].'" onchange="salvarEscrita(\''.$e['id'].'\')">
                            </div>

                        </div>

                        <div class="col-xl-4">

                            <div class="mb-3">
                              <label class="form-label">'._t('Caracteres/diacríticos e ordem').'</label>
                              <input type="text" class="form-control" id="searchAlfabeto' . $e['id'] . '" placeholder="' . _t('Buscar por glifo, descrição ou variantes') . '">
                            </div>

                            <div class="mb-3 overflow-auto" style="max-height: 45rem">
                                <div class="col-12">
                                    <a class="btn btn-primary"  onclick="'.$editChar.$e['id'].'\')">+</a>
                                    <!--a class="btn btn-primary"  onclick="editarMetadado(\''.$e['id'].'\')">'._t('Metadados').'</a-->
                                </div>
                                <div id="alfabeto'.$e['id'].'" class="list-group list-group-flush list-group-hoverable">
                                    <a class="btn btn-primary" onClick="carregarTabelaAlfabeto(\''.$e['id'].'\')"><i class="fa fa-refresh"></i>'._t('Carregar').'</a>
                                </div> 

                            </div>

                            

                        </div>

                        <div class="col-xl-4" >

                            <div class="mb-3">
                              <label class="form-label">'._t('Substituição automática').' <span class="text-secondary">'._t('a partir da digitação em Pronúncia').'</span></label>
                              <input type="text" class="form-control" id="searchAutoSubstituicao' . $e['id'] . '" placeholder="' . _t('Buscar por tecla, IPA ou glifos') . '">
                            </div>
                            <div class="mb-3 overflow-auto" style="max-height: 45rem">

                                <div class="col-12">
                                    <a class="btn btn-primary"  onclick="'.$editSubs.'(\''.$e['id'].'\',0)">+</a>
                                </div>
                                <div id="autoSubstituicao'.$e['id'].'"  class="list-group list-group-flush list-group-hoverable">
                                    <a class="btn btn-primary" onClick="carregarTabelaEscrita(\''.$e['id'].'\')"><i class="fa fa-refresh"></i>'._t('Carregar').'</a>
                                </div>

                            </div>

                        </div>

                      </div>';

        $contents .= '</div></div></div>';
    }
?>

<input type="hidden" id="codigo" value="<?=$id_idioma?>" />
<input type="hidden" id="idPalavra" value="0" />


        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  <ol class="breadcrumb breadcrumb-arrows">
                      <li class="breadcrumb-item"><a href="index.php"><?=_t('Início')?></a></li>
                      <li class="breadcrumb-item"><a href="?page=editlanguage&iid=<?=$id_idioma?>"><?=$idioma['nome_legivel']?></a></li>
                      <li class="breadcrumb-item active"><a><?=_t('Sistemas de escrita')?></a></li>
                    </ol>
                </h2>
              </div>

              <!-- Page title actions -->
              <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                  <a class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-novo">
                    <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                    <?=_t('Novo')?>
                  </a>
                </div>
              </div>

            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="row row-deck row-cards">



              <?=$contents?>
              


            </div>
          </div>
        </div>


<style><?=$fonts?></style>


<script>

  let autosubstitutionData = {};
  let alfabetoData = {};

  function renderAutosubstitutionList(id, items) {
      const $container = $('#autoSubstituicao' + id);
      $container.html(''); // Clear existing content

      items.forEach(item => {
          const teclaEscaped = item.tecla.replace(/'/g, "*");
          let html = `
              <div class="list-group-item">
                  <div class="row align-items-center">
                      <div class="col-auto" onclick="${
                          item.fonte == 3 
                          ? `addSubstituicaoDraw('${id}', '${item.id}', '${teclaEscaped}', '${item.glifos}')`
                          : `addSubstituicao('${id}', '${item.id}', '${teclaEscaped}', '${item.glifos.replace(/'/g, "*")}')`
                      }">
                          ${item.tecla} /${item.ipa}/ → `;
          
          if (item.fonte == 3) {
              html += `<span class="drawchar drawchar-${item.tamanho}" style="background-image: url(./writing/${id}/${item.cid}.png?${item.ultima})"></span>`;
          } else {
              html += `<span class="custom-font-${id}">${item.glifos}</span>`;
          }

          html += `
                      </div>
                      <div class="col text-end">
                          <div class="text-secondary text-truncate mt-n1">
                              <a class="btn btn-danger btn-sm" onClick="remAutosubs('${item.id}', '${id}')">X</a>
                          </div>
                      </div>
                  </div>
              </div>`;
          
          $container.append(html);
      });
  }

  function carregarTabelaEscrita(id) {
      $.post("api.php?action=ajaxLoadAutosubstitutionData&eid=" + id, function (data) {
          const $container = $('#autoSubstituicao' + id);
          $container.html(''); // Clear existing content

          if (data.error) {
              $container.html('<div class="alert alert-danger">' + data.error + '</div>');
              return;
          }

          // Store data for filtering
          autosubstitutionData[id] = {
              fonte: data.fonte,
              tamanho: data.tamanho,
              items: data.autosubstitutions.map(item => ({
                  ...item,
                  fonte: data.fonte,
                  tamanho: data.tamanho
              }))
          };

          // Render initial list
          renderAutosubstitutionList(id, autosubstitutionData[id].items);

          // Add search handler
          $('#searchAutoSubstituicao' + id).off('input').on('input', function () {
              const searchTerm = $(this).val().toLowerCase();
              const filteredItems = autosubstitutionData[id].items.filter(item =>
                  item.tecla.toLowerCase().includes(searchTerm) ||
                  item.ipa.toLowerCase().includes(searchTerm) ||
                  item.glifos.toLowerCase().includes(searchTerm)
              );
              renderAutosubstitutionList(id, filteredItems);
          });
      }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
          $('#autoSubstituicao' + id).html('<div class="alert alert-danger">Error loading data: ' + textStatus + '</div>');
      });
  }

  function renderAlfabetoList(id, items) {
      const $container = $('#alfabeto' + id);
      $container.html(''); // Clear existing content

      items.forEach(item => {
          let glifoHtml = '';
          if (item.fonte == 3) {
              glifoHtml = `<span class="drawchar drawchar-${item.tamanho}" style="background-image: url(./writing/${id}/${item.id}.png?${item.ultima})"></span> ${item.descricao}`;
          } else {
              glifoHtml = `<span class="custom-font-${item.id_escrita}">${item.glifo}</span>`;
              if (item.descricao !== '') {
                  glifoHtml += ` (${item.descricao})`;
              }
              if (item.variantes !== '' && item.variantes !== null) {
                  glifoHtml += `<br><span class="text-secondary text-truncate custom-font-${item.id_escrita}"><small>${item.variantes}</small></span>`;
              }
          }

          const glifoEscaped = item.glifo.replace(/'/g, "*");
          const variantesEscaped = item.variantes ? item.variantes.replace(/'/g, "*") : '';
          const vetorEscaped = item.vetor ? `\`${item.vetor}\`` : '[]';

          const html = `
              <div class="list-group-item">
                  <div class="row align-items-center">
                      <div class="col-auto" onclick="${
                          item.fonte == 3 
                          ? `drawCaractere('${item.id_escrita}', '${item.descricao}', '${item.id}', '${glifoEscaped}', '${variantesEscaped}', ${vetorEscaped})`
                          : `addCaractere('${item.id_escrita}', '${item.descricao}', '${item.id}', '${glifoEscaped}', '${variantesEscaped}')`
                      }">
                          ${glifoHtml}
                      </div>
                      <div class="col text-end">
                          <div class="text-secondary text-truncate mt-n1">
                              <a class="btn btn-danger btn-sm" onClick="apagarGlifo('${item.id}', '${id}')">X</a>
                              <a class="btn btn-primary btn-sm" onClick="moverAbaixo('${item.id}', '${id}')">v</a>
                              <a class="btn btn-primary btn-sm" onClick="moverAcima('${item.id}', '${id}')">^</a>
                          </div>
                      </div>
                  </div>
              </div>`;
          
          $container.append(html);
      });
  }

  function carregarTabelaAlfabeto(id) {
      $.post("api.php?action=ajaxLoadAlphabetData&eid=" + id, function (data) {
          const $container = $('#alfabeto' + id);
          $container.html(''); // Clear existing content

          if (data.error) {
              $container.html('<div class="alert alert-danger">' + data.error + '</div>');
              return;
          }

          // Store data for filtering
          alfabetoData[id] = {
              fonte: data.fonte,
              tamanho: data.tamanho,
              items: data.glyphs.map(item => ({
                  ...item,
                  fonte: data.fonte,
                  tamanho: data.tamanho
              }))
          };

          // Render initial list
          renderAlfabetoList(id, alfabetoData[id].items);

          // Add search handler
          $('#searchAlfabeto' + id).off('input').on('input', function () {
              const searchTerm = $(this).val().toLowerCase();
              const filteredItems = alfabetoData[id].items.filter(item =>
                  item.glifo.toLowerCase().includes(searchTerm) ||
                  (item.descricao && item.descricao.toLowerCase().includes(searchTerm)) ||
                  (item.variantes && item.variantes.toLowerCase().includes(searchTerm))
              );
              renderAlfabetoList(id, filteredItems);
          });
      }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
          $('#alfabeto' + id).html('<div class="alert alert-danger">Error loading data: ' + textStatus + '</div>');
      });
  }

  $(document).ready(function(){
      <?php echo $script; ?>
  });

  function salvarEscrita(id){
    $('#btnSalvar'+id).show();
  }

  function execSalvarEscrita(id){
      
    $.post("api.php?action=ajaxSalvarEscrita&eid="+id, 
    { id_tipo:$('#id_tipo'+id).val(),
          nome:$('#nome'+id).val(),
      id_fonte:$('#fonte'+id).val(),
      id_nativo:$('#id_nativo'+id).val(),
      publico:document.getElementById('publico'+id).checked ? 1 : 0,
      substituicao:document.getElementById('substituicao'+id).checked ? 1 : 0, //$('#substituicao'+id).val(),
          checar_glifos:document.getElementById('checar_glifos'+id).checked ? 1 : 0, //$('#checar_glifos'+id).val(),
          binario:document.getElementById('bin'+id).checked ? 1 : 0, //$('#bin'+id).val(),
          iniciadores:$('#iniciadores'+id).val(),
          separadores:$('#separadores'+id).val(),
          sep_sentencas:$('#sep_sentencas'+id).val(),
          inic_sentencas:$('#inic_sentencas'+id).val(),
      tamanho:$('#tamanho'+id).val(),
      iid:<?=$id_idioma?>
          // substituicao
      
    }, function (data){
      if ($.trim(data) > 0){
        $('#btnSalvar'+id).hide();
              location.reload(true);
      }else{
        alert(data);
      };
    });
  };

  function addCaractere(eid, detalhes = '', cid = 0, glifo = '', vars = ''){ 
      glifo = glifo.replaceAll("*","'");
      vars = vars.replaceAll("*","'");

      $("#va").val(vars);
      $("#char").val(glifo);
      $("#desc").val(detalhes);
      $("#eid").val(eid);
      $("#cid").val(cid);

      $("#va").attr("class","form-control custom-font-"+eid);
      $("#char").attr("class","form-control custom-font-"+eid);

      $("#modal-add-caractere").modal('show');

  };

  function execAddCaractere(){ 

    var cid = $('#cid').val();
    var eid = $('#eid').val();

    var ch = $('#char').val();
    var desc = $('#desc').val();
    var va = $('#va').val();
    if(ch.length != 1){
        $.alert('Insira um único caractere, diacrítico ou caractere com diacrítico');
        return false;
    };
    $.get("api.php?action=ajaxAddCaractereEscrita&eid="+eid+"&cid="+cid+"&c="+ch+"&desc="+desc+"&vars="+va, function (data){
        if(data>0) {
          carregarTabelaAlfabeto(eid);
          $("#modal-add-caractere").modal('hide');
        }
        else alert(data);
    });
    
  }

  function editarMetadado(eid){ alert('to do'); return;
      alert('a fazer, editar colunas, e qqr uma delas servirá como ordenação, configure o padrão, p ex, numero simples (com autoordenar) ou extra, tipo numero de traços ou outra info');
  };

  function checarAutoIPA(el = 'teclaauto', dest = 'autoipa'){
      // let data = getChecarPronuncia('<?=$id_idioma?>', $("#"+el).val(), 1);
      $.post('api.php?action=getChecarPronuncia&iid=<?=$id_idioma?>',{
        p: $("#"+el).val()
      }, function (data){
      if(data=='-1'){ 
        $("#"+dest).val( '' );
      }else{
              $("#"+dest).val( data );
      };
    } );
  };

  function addSubstituicao(eid,id,tecla = '',glifo = ''){ 
      tecla = tecla.replaceAll("*","'");
      glifo = glifo.replaceAll("*","'");

      $("#teclaauto").val(tecla);
      $("#glifo").val(glifo);
      $("#autoid").val(id);
      $("#eid").val(eid);
      $("#glifo").attr("class","form-control custom-font-"+eid);

      $("#modal-autosubstituicao").modal('show');

      checarAutoIPA();

  }

  function execAddSubstituicao(){

    if($('#teclaauto').val()==''){
        $.alert('Insira um ou mais caracteres de entrada!');
        return false;
    };

    $.get("api.php?action=ajaxEditarAutosubstituicao&eid="+$('#eid').val()+"&id="+$('#autoid').val()+"&g="+$('#glifo').val()+"&k="+$('#teclaauto').val(), function (data){
        //carregaTabelaSubstituicao();
        if(data>0) {
          carregarTabelaEscrita($('#eid').val());
          $("#modal-autosubstituicao").modal('hide');
        }
        else alert(data);
    });
  }

  function salvarSubstituicao(eid){
      alert('a fazer salvar substituicao automatica');
      // add na tbl
  };

  function moverAcima(id,eid){
      $.get("api.php?action=ajaxGlifoAcima&id="+id+"&eid="+eid, function (data){
          if(data=='ok'){
              carregarTabelaAlfabeto(eid);
          }else{
              alert(data);
          }
      });
  };

  function moverAbaixo(id,eid){
      $.get("api.php?action=ajaxGlifoAbaixo&id="+id+"&eid="+eid, function (data){
          if(data=='ok'){
              carregarTabelaAlfabeto(eid);
          }else{
              alert(data);
          }
      });
  };

  function setPadrao(eid){

      // se tá td salvo, btnsalvar hidden
      if($('#btnSalvar'+eid).css('display') == 'none'){
        
          $.get("api.php?action=ajaxSetEscritaPadrao&eid="+eid+"&iid=<?=$id_idioma?>", function (data){
              if(data=='ok') {
                location.reload(true);
              }
              else alert(data);
          });
      }else{
        alert('Salve primeiro!');
      }
  }

  function novoSistema(){ 
    var fonte = $('#fonteid').val();
    var enome = $('#enome').val();
    if(enome==''){
        alert('<?=_t('Nome vazio!')?>');
        return false;
    };
    if(fonte < 1){
        alert('<?=_t('Selecione uma opção de fonte!')?>');
        return false;
    };
    if(fonte == '3' && !confirm('<?=_t('Usando esta opção (fonte desenhada), você deve desenhar os caracteres diretamente na tela.')?>') ){
        return false;
    };
    $.get("api.php?action=ajaxNovaEscrita&iid=<?=$id_idioma?>&f="+fonte+"&n="+enome, function (data){
        //carregaTabela();
        if(data>0) location.reload(true);
        else alert(data);
    });
  };
  
  function selectNativo(id,selected = '0'){

      let data = <?=getLastChange('lexicon',$id_idioma)?>;
      if (data > localStorage.getItem("k_opwords_<?=$id_idioma?>_updated")){
          console.log('local words outdated > update');
          $.get("api.php?action=getOptionsListWords&iid=<?=$id_idioma?>&eid="+id+"&selected="+selected, function (lex){

              $("#id_nativo"+id).html(lex);

              localStorage.setItem("k_opwords_<?=$id_idioma?>", lex);
              localStorage.setItem("k_opwords_<?=$id_idioma?>_updated", data);
              createTablerSelectNativeWords("id_nativo"+id,'<?=$fonte?>','<?=$tamanho?>');
              updateTablerSelect("id_nativo"+id,selected);
              $("#btnSalvar"+id).hide();
          });
      }else{
          console.log('local words load');
          $("#id_nativo"+id).html( localStorage.getItem("k_opwords_<?=$id_idioma?>") );
          createTablerSelectNativeWords("id_nativo"+id,'<?=$fonte?>','<?=$tamanho?>');
          updateTablerSelect("id_nativo"+id,selected);
          $("#btnSalvar"+id).hide();
      };

  };

  function apagarEscrita(eid,np =0){
      // GET num palavras na escrita
      var p = "";
      if (np>0) p = " das "+np+" palavras que o usam";
      if(confirm("<?=_t('Deseja mesmo apagar esta escrita? As palavras não serão removidas, apenas sua forma escrita.')?>")) {

          // GET delete escrita, q apaga ela e as palavrasNtivas dela, ver tbm outras deps, como autosubstituições, e tbm apaga a fonte se só ela usa
          $.get("api.php?action=ajaxDeleteEscrita&id="+eid, function (data){
              if ($.trim(data) == 'ok') location.reload(true);
              else alert(data);
          });

      }
  }

  function remAutosubs(id,eid){
      if(confirm("<?=_t('Deseja mesmo apagar?')?>"))
      $.get("api.php?action=ajaxDeleteAutosubstitution&id="+id, function (data){
          if ($.trim(data) == 'ok') carregarTabelaEscrita(eid);
          else alert(data);
      });
  }

  function apagarGlifo(id,eid){
      if(confirm("<?=_t('Deseja mesmo apagar este caractere do alfabeto?')?>"))
      $.get("api.php?action=ajaxDeleteGlifo&id="+id+"&eid="+eid, function (data){
          if ($.trim(data) == 'ok') carregarTabelaAlfabeto(eid);
          else if ($.trim(data)>0){
              if(confirm("Este caractere tem "+$.trim(data)+" usos, entre palavras e autosubstituições. Apagar mesmo assim? Isso tornará tais palavras e autosubstituições em erros!")) {

                $.get("api.php?action=ajaxDeleteGlifo&force=1&id="+id+"&eid="+eid, function (data2){
                    if ($.trim(data2) == 'ok') carregarTabelaAlfabeto(eid);
                    else alert(data2);
                });

              }
          }else alert(data);
      });
  }

  function addSubstituicaoDraw(eid,id,tecla = '',glifo = ''){ 
      tecla = tecla.replaceAll("*","'");
      glifo = glifo.replaceAll("*","'");

      $("#teclaautow").val(tecla);
      $("#glifow"+eid).val(glifo);
      $("#autoid").val(id);
      $("#eid").val(eid);
      $("#glifow"+eid).attr("class","form-control custom-font-"+eid);

      $.get("api.php?action=ajaxLoadAlphabetDrawSubs&eid="+$('#eid').val(), function (data){
          $("#subsDraw").html(data);
          $("#modal-autosubstituicaodraw").modal('show');
          checarAutoIPA('teclaautow','autoipaw');
          $("input[name=glifow"+$('#eid').val()+"][value=" + glifo + "]").attr('checked', 'checked');
      });

  }

  function execAddSubstituicaoDraw(){

    if($('#teclaautow').val()==''){
        $.alert('Insira um ou mais caracteres de entrada!');
        return false;
    };

    var sel = document.querySelector('input[name="glifow'+$('#eid').val()+'"]:checked').value; //$('#glifow'+eid).val()
    
    $.get("api.php?action=ajaxEditarAutosubstituicao&eid="+$('#eid').val()+"&id="+$('#autoid').val()+"&gw="+sel+"&k="+$('#teclaautow').val(), function (data){
        //carregaTabelaSubstituicao();
        if(data>0) {
          carregarTabelaEscrita($('#eid').val());
          $("#modal-autosubstituicaodraw").modal('hide');
        }
        else alert(data);
    });
    
  }
  
</script>

<div class="modal modal-blur" id="modal-novo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Novo sistema de escrita')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Nome')?></label>
            <input type="text" class="form-control" placeholder="<?=_t('Nome legível')?>" id="enome" />
        </div>
        <div class="mb-3">
          <label class="form-label"><?=_t('Fonte')?></label>
          <select class="form-select" id="fonteid">
            <option value="0" selected><?=_t('Selecione a fonte')?>...</option>
            <option value="3"><?=_t('Desenhada')?></option>
            <?php 
              $refs = mysqli_query($GLOBALS['dblink'],"SELECT * FROM fontes;");
              while($r = mysqli_fetch_assoc($refs)) {
                echo '<option value="'.$r['id'].'">'.$r['nome'].'</option>';
              }	
            ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="novoSistema()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>


<div class="modal modal-blur" id="modal-add-caractere" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Caractere')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <div class="mb-3">
            <label class="form-label"><?=_t('Caractere base (ex.: a)')?></label>
            <input type="text" class="form-control custom-font" id="char" />
        </div>
        <div class="mb-3">
            <label class="form-label"><?=_t('Variações (separadas por espaço) (ex.: á à ã)')?></label>
            <input type="text" class="form-control custom-font" id="va" />
            <span class="text-secondary"><?=_t('Considera-se o mesmo caractere com diferentes representações, como diacríticos ou marcas vocálicas.')?></span>
        </div>
        <div class="">
            <label class="form-label"><?=_t('Descrição')?></label>
            <input type="text" class="form-control" placeholder="<?=_t('ex.: á à ã')?>" id="desc" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execAddCaractere()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<input type="hidden"id="autoid"/>
<input type="hidden"id="eid"/>
<input type="hidden"id="cid"/>

<div class="modal modal-blur" id="modal-autosubstituicao" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Autosubstituição')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Caractere(s) de entrada (use os mesmos inputs usados na tela de Sons)')?></label>
            <input type="text" class="form-control" id="teclaauto" onkeyup="checarAutoIPA()" placeholder="<?=_t('Entrada (Ex.: á)')?>" />
            <input type="text" class="form-control disabled" readonly  id="autoipa"/>
        </div>
        <div class="mb-3">
            <label class="form-label"><?=_t('Caractere(s) de saída')?></label>
            <input type="text" class="form-control" id="glifo" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execAddSubstituicao()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" id="modal-autosubstituicaodraw" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Autosubstituição')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Caractere(s) de entrada (use os mesmos inputs usados na tela de Sons)')?></label>
            <input type="text" class="form-control" id="teclaautow" onkeyup="checarAutoIPA('teclaautow','autoipaw')" placeholder="<?=_t('Entrada (Ex.: á)')?>" />
            <input type="text" class="form-control disabled" readonly  id="autoipaw"/>
        </div>
        <div class="mb-3">
            <label class="form-label"><?=_t('Caractere de saída')?></label>
            <div class="form-selectgroup" id="subsDraw"></div>
            <!--input type="text" class="form-control" id="glifow" /-->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="execAddSubstituicaoDraw()"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" tabindex="-1" id="modal-draw-caractere" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Caractere')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="signature position-relative mb-3">
          <div class="position-absolute top-0 end-0 p-2">
            <div class="btn btn-icon" id="drawchar-wplus" data-bs-toggle="tooltip" aria-label="Clear signature" data-bs-original-title="<?=_t('Linha média')?>">
              +
            </div>
            <div class="btn btn-icon" id="drawchar-wminus" data-bs-toggle="tooltip" aria-label="Clear signature" data-bs-original-title="<?=_t('Linha fina')?>">
              -
            </div>
            <div class="btn btn-icon" id="drawchar-undo" data-bs-toggle="tooltip" aria-label="Desfazer" data-bs-original-title="<?=_t('Desfazer')?>">
              Desfazer
            </div>
            <div class="btn btn-icon" id="drawchar-clear" data-bs-toggle="tooltip" aria-label="Limpar" data-bs-original-title="<?=_t('Limpar')?>">
              X
            </div>
          </div>
          <canvas id="drawchar" width="300" height="300" class="signature-canvas" style="touch-action: none; user-select: none; color:var(--tblr-primary);"></canvas>
        </div>
        <div class="mb-3">
            <label class="form-label"><?=_t('Descrição')?></label>
            <input type="text" class="form-control" placeholder="<?=_t('Nome/detalhes')?>" id="desc2" />
        </div>
        
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="saveDrawChar"><?=_t('Salvar')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal modal-blur" tabindex="-1" id="modalFontes" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=_t('Fontes')?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="bodyModalFontes">
        
        
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?=_t('Carregar Nova Fonte')?></label>
            <input type="text" class="form-control" id="fontName" placeholder="Nome da fonte">
            <input type="file" class="form-control" id="fontFile" accept=".ttf">
        </div>
        <button type="button" class="btn btn-primary" onclick="carregarFonte()"><?=_t('Carregar')?></button>
      </div>
      <div class="modal-footer">
      </div>
    </div>
  </div>
</div>

<script src="./dist/libs/signature_pad/dist/signature_pad.umd.min.js?1745260900" defer=""></script>
<script>

const canvas = document.getElementById("drawchar");
var signaturePad;
var undoData = [];

$(document).ready(function(){
    signaturePad = new SignaturePad(canvas, {
        backgroundColor: "transparent",
        penColor: getComputedStyle(canvas).color,
        minWidth: 1,
        maxWidth: 3
    });
    //console.log(getComputedStyle(canvas));

    document.querySelector("#drawchar-clear").addEventListener("click", function () {
        signaturePad.clear();
    });
    function resizeCanvas() {
      const ratio = Math.max( 1, 1);
      console.log(canvas.offsetWidth, canvas.offsetHeight);
      canvas.width = canvas.offsetWidth * ratio;
      canvas.height = canvas.offsetWidth  * ratio;
      canvas.getContext("2d").scale(ratio, ratio);
      signaturePad.fromData(signaturePad.toData());
    }
    window.addEventListener("resize", resizeCanvas);
    window.addEventListener("shown.bs.modal", resizeCanvas);
    
    document.querySelector("#drawchar-clear").addEventListener("endStroke", () => {
        // clear undoData when new data is added
        undoData = [];
    });
    document.querySelector("#drawchar-undo").addEventListener("click", () => {
      const data = signaturePad.toData();

      if (data && data.length > 0) {
        // remove the last dot or line
        const removed = data.pop();
        undoData.push(removed);
        signaturePad.fromData(data);
      }
    });
    function dataURLToBlob(dataURL) {
      const parts = dataURL.split(";base64,");
      const contentType = parts[0].split(":")[1];
      const raw = window.atob(parts[1]);
      const rawLength = raw.length;
      const uInt8Array = new Uint8Array(rawLength);
      for (let i = 0; i < rawLength; ++i) {
        uInt8Array[i] = raw.charCodeAt(i);
      }
      return new Blob([uInt8Array], { type: contentType });
    }
    document.querySelector("#drawchar-wplus").addEventListener("click", () => {
      signaturePad.minWidth = 4; // inputs?
      signaturePad.maxWidth = 6; // inputs?
    });
    document.querySelector("#drawchar-wminus").addEventListener("click", () => {
      signaturePad.minWidth = 1; // inputs?
      signaturePad.maxWidth = 3; // inputs?
    });
    document.querySelector("#saveDrawChar").addEventListener("click", () => {
      var data = signaturePad.toData();
      const dataURL = signaturePad.toDataURL();
      const vdataURL = signaturePad.toDataURL("image/svg+xml");
      execDrawCaractere(data,dataURL,vdataURL);
    }); 
    resizeCanvas();
});


function drawCaractere(eid, detalhes = '', cid = 0, glifo = '', vars = '',vetor = "[]"){ 

    glifo = glifo.replaceAll("*","'");
    vars = vars.replaceAll("*","'");

    $("#va").val(vars);
    $("#char").val(glifo);
    $("#desc2").val(detalhes);
    $("#eid").val(eid);
    $("#cid").val(cid);

    //$("#va").attr("class","form-control custom-font-"+eid);
    //$("#char").attr("class","form-control custom-font-"+eid);

    if (vetor != '') signaturePad.fromData(JSON.parse(vetor));

    $("#modal-draw-caractere").modal('show'); //$("#modal-draw-caractere").modal('show');

};

function execDrawCaractere(vetor,imagem, svg){ 
    
    var cid = $('#cid').val();
    var eid = $('#eid').val();

    var ch = $('#char').val();
    var desc = $('#desc2').val();
    var va = $('#va').val();
    vetor = JSON.stringify(vetor);
    
    
    $.post("api.php?action=ajaxAddDrawCaractereEscrita&eid="+eid+"&cid="+cid+"&c="+ch+"&desc="+desc+"&vars="+va, 
      {vetor:vetor,png: imagem,svg: svg}, function (data){
      if(data>0) {
        carregarTabelaAlfabeto(eid);
        $("#modal-draw-caractere").modal('hide');
      }
      else alert(data);
    }); 
} 
</script>