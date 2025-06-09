<div class="page-body">
  <div class="container-xl">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">

        <div class="card card-lg">
          <div class="card-body markdown">
            <h1>Alterador Sonoro do Kondisonair</h1>
            <div class="mb-3">
              <h3 class="mb-3">Como utilizar</h3>
              Inspirado em outros alteradores bastante conhecidos, o KSC permite integrar as ferramentas do Kondisonair diretamente no alterador, como a criação automática 
              das categorias (aqui chamadas de classes) e conjugações/flexões das palavras no dicionário. O KSC também pode ser usado por si só, buscando a facilidade e simplicidade 
              do SCA2 mas querendo a potência e versatilidade do Lexurgy.
            </div>
            <div class="mb-3">
              <h3 class="mb-3">Definição de classes</h3>
              As classes de sons definidas em Sílabas são carregadas automaticamente, bem como os tipos de articulação e produção sonora existentes na tabela Sons, mas você pode incluir mais classes, inclusive com tokens de mais de um caractere 
              (separando por vírgulas, com espaços opcionais), usando uma letra maiúscula ou uma palavra iniciada por maiúscula. Para maior clareza, usamos o nome "token" para cada posição 
              na palavra, pois em classes com sons com mais de um caractere (como ligaturas, ou dígrafos, caso utilize romanização), o KSC pode tratá-los como um único token, em vez de vários caracteres separados. 
              Também é possível incluir uma classe dentro de outra.
              <ul>
                <li>C=ptk</li>
                <li>C=p,t,k</li>
                <li>C = {p, t, k}</li>
                <li>Cons = ptk</li>
                <li>Ejective = pK, tK, kK</li>
                <li>Vogal = {Atona, Tonica}</li>
              </ul>
            </div>

            <div class="mb-3">
              <h3 class="mb-3">Regras suportadas</h3>
              O KSC permite diferentes formatos para você usar o que achar melhor. O formato base é: origem / destino / contexto(s) / exclusão(ões).
              Origem e destino devem ter a mesma quantidade de tokens, senão serão considerados literais, que indicam substituição direta. Tokens podem ser tanto os próprios símbolos IPA 
              do inventário do idioma quanto quaisquer caracteres digitáveis num teclado padrão. Origem e destino podem ser separados com barra ou seta, 
              conforme o que achar melhor. Contextos e exclusões podem ser múltiplos separados por vírgulas. Espaços são ignorados.
              No uso de articulações (nomes das colunas e linhas em Sons), é possível selecionar um token que seja parte de duas classes ao mesmo tempo, usando colchetes.
              <ul>
                <li>a/u</li>
                <li>a →u</li>
                <li>a > u</li>
                <li>a=> u</li>
                <li>a => u / _ C</li>
                <li>a => u / _C, t_ / _h, #_</li>
                <li>a => e / _ [Sibilante Coronal]#</li>
                <li>a => e / _ {[Sibilante Coronal], s}</li>
              </ul>
            </div>
            
            <div class="mb-3">
              Além dos caracteres literais, é possível usar classes predefinidas do idioma (cujos sons terão aqui como símbolo as teclas, caso tenham, em vez do IPA), 
              classes iniciadas direto nas regras e classes de correspondência direta, desde que tenham a mesma quantidade de tokens tanto na origem quanto no destino.
              Também pode-se definir caracteres opcionais nas buscas de contextos e exclusões.
              <ul>
                <li>a => u / _ C</li>
                <li>A => T / # _ / _ r</li>
                <li>Glotal => Ejetiva / _ Tonica, _ Alta</li>
              </ul>
            </div>
            
            <div class="mb-3">
              Outros tokens importantes são: # (borda da palavra), ~ (vazio, para inserção se estiver na origem, ou 
              remoção se estiver no destino; também pode ser ∅, ø ou 0), % (qualquer caractere, apenas um),
              opcionais (caracteres entre parêntesis, inclusive classes, juntos ou separados por vírgulas) e capturas (números de 1 a 9 que representam o caractere
              na origem na mesma posição do número). Capturas podem ser úteis para remoção, inserção e inversão de sons, 
              e elas funcionam mesmo se o número de itens na origem e no destino forem diferentes.
              <ul>
                <li>~a/wa/{k,g}_</li>
                <li>wa/∅a/#{k,g}_</li>
                <li>{k,p,t} => {c,pf,ts} / _ (j,w)a</li>
                <li>{r,l}T{b,v} => 3T1</li>
                <li>Plosiva 1 => 1 / _ Atona</li>
              </ul>
              Outros tokens úteis disponíveis para contextos são o repetidor + e o coringa *. O repetidor indica que deve haver mais de um do caractere anterior para o contexto ser válido. 
              Caso o token anterior esteja entre parêntesis, o que o torna opcional, a regra é que haja quantos caracteres houverem, de zero ao limite.
              Podem ser úteis para regras mais complexas, como harmonia vocálica e dissimilação. 
              Já o coringa indica uma sequência qualquer de caracteres. Também pode-se utilizar ? para representar qualquer som de qualquer classe.
              <ul>
                <li>Back => Front / _?+i</li>
                <li>l => r / l?+_alis</li>
                <li>C => CC / _V+</li>
                <li>e => i / _ * i</li>
              </ul>
            </div>
          </div>
          
        </div>

      </div>
    </div>
  </div>
</div>