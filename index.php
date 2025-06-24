<?php
require('api.php'); 
?>

<!DOCTYPE html> 
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?=$tituloPagina?></title>
    <!-- CSS files -->
    <link href="dist/css/tabler2.min.css?1692870487" rel="stylesheet"/>
    <link href="dist/css/tabler-flags.min.css?1692870487" rel="stylesheet"/>
    <link href="dist/css/tabler-payments.min.css?1692870487" rel="stylesheet"/>
    <link href="dist/css/tabler-vendors.min.css?1692870487" rel="stylesheet"/>
    <link href="kondisonair.css" rel="stylesheet"/>    
    
    <link href="dist/css/tabler-themes.css" rel="stylesheet"/>
    
    <script src="jquery.min.js"></script>
    <script src="dist/js/demo-theme2.min.js?1692870487"></script>
    

  </head>
  <body >
    <div class="page">

      <header class="navbar navbar-expand-md d-print-none" >
        <div class="container-xl">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="index.php">
              <img src="logo.png" width="60" height="16" alt="Kondisonair" class="navbar-brand-image">
            </a>
          </h1>
          <div class="navbar-nav flex-row order-md-last">
            <div class="d-none d-md-flex">
              <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSettings" onclick="loadExtraPanel('theme')">
                <span class="nav-link-icon d-md-none d-lg-inline-block">
                  <!-- Download SVG icon from http://tabler.io/icons/icon/settings -->
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                    <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"></path>
                    <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                  </svg>
                </span>
              </a>

              <?php if($_SESSION['KondisonairUzatorIDX']>0) { ?>
              <!--div class="nav-item dropdown d-none d-md-flex me-3">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
                  <span class="badge bg-red"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title"><?=_t('Notificações')?></h3>
                    </div>
                    <div class="list-group list-group-flush list-group-hoverable" id="div-list-notifications">

                    </div>
                  </div>
                </div>
              </div-->
              <?php } ?>
            </div>
            <div class="nav-item dropdown">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                <div class="d-none d-xl-block ps-2">
                <?php if($_SESSION['KondisonairUzatorIDX']>0) { ?>
                  <div><?=$_SESSION['KondisonairUzatorID']?></div>
                  <div class="mt-1 small text-secondary"><?=_t('Usuário')?></div>
                <?php } else { ?>
                  <div><?=_t('Olá')?></div>
                  <div class="mt-1 small text-secondary"><?=_t('Visitante')?></div>
                <?php } ?>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <?php if($_SESSION['KondisonairUzatorIDX']>0) { ?>
                  <a href="?page=profile" class="dropdown-item"><?=_t('Perfil')?></a>
                  <div class="dropdown-divider"></div>
                  <a href="?page=settings" class="dropdown-item"><?=_t('Configurações')?></a>
                  <a href="?action=logout" class="dropdown-item"><?=_t('Sair')?></a>
                <?php } else { ?>
                  <a href="?page=login" class="dropdown-item"><?=_t('Entrar')?></a>
                  <a href="?page=signup" class="dropdown-item"><?=_t('Cadastrar-se')?></a>
                <?php } ?>
              </div>
            </div>
          </div>
          <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
              
              <ul class="navbar-nav">
                
                <?php if($_SESSION['KondisonairUzatorIDX']>0) { ?>                
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false" >
                    <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/star -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l3 3l8 -8" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                    </span>
                    <span class="nav-link-title">
                      <?=_t('Meus projetos')?>
                    </span>
                  </a>
                  <div class="dropdown-menu">
                    <div class="dropdown-menu-columns">
                      <div class="dropdown-menu-column">
                        <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>
                        <a class="dropdown-item" href="?page=mylanguages">
                          <?=_t('Meus idiomas')?>
                        </a>
                        <a class="dropdown-item" href="?page=myarticles">
                          <?=_t('Meus artigos')?>
                        </a>
                        <a class="dropdown-item" href="?page=myworlds">
                          <?=_t('Minhas realidades')?>
                        </a>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                </li>

                <?php } ?>

                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false" >
                    <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/star -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" /></svg>
                    </span>
                    <span class="nav-link-title">
                      <?=_t('Ferramentas')?>
                    </span>
                  </a>
                  <div class="dropdown-menu">
                    <div class="dropdown-menu-columns">
                      <div class="dropdown-menu-column">
                        <?php if($_SESSION['KondisonairUzatorIDX']>0){ ?>
                        <a class="dropdown-item" href="?page=wordbanks">
                        <?=_t('Bancos de palavras')?>
                        </a>
                        <a class="dropdown-item" href="?page=phrases">
                        <?=_t('Frases')?>
                        </a>
                        <a class="dropdown-item" href="?page=texts">
                          <?=_t('Textos')?>
                        </a>
                        <?php } ?>
                        <!--a class="dropdown-item" href="?page=courses">
                        <?=_t('Cursos')?>
                        </a-->
                        <a class="dropdown-item" href="?page=changer">
                        <?=_t('Alterador sonoro')?>
                        </a>
                        <a class="dropdown-item" href="?page=wordgen">
                        <?=_t('Gerador de palavras')?>
                        </a>
                      </div>
                    </div>
                  </div>
                </li>

              </ul>
              
            </div>
          </div>
        </div>
      </header>

      <script type="text/javascript" src="kondisonair.js"></script>

      <div class="page-wrapper">
        <?php
        if($page==''){
          require("views/start.php");
        }else{
          require("views/".$page.".php");
        };
        ?>
        <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                  <?php
                  echo gerarLinksIdiomas($_SESSION['KondisonairUzatorDiom'], true);
                  ?>
                </ul>
              </div>
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">
                    <a href="https://github.com/kiyoshispreclerg/kondisonair" class="link-secondary" rel="noopener">
                    <?=_t('Versão')?> <?=$versaoK1.'.'.$versaoK2.'.'.$versaoK3?> &copy; <?=date('Y')?>
                    </a>
                  </li>
                  <li class="list-inline-item">
                    
                    <a href="https://kiyoshi.42web.io/" class="link-secondary">Kiyoshi Spreclerg</a>.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
        
      </div>
    </div>

    <div class="settings"><form class="offcanvas offcanvas-end offcanvas-narrow" tabindex="-1" id="offcanvasSettings"></form></div>

    <!-- Libs JS -->
    
    <script src="./dist/libs/apexcharts/dist/apexcharts.min.js?1692870487" defer></script>
    <script src="./dist/libs/jsvectormap/dist/js/jsvectormap.min.js?1692870487" defer></script>
    <script src="./dist/libs/jsvectormap/dist/maps/world.js?1692870487" defer></script>
    <script src="./dist/libs/jsvectormap/dist/maps/world-merc.js?1692870487" defer></script>
    <script src="./dist/libs/tom-select/dist/js/tom-select.base.min.js?1692870487" defer></script>
    <script src="./dist/libs/list.js/dist/list.min.js?1692870487" defer></script>

    <script src="./dist/libs/tinymce/tinymce.min.js?1692870487" defer></script>

    <!-- Tabler Core -->
    <script src="./dist/js/tabler2.min.js?1692870487" defer></script>
    <script src="./dist/js/demo.min.js?1692870487" defer></script>

    <script>
      globalFonts(<?=getLastChange('fonts')?>);
    </script>

  </body>
</html>
