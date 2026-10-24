🌐 **Esperanto** | [Português](README.pt.md) | [English](README.md) | [日本語](README.ja.md)

# Kondisonair

**Kondisonair** estas platformo, kiun mi kreis por organizi kaj dividi miajn artajn lingvojn, sed mi publikigas ĝin ĉar mi pensas, ke ĝi povas esti utila al aliaj kreintoj de konlangoj. Agordu sonojn, silabformojn, vortklasojn, skribsistemojn, leksikon, tabelojn de konjugacio kaj fleksio, kaj tekstojn en viaj konlangoj. Ĝi inkluzivas ilojn kiel vortgeneratoron kaj sonŝanĝilon por simuli la evoluon de lingvoj.

## Funkcioj
- **Organizado de konlangoj**: Kreu kaj redaktu lingvojn kun detaloj kiel:
  - Sonoj: Difinu tabelojn de fonemoj.
  - Silabformoj: Strukturu silabojn.
  - Vortklasoj: Organizu substantivojn, verbojn ktp.
  - Tabeloj de konjugacio kaj fleksio: Konstruu paradigmojn kaj aŭtomatajn konjugaciojn por verboj, substantivoj ktp.
  - Skribsistemoj: Kreu proprajn alfabetojn aŭ skribojn.
  - Leksikono: Aldonu vortprovizon kun detalaj signifoj.
- **Dividado**: Publikigu viajn lingvojn por la komunumo rekte en la platformo.
- **Vortgeneratoro**: Aŭtomate generu vortojn laŭ silabmodeloj.
- **Sonŝanĝilo**: Simulu sonŝanĝojn.
- **Tekstoj en konlangoj**: Alŝutu tekstojn kaj ekzercu per interagaj legaj ekzercoj.
- **Organizado de mondoj**: Kreu kaj redaktu la mondojn, kie viaj lingvoj estas parolataj:
  - Tempsistemoj: Kreu proprajn kalendarojn.
  - Estaĵoj: Enmetu rolulojn kaj lokojn en vian mondon.
  - Parametroj: Ligu parametrojn al estaĵoj por sekvi progreson.
  - Templinio: Enmetu gravajn momentojn de la historio.
  - Rakontoj: Verku tekstojn ligitajn al estaĵoj kaj momentoj.

## Uzataj teknologioj
- PHP, MySQL, JavaScript, [Tabler](https://tabler.io), [jQuery](https://jquery.com) ktp.

**Noto**: Ĉi tio estas simpla projekto, sen Composer aŭ npm. Dependecoj estas inkluzivitaj en la dosieroj.

**Noto 2**: Multaj partoj de la kodo estis kreitaj helpe de artefarita inteligento.

## Instalado
1. Postuloj: retservilo kun **PHP** (kun la etendaĵo PDO MySQL) kaj **MySQL/MariaDB**.
2. Malpakigu la dosierojn en dosierujon servatan de via retservilo (ekz. `htdocs/kondisonair`). La servilo devas povi skribi en la dosierujo, por ke la instalilo kreu `db.php`.
3. Malfermu `install.php` en la retumilo (ekz. `http://localhost/kondisonair/install.php`).
4. Plenigu la gastiganton, uzanton, pasvorton kaj nomon de la datumbazo (ĝi estas kreata, se ĝi ne ekzistas), kaj ankaŭ la nomon/pasvorton de la administranto kaj la defaŭltan lingvon.
5. Fine la instalilo kreas `db.php` kun la datumbaza aliro kaj vi povas ensaluti. Se vi bezonas ŝanĝi la datumbazan aliron poste, redaktu `db.php`.

Por rulumi la instalilon denove, unue forigu `db.php` (tio ne forigas la datumbazon).

## Licenco
Ĉi tiu projekto estas licencita sub la [GNU GPLv3](LICENSE).

**Kondisonair** estas nur hobia projekto, sed bonvolu libere provi ĝin!
