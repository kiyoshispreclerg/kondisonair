function setLang(id){
    $.get("?action=setLanguage&i="+id,function(data){
        location.reload(true)
    })
}

function formatarTablerSelect(campo, parent = 'body', create = false){
  // @formatter:off
  document.addEventListener("DOMContentLoaded", function () {
      var el;
      window.TomSelect && (new TomSelect(el = document.getElementById(campo), {
          copyClassesToDropdown: false,
          dropdownParent: parent,
          create: create,
          controlInput: '<input>',
          render:{
              item: function(data,escape) {
                  /*if( data.customProperties ){
                      return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                  }*/
                  return '<div>' + escape(data.text) + '</div>';
              },
              option: function(data,escape){
                  /*if( data.customProperties ){
                      return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                  }*/
                  return '<div>' + escape(data.text) + '</div>';
              },
          },
      }));
  });
  // @formatter:on
};

function createTablerSelect(campo, parent = 'body', create = false){
  // @formatter:off
  if (campo == null) return;
      var el;
    new TomSelect(el = document.getElementById(campo), {
          copyClassesToDropdown: false,
          dropdownParent: parent,
          create: create,
          controlInput: '<input>',
          render:{
              item: function(data,escape) {
                  /*if( data.customProperties ){
                      return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                  }*/
                  return '<div>' + escape(data.text) + '</div>';
              },
              option: function(data,escape){
                  /*if( data.customProperties ){
                      return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                  }*/
                  return '<div>' + escape(data.text) + '</div>';
              },
          },
      });
  // @formatter:on
};

function createTablerSelectNativeWords(campo,fonte = '0', tamanho = ''){
  // @formatter:off
      var el;
    new TomSelect(el = document.getElementById(campo), {
          copyClassesToDropdown: false,
          dropdownParent: 'body',
          controlInput: '<input>',
          render:{
              item: function(data,escape) {
                  /*if( data.customProperties ){
                      return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                  }*/
                  if (fonte == 3) {
                    var tmp = '';
                    data.nativa.split(",").forEach(function(t){
                        tmp += '<span class="drawchar drawchar-'+tamanho+'" style="background-image: url(./writing/'+data.eid+'/'+t+'.png)"></span>';
                    });
                    return '<div>' + tmp + escape(data.text) + '</div>';
                  }else
                    return '<div><span class="custom-font-'+data.eid+'">' + data.nativa + '</span>' + escape(data.text) + '</div>';
              },
              option: function(data,escape){
                  if (fonte == 3) {
                    var tmp = '';
                    data.nativa.split(",").forEach(function(t){
                        tmp += '<span class="drawchar drawchar-'+tamanho+'" style="background-image: url(./writing/'+data.eid+'/'+t+'.png)"></span>';
                    });
                    return '<div>' + tmp + escape(data.text) + '</div>';
                  }else
                  return '<div><span class="custom-font-'+data.eid+'">' + data.nativa + '</span>' + escape(data.text) + '</div>';
              },
          },
      });
  // @formatter:on
};

function createTablerSelectAllNativeWords(campo){
  // @formatter:off
      var el;
    new TomSelect(el = document.getElementById(campo), {
          copyClassesToDropdown: false,
          dropdownParent: 'body',
          controlInput: '<input>',
          render:{
              item: function(data,escape) {
                  /*if( data.customProperties ){
                      return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                  }*/
                  if (data.f < 0) {
                    var tmp = '';
                    data.n.split(",").forEach(function(t){
                        tmp += '<span class="drawchar drawchar-'+data.t+'" style="background-image: url(./writing/'+data.eid+'/'+t+'.png)"></span>';
                    });
                    return '<div>' + tmp + escape(data.text) + '</div>';
                  }else
                    return '<div><span class="custom-font-'+data.eid+'">' + data.n + '</span> ' + escape(data.text) + '</div>';
              },
              option: function(data,escape){
                  if (data.f < 0) {
                    var tmp = '';
                    data.n.split(",").forEach(function(t){
                        tmp += '<span class="drawchar drawchar-'+data.t+'" style="background-image: url(./writing/'+data.eid+'/'+t+'.png)"></span>';
                    });
                    return '<div>' + tmp + escape(data.text) + '</div>';
                  }else
                  return '<div><span class="custom-font-'+data.eid+'">' + data.n + '</span> ' + escape(data.text) + '</div>';
              },
          },
      });
  // @formatter:on
};

function updateTablerSelect(campo,val){
    document.querySelector('#'+campo).tomselect.setValue(val);
}

async function sonalMdason(mdasonList, palavrList, mtor, elment, iid, defCats = ""){
    if (palavrList == '') return '';
        
    const formData = new FormData();
    formData.append('palavras', palavrList);
    formData.append('regras', mdasonList);
    //formData.append('categorias', defCats);
    formData.append('v', 0);
    formData.append('classes', defCats);
    // formData.append('substituicoes', document.getElementById('check_rewrites').checked ? document.getElementById('text_rewrites').value : 0);

    const response = await fetch(`?action=getKSC&iid=`+iid, {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();

    if (data.errors && Array.isArray(data.errors)) {
        alert(data.errors.join('\n').trim());
    }
    
    return Array.isArray(data.words) ? data.words.join('\n').trim() : ''; // data.trim();

};

function btnJoes(val,tipo,id,div=""){
    $.get("?action=ajaxJoes&t="+tipo+"&id="+id+"&l="+val,function(data){
        $("#joesDiv"+div).html(data);
    })
};

function testFilter(divClass,filterField,classe=0){ 
  
  const posts = [...document.getElementsByClassName(divClass)];
  posts.forEach(post => {
          post.classList.add("display-none");
      });

  let userInput = document.getElementById(filterField).value;
  //let searchQuery = [];

  if(classe > 0) {
      let k = "k" + classe + " ";
      const matchingPost = posts.filter(post => {
          const search = post.dataset.search;
          return ( 
              search.includes(userInput) 
              && search.includes(k)
          );// || tags.includes(searchQuery);
      });
      matchingPost.forEach(post => {
          if (post.classList.value.includes("display-none")) {
              post.classList.remove("display-none");
          }
      });
  }else{
      const matchingPost = posts.filter(post => {
          const search = post.dataset.search;
          return ( 
              search.includes(userInput) 
          );
      });
      matchingPost.forEach(post => {
          if (post.classList.value.includes("display-none")) {
              post.classList.remove("display-none");
          }
      });
  };

  const matchingPost = posts.filter(post => {
      const search = post.dataset.search;
      return ( 
          search.includes(userInput) 
          // && ( k || search.includes(classe) )
      );// || tags.includes(searchQuery);
  });

  /*const nonMatchingPost = posts.filter(post => {
      return !post.dataset.search.toLowerCase().includes(searchQuery);
  });*/

  //console.log(matchingPost);

  /*if (matchingPost) {
      nonMatchingPost.forEach(post => {
          post.classList.add("display-none");
      });
  }
  matchingPost.forEach(post => {
      if (post.classList.value.includes("display-none")) {
          post.classList.remove("display-none");
      }
  });
  */
}

var esperando = false;
function buscaGeral(){
if($('#inputBusca').val().length<1) return;
if(esperando) return;
//$("#divResBusca").html('<div class="loaderSpin"></div>');
$.post("?action=ajaxBuscaGeral&t="+ $('#inputBusca').val(), function (data){
    if(data>0) {
        esperando = true;
        setTimeout(function(){  esperando = false; buscaGeral();}, data*1000);
    }else{ 
        $('#divResBusca').html($.trim(data));
        setTimeout(function(){  esperando = false; }, data*1000);
    }
});
}

function globalFonts(data, force = false){ 
    var style = document.createElement('style');
    style.type = 'text/css';
    if (force || data > localStorage.getItem("k_fonts_updated")){
        console.log('local fonts outdated > update');
        $.get("api.php?action=getGlobalFonts", function (lex){
            localStorage.setItem("k_fonts", lex);
            localStorage.setItem("k_fonts_updated", lex);
            style.innerHTML = lex; document.getElementsByTagName('head')[0].appendChild(style);
        });
    }else{
        console.log('local fonts load');
        style.innerHTML = localStorage.getItem("k_fonts"); document.getElementsByTagName('head')[0].appendChild(style);
    }
}

function abrirSig(pid){
  $("#offcanvasHelperBody").load("api.php?action=fac&t=palavr&v1="+pid);
}

function appLoad(done = true){
  if (done) { $(".appLoad").show(); $(".appholder").hide(); }
  else{ $(".appLoad").hide(); $(".appholder").show(); }
}

function loadThemePanel() {
  var themeConfig = {
    theme: "light",
    "theme-base": "green",
    "theme-font": "sans-serif",
    "theme-primary": "green",
    "theme-radius": "1",
  };
  var url = new URL(window.location);
  var form = document.getElementById("offcanvasSettings");
  var resetButton = document.getElementById("reset-changes");
  var checkItems = function () {
    for (var key in themeConfig) {
      var value = window.localStorage["tabler-" + key] || themeConfig[key];
      if (!!value) {
        var radios = form.querySelectorAll(`[name="${key}"]`);
        if (!!radios) {
          radios.forEach((radio) => {
            radio.checked = radio.value === value;
          });
        }
      }
    }
  };
  form.addEventListener("change", function (event) {
    var target = event.target,
      name = target.name,
      value = target.value;
    for (var key in themeConfig) {
      if (name === key) {
        document.documentElement.setAttribute("data-bs-" + key, value);
        window.localStorage.setItem("tabler-" + key, value);
        url.searchParams.set(key, value);
      }
    }
    window.history.pushState({}, "", url);
  });
  resetButton.addEventListener("click", function () {
    for (var key in themeConfig) {
      var value = themeConfig[key];
      document.documentElement.removeAttribute("data-bs-" + key);
      window.localStorage.removeItem("tabler-" + key);
      url.searchParams.delete(key);
    }
    checkItems();
    window.history.pushState({}, "", url);
  });
  checkItems();
};

async function getLastChange(data, id) {
    const response = await fetch(`?action=getLastChange&data=${data}&${data === 'calendar' ? 'cid' : 'rid'}=${id}`);
    const timestamp = await response.text();
    return parseInt(timestamp) || 0;
}

async function fetchCalendarData(calId) {
    const calendarCacheKey = `k_calendar_${calId}`;
    const calendarUpdatedKey = `k_calendar_${calId}_updated`;

    const response = await fetch(`?action=getDadosCalendario&id=${calId}`);
    const calendarData = await response.json();

    if (!calendarData.error) {
        localStorage.setItem(calendarCacheKey, JSON.stringify(calendarData));
        const lastChange = await getLastChange('calendar', calId);
        localStorage.setItem(calendarUpdatedKey, lastChange.toString());
    }

    return calendarData;
}

async function fetchMomentsData(rid) {
    const momentsCacheKey = `k_momentos_${rid}`;
    const momentsUpdatedKey = `k_momentos_${rid}_updated`;

    const response = await fetch(`?action=getMomentos&rid=${rid}`);
    const momentsData = await response.json();

    if (!momentsData.error) {
        localStorage.setItem(momentsCacheKey, JSON.stringify(momentsData));
        const lastChange = await getLastChange('moments', rid);
        localStorage.setItem(momentsUpdatedKey, lastChange.toString());
    }

    return momentsData;
}

async function loadCalendar(
    containerId, 
    yearSelectId, 
    monthSelectId, 
    daysId, 
    bodyId, 
    warningsId, 
    calId = 1, 
    startYear = 0, 
    startMonth = 0, 
    timeValueId, 
    timeNameId, 
    rid, changed
) {
    // Chaves para localStorage
    const calendarCacheKey = `k_calendar_${calId}`;
    const momentsCacheKey = `k_momentos_${rid}`;

    let calendarData, momentsData;

    // changed checar aqui!!!

    try {
        // --- Carregar dados do calendário do localStorage ---
        let cachedCalendar = localStorage.getItem(calendarCacheKey);
        if (!cachedCalendar) {
            console.log('No cached calendar data, fetching from server');
            calendarData = await fetchCalendarData(calId);
            if (calendarData.error) {
                throw new Error(`Failed to fetch calendar data: ${calendarData.error}`);
            }
            // Dados já salvos no localStorage pelo fetchCalendarData
            cachedCalendar = localStorage.getItem(calendarCacheKey);
            if (!cachedCalendar) {
                throw new Error('Failed to cache calendar data');
            }
        } else {
            console.log('Loading calendar from localStorage');
        }
        calendarData = JSON.parse(cachedCalendar);
        if (calendarData.error) {
            throw new Error(calendarData.error);
        }

        // --- Carregar momentos ---
        let cachedMoments = localStorage.getItem(momentsCacheKey);
        if (!cachedMoments) {
            console.log('No cached moments data, fetching from server');
            momentsData = await fetchMomentsData(rid);
            if (momentsData.error) {
                console.warn('Failed to fetch moments data:', momentsData.error);
                momentsData = { momentos: [] }; // Fallback para lista vazia
            }
            // Dados já salvos no localStorage pelo fetchMomentsData
            cachedMoments = localStorage.getItem(momentsCacheKey) || JSON.stringify({ momentos: [] });
        } else {
            console.log('Loading moments from localStorage');
        }
        momentsData = JSON.parse(cachedMoments);
        if (momentsData.error) {
            console.warn('Error in cached moments data:', momentsData.error);
            momentsData = { momentos: [] };
        }

        const { time_system, units, cycles, days, months, leap_rules, warnings } = calendarData;
        const momentos = momentsData.momentos || [];

        // Exibir avisos
        const warningsPanel = document.getElementById(warningsId);
        if (warningsPanel && warnings.length > 0) {
            warningsPanel.innerHTML = warnings.map(w => `
                <div class="alert alert-warning alert-dismissable">${w.mensagem}<a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a></div>
            `).join('');
        }

        // Encontrar durações
        const momentsByDate = {};
        const dayUnit = units.find(u => u.equivalente === 'dia');
        const monthUnit = units.find(u => u.equivalente === 'mes');
        const yearUnit = units.find(u => u.equivalente === 'ano');
        const weekUnit = units.find(u => u.equivalente === 'semana');
        const daysPerMonthCycle = cycles.find(c => c.id_unidade === monthUnit.id && c.id_unidade_ref === dayUnit.id);
        const monthsPerYearCycle = cycles.find(c => c.id_unidade === yearUnit.id && c.id_unidade_ref === monthUnit.id);
        const daysPerWeekCycle = cycles.find(c => c.id_unidade === weekUnit.id && c.id_unidade_ref === dayUnit.id);
        const daysPerMonth = daysPerMonthCycle ? daysPerMonthCycle.quantidade : 30;
        const monthsPerYear = monthsPerYearCycle ? monthsPerYearCycle.quantidade : 12;

        console.log('===== monthUnit.id: '+monthUnit.id+' - yearUnit.id: '+yearUnit.id + ' - monthsPerYear: '+monthsPerYear)

        momentos.forEach(momento => {
            const totalSeconds = momento.time_value;
            const secondsPerDay = dayUnit.duracao;
            const secondsPerMonth = secondsPerDay * daysPerMonth;
            const secondsPerYear = secondsPerMonth * monthsPerYear;

            const year = Math.floor(totalSeconds / secondsPerYear);
            const remainingAfterYear = totalSeconds % secondsPerYear;
            const monthIndex = Math.floor(remainingAfterYear / secondsPerMonth);
            const remainingAfterMonth = remainingAfterYear % secondsPerMonth;
            const day = Math.floor(remainingAfterMonth / secondsPerDay) + 1;

            const dateKey = `${year}/${monthIndex}/${day}`;
            if (!momentsByDate[dateKey]) {
                momentsByDate[dateKey] = [];
            }
            momentsByDate[dateKey].push(momento);
        });

        // Preencher dropdown de meses
        const monthSelect = document.getElementById(monthSelectId);
        months.forEach((month, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = month.nome;
            monthSelect.appendChild(option);
        });

        // Configurar input de ano
        const yearInput = document.getElementById(yearSelectId);
        yearInput.value = 0; // Ano inicial

        // Preencher dias da semana
        const daysRow = document.getElementById(daysId);
        days.forEach(day => {
            const th = document.createElement('th');
            th.textContent = day;
            daysRow.appendChild(th);
        });

        // Função para aplicar regras de leaps
        function applyLeapRules(year, monthIndex) {
            let extraDays = 0;
            leap_rules.forEach(rule => {
                if (rule.id_unidade === yearUnit.id && eval(rule.condition.replace('year', year))) { // Substituir eval em produção
                    const targetUnit = units.find(u => u.id === rule.target_unidade);
                    if (targetUnit.equivalente === 'dia') {
                        extraDays += rule.add_units;
                    }
                }
            });
            return extraDays;
        }

        // Função para calcular o total de dias até o início do mês
        function calculateTotalDays(targetYear, targetMonth) {
            let totalDays = 0;
            const monthsPerYear = monthsPerYearCycle.quantidade;

            if (targetYear > 0) {
                for (let y = 0; y < targetYear; y++) {
                    for (let m = 0; m < monthsPerYear; m++) {
                        totalDays += parseFloat(typeof months[m] !== 'undefined' ? months[m].days : daysPerMonth/*months[0].days*/) + applyLeapRules(y, m);
                    }
                }
            } else if (targetYear < 0) {
                for (let y = targetYear; y < 0; y++) {
                    for (let m = 0; m < monthsPerYear; m++) {
                        totalDays -= parseFloat(typeof months[m] !== 'undefined' ? months[m].days : daysPerMonth/*months[0].days*/) + applyLeapRules(y, m);
                    }
                }
            }

            for (let m = 0; m < targetMonth; m++) {
                totalDays += parseFloat(typeof months[m] !== 'undefined' ? months[m].days : daysPerMonth/*months[0].days*/) + applyLeapRules(targetYear, m);
            }
            return totalDays;
        }

        // Função para renderizar os dias do mês
        function renderMonth(year, monthIndex) {
            const month = months[monthIndex];
            let daysInMonth = parseFloat(month.days);
            daysInMonth += applyLeapRules(year, monthIndex);
            const daysPerWeek = daysPerWeekCycle.quantidade;
            const tbody = document.getElementById(bodyId);
            tbody.innerHTML = '';

            const totalDays = calculateTotalDays(year, monthIndex);
            const startDayOfWeek = (totalDays % daysPerWeek + daysPerWeek) % daysPerWeek;

            let dayCounter = 1;
            const weeks = Math.ceil((startDayOfWeek + daysInMonth) / daysPerWeek);

            console.log(`Year: ${year}, Month: ${monthIndex}, Total Days: ${totalDays}, Start Day: ${startDayOfWeek}, Weeks: ${weeks}`);

            for (let i = 0; i < weeks; i++) {
                const tr = document.createElement('tr');
                for (let j = 0; j < daysPerWeek; j++) {
                    const td = document.createElement('td');
                    const currentPosition = i * daysPerWeek + j;

                    if (currentPosition >= startDayOfWeek && dayCounter <= daysInMonth) {
                        td.textContent = dayCounter;
                        td.classList.add('calendar-day');
                        td.dataset.day = dayCounter;
                        td.dataset.month = monthIndex;
                        td.dataset.year = year;

                        const dateKey = `${year}/${monthIndex}/${dayCounter}`;
                        if (momentsByDate[dateKey]) {
                            td.classList.add('has-moments');
                            td.title = `${momentsByDate[dateKey].length} momento(s)`;
                        }

                        td.addEventListener('click', handleDayClick);
                        dayCounter++;
                    } else {
                        td.classList.add('calendar-empty');
                    }
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            }
        }

        // Função para calcular time_value
        function calculateTimeValue(year, monthIndex, day) {
            let totalSeconds = 0;
            const daysPerMonth = daysPerMonthCycle.quantidade;
            const monthsPerYear = monthsPerYearCycle.quantidade;

            if (year > 0) {
                for (let y = 0; y < year; y++) {
                    let daysInYear = monthsPerYear * daysPerMonth;
                    daysInYear += applyLeapRules(y, 0);
                    totalSeconds += daysInYear * dayUnit.duracao;
                }
            } else if (year < 0) {
                for (let y = year; y < 0; y++) {
                    let daysInYear = monthsPerYear * daysPerMonth;
                    daysInYear += applyLeapRules(y, 0);
                    totalSeconds -= daysInYear * dayUnit.duracao;
                }
            }

            totalSeconds += monthIndex * daysPerMonth * dayUnit.duracao;
            totalSeconds += (day - 1) * dayUnit.duracao;

            return totalSeconds;
        }

        // Lidar com clique em um dia
        function handleDayClick(event) {
            const day = parseInt(event.target.dataset.day);
            const monthIndex = parseInt(event.target.dataset.month);
            const year = parseInt(event.target.dataset.year);
            const timeValue = calculateTimeValue(year, monthIndex, day);
            const dateKey = `${year}/${monthIndex}/${day}`;

            document.querySelectorAll('.calendar-day').forEach(td => {
                td.classList.remove('selected-day');
            });
            event.target.classList.add('selected-day');

            let message = `Data selecionada: ${year}/${months[monthIndex].nome || `Mês ${monthIndex + 1}`}/${day}\nTime Value: ${timeValue} segundos`;
            if (momentsByDate[dateKey]) {
                message += `\n\nMomentos neste dia:`;
                momentsByDate[dateKey].forEach(m => {
                    message += `\n- ${m.nome}: ${m.descricao || 'Sem descrição'}`;
                });
            }

            const timeValueInput = document.getElementById(timeValueId);
            if (timeValueInput) {
                timeValueInput.value = timeValue;
            }
            const timeNameInput = document.getElementById(timeNameId);
            if (timeNameInput) {
                timeNameInput.value = `${year}/${months[monthIndex].nome}/${day}`;
            }

            setDateClicked(timeValue, message);
        }

        // Renderizar mês inicial
        renderMonth(startYear, startMonth);

        // Atualizar ao mudar ano ou mês
        yearInput.addEventListener('input', () => {
            const year = parseInt(yearInput.value) || 0;
            renderMonth(year, parseInt(monthSelect.value));
        });
        monthSelect.addEventListener('change', () => {
            const year = parseInt(yearInput.value) || 0;
            renderMonth(year, parseInt(monthSelect.value));
        });
    } catch (error) {
        console.error('Erro ao carregar calendário ou momentos:', error);
        throw error; // Propagar o erro para o chamador
    }
}

function incrementYear(sid) {
    const yearInput = document.getElementById(`c-year${sid}`);
    if (yearInput) {
        let currentYear = parseInt(yearInput.value) || 0;
        yearInput.value = currentYear + 1;
        // Dispara evento 'input' para notificar a mudança
        yearInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function decrementYear(sid) {
    const yearInput = document.getElementById(`c-year${sid}`);
    if (yearInput) {
        let currentYear = parseInt(yearInput.value) || 0;
        yearInput.value = currentYear - 1;
        // Dispara evento 'input' para notificar a mudança
        yearInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function incrementMonth(sid) {
    const monthSelect = document.getElementById(`c-month${sid}`);
    const yearInput = document.getElementById(`c-year${sid}`);
    if (monthSelect && yearInput) {
        const currentIndex = monthSelect.selectedIndex;
        const maxIndex = monthSelect.options.length - 1;
        
        if (currentIndex < maxIndex) {
            // Avança para o próximo mês
            monthSelect.selectedIndex = currentIndex + 1;
        } else {
            // Volta ao primeiro mês (índice 0) e incrementa o ano
            monthSelect.selectedIndex = 0;
            let currentYear = parseInt(yearInput.value) || 0;
            yearInput.value = currentYear + 1;
            yearInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        // Dispara evento 'change' para notificar a mudança no select
        monthSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function decrementMonth(sid) {
    const monthSelect = document.getElementById(`c-month${sid}`);
    const yearInput = document.getElementById(`c-year${sid}`);
    if (monthSelect && yearInput) {
        const currentIndex = monthSelect.selectedIndex;
        
        if (currentIndex > 0) {
            // Retrocede para o mês anterior
            monthSelect.selectedIndex = currentIndex - 1;
        } else {
            // Vai para o último mês (índice máximo) e decrementa o ano
            monthSelect.selectedIndex = monthSelect.options.length - 1;
            let currentYear = parseInt(yearInput.value) || 0;
            yearInput.value = currentYear - 1;
            yearInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        // Dispara evento 'change' para notificar a mudança no select
        monthSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function formatarTablerMomentsSelect(campo, parent = 'body', create = false){
  // @formatter:off
  document.addEventListener("DOMContentLoaded", function () {
      var el;
      window.TomSelect && (new TomSelect(el = document.getElementById(campo), {
          copyClassesToDropdown: false,
          dropdownParent: parent,
          create: create,
          controlInput: '<input>',
          render:{
              item: function(data,escape) {
                  return `<div>${escape(data.text)}</div>`;
              },
              option: function(data,escape){
                  return `<div>
                        <span class="date" style="font-size: 12px; color: #a0a0a0; display: block;">${escape(data.date || 'Sem data')}</span>
                        <span class="title">${escape(data.text)}</span>
                    </div>`; //'<div>' + escape(data.text) + '</div>';
              },
          },
      }));
  });
  // @formatter:on
};

function addMomentTablerSelect(mid,timevalue,name,select){
    //document.querySelector('#'+campo).tomselect.setValue(val);
    var control = document.querySelector('#'+select).tomselect;//new TomSelect('#'+select);
    control.addOption({value:mid,'date':timevalue, 'text':name});
    control.setValue(mid);
    //control.addItem('test');
}

function setCalendarToTimeValue(calId, timeValue, yearSelectId, monthSelectId, timeValueId, timeNameId) {
    try {
        // Recuperar dados do calendário do localStorage
        const calendarCacheKey = `k_calendar_${calId}`;
        const calendarData = JSON.parse(localStorage.getItem(calendarCacheKey));

        if (!calendarData) {
            console.error('Dados do calendário não encontrados no localStorage');
            return;
        }

        const { units, cycles, months, leap_rules } = calendarData;

        // Encontrar durações
        const dayUnit = units.find(u => u.equivalente === 'dia');
        const monthUnit = units.find(u => u.equivalente === 'mes');
        const yearUnit = units.find(u => u.equivalente === 'ano');
        const daysPerMonthCycle = cycles.find(c => c.id_unidade === monthUnit.id && c.id_unidade_ref === dayUnit.id);
        const monthsPerYearCycle = cycles.find(c => c.id_unidade === yearUnit.id && c.id_unidade_ref === monthUnit.id);
        const monthsPerYear = monthsPerYearCycle ? monthsPerYearCycle.quantidade : 12;
        const defaultDaysPerMonth = daysPerMonthCycle ? daysPerMonthCycle.quantidade : 30;

        // Função para aplicar regras de leap years
        function applyLeapRules(year, monthIndex) {
            let extraDays = 0;
            leap_rules.forEach(rule => {
                if (rule.id_unidade === yearUnit.id && eval(rule.condition.replace('year', year))) { // Substituir eval em produção
                    const targetUnit = units.find(u => u.id === rule.target_unidade);
                    if (targetUnit.equivalente === 'dia') {
                        extraDays += rule.add_units;
                    }
                }
            });
            return extraDays;
        }

        // Função para calcular ano, mês e dia a partir do time_value
        function calculateDateFromTimeValue(totalSeconds) {
            let remainingSeconds = totalSeconds;
            const secondsPerDay = dayUnit.duracao;
            let year = 0, monthIndex = 0, day = 1;

            // Determinar direção (positiva ou negativa)
            const isNegative = totalSeconds < 0;

            // Calcular anos
            while (Math.abs(remainingSeconds) >= secondsPerDay * defaultDaysPerMonth * monthsPerYear) {
                let daysInYear = 0;
                for (let m = 0; m < monthsPerYear; m++) {
                    const daysInMonth = parseFloat(months[m]?.days || defaultDaysPerMonth) + applyLeapRules(isNegative ? year - 1 : year, m);
                    daysInYear += daysInMonth;
                }
                const yearSeconds = daysInYear * secondsPerDay;
                if (isNegative) {
                    if (remainingSeconds < -yearSeconds) {
                        remainingSeconds += yearSeconds;
                        year--;
                    } else {
                        break; // Evitar loop infinito se não puder subtrair mais um ano
                    }
                } else {
                    if (remainingSeconds >= yearSeconds) {
                        remainingSeconds -= yearSeconds;
                        year++;
                    } else {
                        break; // Evitar loop infinito se não puder subtrair mais um ano
                    }
                }
            }

            // Calcular meses
            while (Math.abs(remainingSeconds) >= secondsPerDay) {
                const daysInMonth = parseFloat(months[monthIndex]?.days || defaultDaysPerMonth) + applyLeapRules(year, monthIndex);
                const monthSeconds = daysInMonth * secondsPerDay;
                if (isNegative) {
                    if (Math.abs(remainingSeconds) >= monthSeconds) {
                        remainingSeconds += monthSeconds;
                        monthIndex--;
                        if (monthIndex < 0) {
                            monthIndex = monthsPerYear - 1;
                            year--;
                        }
                    } else {
                        break;
                    }
                } else {
                    if (remainingSeconds >= monthSeconds) {
                        remainingSeconds -= monthSeconds;
                        monthIndex++;
                        if (monthIndex >= monthsPerYear) {
                            monthIndex = 0;
                            year++;
                        }
                    } else {
                        break;
                    }
                }
            }

            // Calcular dias
            day = Math.floor(Math.abs(remainingSeconds) / secondsPerDay) + 1;
            if (isNegative && remainingSeconds !== 0) {
                // Ajustar para o dia correto em valores negativos
                const daysInMonth = parseFloat(months[monthIndex]?.days || defaultDaysPerMonth) + applyLeapRules(year, monthIndex);
                day = daysInMonth - Math.floor(Math.abs(remainingSeconds) / secondsPerDay);
                if (day <= 0) {
                    day = 1;
                    monthIndex--;
                    if (monthIndex < 0) {
                        monthIndex = monthsPerYear - 1;
                        year--;
                    }
                }
            }

            // Garantir que monthIndex esteja dentro dos limites
            if (monthIndex < 0 || monthIndex >= monthsPerYear) {
                console.warn('monthIndex fora dos limites:', monthIndex);
                monthIndex = monthIndex < 0 ? 0 : monthsPerYear - 1;
            }

            return { year, monthIndex, day };
        }

        // Calcular a data
        const { year, monthIndex, day } = calculateDateFromTimeValue(timeValue);

        // Atualizar os inputs de ano e mês
        const yearInput = document.getElementById(yearSelectId);
        const monthSelect = document.getElementById(monthSelectId);
        if (yearInput && monthSelect) {
            yearInput.value = year;
            monthSelect.selectedIndex = monthIndex;

            // Disparar eventos para atualizar o calendário
            monthSelect.dispatchEvent(new Event('change', { bubbles: true }));
            yearInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Atualizar os campos timeValueId e timeNameId
        const timeValueInput = document.getElementById(timeValueId);
        if (timeValueInput) {
            timeValueInput.value = timeValue;
        }
        const timeNameInput = document.getElementById(timeNameId);
        if (timeNameInput) {
            timeNameInput.value = `${year}/${months[monthIndex]?.nome || `Mês ${monthIndex + 1}`}/${day}`;
        }

        // Destacar o dia específico
        document.querySelectorAll('.calendar-day').forEach(td => {
            td.classList.remove('selected-day');
        });
        const dayElement = document.querySelector(`.calendar-day[data-day="${day}"][data-month="${monthIndex}"][data-year="${year}"]`);
        if (dayElement) {
            dayElement.classList.add('selected-day');
        }

    } catch (error) {
        console.error('Erro ao definir calendário para o time_value:', error);
    }
}

function copyToClipboard(button) {
    const text = button.textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        // Mostrar mensagem "Copiado!"
        const message = button.querySelector(".copied-message");
        message.classList.add("show");
        // Esconder a mensagem após 1 segundo
        setTimeout(() => {
            message.classList.remove("show");
        }, 1000);
    }).catch(err => {
        console.error("Erro ao copiar: ", err);
    });
}

function exibirNativa(eid, palavra, fonte = 0, tamanho = '') {
	const $editableDiv = $('#drawchar_editable_' + eid);
	const $hiddenInput = $('#escrita_nativa_' + eid);
	
	// Update hidden input with comma-separated IDs
	$hiddenInput.val(palavra);
	
	// Clear current content
	$editableDiv.html('');
	
	if (fonte == 3 && palavra) {
		// Display drawchar spans for each ID
		palavra.split(',').forEach(function(id) {
			if (id) {
				const span = $('<span>')
					.addClass('drawchar drawchar-' + tamanho + ' rounded')
					.css('background-image', 'url(./writing/' + eid + '/' + id + '.png?2025)')
					.attr('data-id', id);
				$editableDiv.append(span);
			}
		});
	} else {
		// For non-drawchar (font-based), display text directly
		$editableDiv.text(palavra);
	}
	
	// placeCaretAtEnd($editableDiv[0]);
}

function placeCaretAtEnd(el) {
	const range = document.createRange();
	const sel = window.getSelection();
	range.selectNodeContents(el);
	range.collapse(false);
	sel.removeAllRanges();
	sel.addRange(range);
	el.focus();
}

function showSubstitutionOptions(eid, results, $div, fonte, tamanho, $hiddenInput) {
    hideSubstitutionOptions();
    
    const $optionsDiv = $('<div>')
        .attr('id', 'substitution-options')
        .addClass('substitution-options')
        .css({
            position: 'absolute',
            top: $div.offset().top + $div.outerHeight(),
            left: $div.offset().left,
            background: '#fff',
            border: '1px solid #ced4da',
            borderRadius: '4px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
            padding: '5px',
            maxWidth: '300px'
        })
        .data('results', results);
    
    results.forEach(function(result, index) {
        const $button = $('<button>')
            .addClass('substitution-option btn btn-sm btn-light')
            .css({ display: 'inline-flex', alignItems: 'center', margin: '2px' });
        
        if (index < 9) {
            const $number = $('<span>')
                .addClass('option-number')
                .text(index + 1)
                .css({
                    display: 'inline-block',
                    width: '20px',
                    textAlign: 'center',
                    marginRight: '5px',
                    fontWeight: 'bold',
                    color: '#495057'
                });
            $button.append($number);
        }
        
        if (fonte == 3) {
            const $span = $('<span>')
                .addClass('drawchar drawchar-' + tamanho + ' rounded papapapa')
                .css('background-image', 'url(./writing/' + eid + '/' + result.id + '.png?2025)')
                .attr('data-id', result.id);
            $button.append($span);
            $button.append('<span style="margin-left: 5px;">' + (result.desc || result.id) + '</span>');
        } else {
            $button.text(result.id);
        }
        
        $button.on('click', function() {
            selectSubstitutionOption(eid, result.id, $div, fonte, tamanho, $hiddenInput);
        });
        
        $optionsDiv.append($button);
    });
    
    $('body').append($optionsDiv);
}

function selectSubstitutionOption(eid, id, $div, fonte, tamanho, $hiddenInput) {
    let currentIds = $hiddenInput.val() ? $hiddenInput.val().split(',') : [];
    currentIds.push(id);
    exibirNativa(eid, currentIds.filter(id => id).join(','), fonte, tamanho);
    editarPalavra();
    hideSubstitutionOptions();
}

function hideSubstitutionOptions() {
    $('#substitution-options').remove();
}

function addNatDraw(draw, fonte = -1, tamanho) {
    const eid = $('#lateralEid').val();
    const hiddenInput = $('#escrita_nativa_' + eid);
    let currentIds = hiddenInput.val() ? hiddenInput.val().split(',') : [];
    if (draw) {
        currentIds.push(draw);
    }
    exibirNativa(eid, currentIds.filter(id => id).join(','), fonte, tamanho);
}
function okIpaPronuncia(){
    $("#pronuncia").val($("#tempPron").val());
    $("#pronuncia").trigger("change");
}

function okInsertNativo() {
    const eid = $('#lateralEid').val();
    const fonte = $('#drawchar_editable_' + eid).data('fonte');
    const tamanho = $('#drawchar_editable_' + eid).data('tamanho');
    exibirNativa(eid, $('#tempNat').val(), fonte, tamanho);
}

$(document).on('input', '.editable-drawchar', function(e) {
    const $div = $(this);
    const eid = $div.data('eid');
    const fonte = $div.data('fonte');
    const tamanho = $div.data('tamanho');
    const $hiddenInput = $('#escrita_nativa_' + eid);
    
    // Get current text content (excluding spans)
    let text = '';
    $div.contents().each(function() {
        if (this.nodeType === 3) { // Text node
            text += this.nodeValue;
        }
    });
    
    // If text is present, call substitution API
    if (text && fonte == 3) {
        $.post('api.php?action=getAutoSubstituicao&eid=' + eid, { p: text }, function(data2) {
            if (data2 == '-1') {
                exibirNativa(eid, $hiddenInput.val(), fonte, tamanho);
                hideSubstitutionOptions();
            } else {
                let results;
                try {
                    results = JSON.parse(data2);
                } catch (e) {
                    results = [{ id: data2, desc: '' }]; // Fallback for non-drawchar or single ID
                }
                
                if (Array.isArray(results) && results.length === 1 && results[0].desc === text) {
                    let id = results[0].id;
                    let currentIds = $hiddenInput.val() ? $hiddenInput.val().split(',') : [];
                    currentIds.push(id);
                    exibirNativa(eid, currentIds.filter(id => id).join(','), fonte, tamanho);
                    editarPalavra();
                    hideSubstitutionOptions();
                } else if (Array.isArray(results) && results.length > 0) {
                    showSubstitutionOptions(eid, results, $div, fonte, tamanho, $hiddenInput, text);
                } else {
                    hideSubstitutionOptions();
                }
            }
        });
    } else {
        hideSubstitutionOptions();
    }
});

$(document).on('keydown', '.editable-drawchar', function(e) {
    const $div = $(this);
    const eid = $div.data('eid');
    const fonte = $div.data('fonte');
    const tamanho = $div.data('tamanho');
    const $hiddenInput = $('#escrita_nativa_' + eid);
    
    if (e.key === 'Backspace') {
        let currentIds = $hiddenInput.val() ? $hiddenInput.val().split(',') : [];
        if (currentIds.length > 0) {
            e.preventDefault();
            currentIds.pop();
            exibirNativa(eid, currentIds.filter(id => id).join(','), fonte, tamanho);
            editarPalavra();
            hideSubstitutionOptions();
        }
    } else if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const $options = $('#substitution-options');
        if ($options.is(':visible') && $options.data('results')) {
            // Select first option
            selectSubstitutionOption(eid, $options.data('results')[0].id, $div, fonte, tamanho, $hiddenInput);
        }
    } else if (e.key === 'Escape') {
        e.preventDefault();
        hideSubstitutionOptions();
    } else if (/^[1-9]$/.test(e.key)) {
        e.preventDefault();
        const $options = $('#substitution-options');
        if ($options.is(':visible') && $options.data('results')) {
            const index = parseInt(e.key) - 1;
            if (index < $options.data('results').length) {
                selectSubstitutionOption(eid, $options.data('results')[index].id, $div, fonte, tamanho, $hiddenInput);
            }
        }
    }
});

$(document).on('click', function(e) {
    const $options = $('#substitution-options');
    if ($options.length && !$(e.target).closest('#substitution-options').length && 
        !$(e.target).closest($options.data('input-div')).length) {
        hideSubstitutionOptions();
    }
});

function limparCacheLocal() {
    if (confirm("Tem certeza?")) {
        for (let i = localStorage.length - 1; i >= 0; i--) {
            const key = localStorage.key(i);
            if (key && key.startsWith('k_')) {
                localStorage.removeItem(key);
            }
        }
        if ('caches' in window) {
            caches.keys().then(cacheNames => {
                cacheNames.forEach(cacheName => {
                    caches.delete(cacheName);
                });
                console.log('Todos os caches do Service Worker foram limpos.');
            }).catch(error => {
                console.error('Erro ao limpar caches:', error);
            });
        }
        window.location.reload();
    }
}

function loadAutoSubstituicoes(eid, changed = 0, force = false) {
    $.get("api.php?action=getAllAutoSubstituicoes&eid=" + eid, function(data) {
        const response = JSON.parse(data);
        const storageKey = "k_autosubs_" + eid;
        const updatedKey = "k_autosubs_updated_" + eid;
        
        if (force || !localStorage.getItem(updatedKey)) {
            console.log('Autosubstitutions outdated or not found > update');
            localStorage.setItem(storageKey, JSON.stringify(response));
            localStorage.setItem(updatedKey, changed);
        }
    });
}

function getAutoSubstituicao(eid, input) {
    const storageKey = `k_autosubs_${eid}`;
    const data = JSON.parse(localStorage.getItem(storageKey) || '{}');
    
    if (!data || !data.fonte || !data.autosubs) {
        console.log(`No autosubstitution data found for eid: ${eid}`);
        return '';
    }
    
    const fonte = data.fonte;
    const autosubs = data.autosubs;
    
    if (fonte == 3) {
        let matches = [];
        
        autosubs.forEach(r => {
            // Case-sensitive matching with IPA support
            if (r.tecla && input.startsWith(r.tecla)) {
                matches.push({
                    id: r.glifos,
                    desc: r.tecla,
                    tam: r.tam
                });
            }
        });
        
        if (matches.length === 0) {
            return '-1';
        }
        
        matches.sort((a, b) => {
            const a_exact = a.desc === input;
            const b_exact = b.desc === input;
            
            if (a_exact && !b_exact) return -1;
            if (!a_exact && b_exact) return 1;
            if (a.tam === b.tam) return a.id.localeCompare(b.id);
            return b.tam - a.tam;
        });
        
        return JSON.stringify(matches);
    } else {
        let palavra = '';
        // Use Array.from for proper Unicode/IPA character splitting
        const chars = Array.from(input);
        let i = 0;
        
        while (i < chars.length) {
            let found = '*';
            for (let r of autosubs) {
                // Construct substring with exact character count (tam)
                const substr = chars.slice(i, i + parseInt(r.tam)).join('');
                if (substr === r.tecla) {
                    found = r.glifos;
                    i += parseInt(r.tam);
                    break;
                }
            }
            palavra += found;
            if (found === '*') i++;
        }
        
        return palavra.indexOf('*') === -1 ? palavra : '';
    }
}

function loadPronuncias(iid, changed = 0, force = false) {
    const storageKey = "k_pronuncias_" + iid;
    const updatedKey = "k_pronuncias_updated_" + iid;
    
    $.get("api.php?action=getAllPronuncias&iid=" + iid, function(data) {
        const pronuncias = JSON.parse(data);
        
        if (force || !localStorage.getItem(updatedKey)) {
            console.log('Pronunciation data outdated or not found > update');
            localStorage.setItem(storageKey, JSON.stringify(pronuncias));
            localStorage.setItem(updatedKey, changed);
        }
    });
}

function loadExtraPanel(page){
    $("#offcanvasSettings").load("index.php?panel="+page);
}

function getChecarPronuncia(iid, input, checar = '1') {
    const storageKey = `k_pronuncias_${iid}`;
    const pronuncias = JSON.parse(localStorage.getItem(storageKey) || '[]');
    
    if (!pronuncias.length) {
        console.log(`No pronunciation data found for iid: ${iid}`);
        return '-1';
    }
    
    let palavra = '';
    // Use Array.from to properly split Unicode characters, including IPA
    const chars = Array.from(input);
    
    for (let i = 0; i < chars.length; i++) {
        let found = false;
        
        // Check double character
        if (i < chars.length - 1) {
            const doublechar = chars[i] + chars[i + 1];
            // Case-sensitive matching
            const match = pronuncias.find(p => 
                (p.tecla && p.tecla === doublechar) || 
                (p.ipa && p.ipa === doublechar) || 
                (p.ipa2 && p.ipa2 === doublechar)
            );
            
            if (match) {
                if (match.ipa) palavra += match.ipa;
                else if (match.ipa2) palavra += match.ipa2;
                else palavra += '+';
                i++; // Skip next character
                found = true;
                continue;
            }
        }
        
        // Check single character
        const char = chars[i];
        const match = pronuncias.find(p => 
            (p.tecla && p.tecla === char) || 
            (p.ipa && p.ipa === char) || 
            (p.ipa2 && p.ipa2 === char)
        );
        
        if (match) {
            if (match.ipa) palavra += match.ipa;
            else if (match.ipa2) palavra += match.ipa2;
            else palavra += '=';
            found = true;
        } else {
            // Check if char exists in IPA inventory
            const ipaMatch = pronuncias.find(p => p.ipa && p.ipa === char);
            if (!ipaMatch) {
                palavra += '%';
                return '-1';
            } else {
                palavra += char;
            }
        }
    }
    
    return checar === '0' ? input : palavra;
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('sw.js')
        .then(registration => {
          console.log('Service Worker registrado com sucesso:', registration.scope);
        })
        .catch(error => {
          console.error('Erro ao registrar o Service Worker:', error);
        });
    });
}

function checarDigitacao(iid, ipaInput) {
    const storageKey = `k_pronuncias_${iid}`;
    const pronuncias = JSON.parse(localStorage.getItem(storageKey) || '[]');
    
    if (!pronuncias.length) {
        console.log(`No pronunciation data found for iid: ${iid}`);
        return [ipaInput]; // Return input as-is if no data
    }
    
    // Split IPA input into Unicode characters
    const chars = Array.from(ipaInput);
    
    // Store possible tecla sequences with their current state
    let possibilities = [{ tecla: '', index: 0 }];
    let finalResults = [];
    
    while (possibilities.length > 0) {
        let newPossibilities = [];
        
        for (let p of possibilities) {
            const i = p.index;
            if (i >= chars.length) {
                finalResults.push(p.tecla);
                continue;
            }
            
            // Check double-character IPA first
            let found = false;
            if (i < chars.length - 1) {
                const doublechar = chars[i] + chars[i + 1];
                const matches = pronuncias.filter(p => 
                    (p.ipa && p.ipa === doublechar) || 
                    (p.ipa2 && p.ipa2 === doublechar)
                ).sort((a, b) => a.ordem - b.ordem);
                
                for (let match of matches) {
                    if (match.tecla) {
                        newPossibilities.push({
                            tecla: p.tecla + match.tecla,
                            index: i + 2
                        });
                        found = true;
                    }
                }
            }
            
            // Check single-character IPA
            const char = chars[i];
            const matches = pronuncias.filter(p => 
                (p.ipa && p.ipa === char) || 
                (p.ipa2 && p.ipa2 === char)
            ).sort((a, b) => a.ordem - b.ordem);
            
            for (let match of matches) {
                if (match.tecla) {
                    newPossibilities.push({
                        tecla: p.tecla + match.tecla,
                        index: i + 1
                    });
                    found = true;
                }
            }
            
            // If no tecla found, use the original IPA character
            if (!found) {
                newPossibilities.push({
                    tecla: p.tecla + char,
                    index: i + 1
                });
            }
        }
        
        possibilities = newPossibilities;
    }
    
    // Remove duplicates and return results
    const uniqueResults = [...new Set(finalResults)];
    return uniqueResults.length > 0 ? uniqueResults : [ipaInput];
}