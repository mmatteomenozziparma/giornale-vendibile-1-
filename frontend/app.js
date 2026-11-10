// WolfPress demo. No external trackers or dependencies. All dynamic text is HTML-escaped.
const API = (location.protocol === "http:" && location.port === "3000") ? "/api" : "http://localhost:3000/api";
const demoArticles=[
{
  id:1,slug:"territori-e-comunita",title:"Territori e comunità: nuove prospettive",category:"Territori",author:"Redazione demo",date:"2026-11-05",summary:"Un articolo dimostrativo dedicato ai servizi locali.",body:"Questa pagina illustra una struttura editoriale. Il contenuto è fittizio e non rappresenta una notizia verificata."
},
{
  id:2,slug:"lavoro-e-innovazione",title:"Lavoro e innovazione digitale",category:"Lavoro",author:"Redazione demo",date:"2026-11-04",summary:"Tecnologie e lavoro in una redazione moderna.",body:"Il flusso editoriale separa proposta, revisione, approvazione e pubblicazione. I dati qui mostrati sono esempi."
},
{
  id:3,slug:"cultura-e-citta",title:"Cultura e città: progetti che coinvolgono",category:"Cultura",author:"Redazione demo",date:"2026-11-03",summary:"Idee e iniziative culturali.",body:"Il modello WolfPress può ospitare molte testate indipendenti. L'interfaccia dimostrativa non pubblica contenuti reali."
},
{
  id:4,slug:"sport-e-partecipazione",title:"Sport e partecipazione",category:"Sport",author:"Redazione demo",date:"2026-11-02",summary:"Lo sport come racconto del territorio.",body:"Articolo dimostrativo."
},
{
  id:5,slug:"tecnologia-accessibile",title:"Tecnologia accessibile per tutti",category:"Tecnologia",author:"Redazione demo",date:"2026-11-01",summary:"Progettare servizi digitali inclusivi.",body:"Articolo dimostrativo."
},
{
  id:6,slug:"istituzioni-e-cittadini",title:"Istituzioni e cittadini",category:"Politica",author:"Redazione demo",date:"2026-10-30",summary:"Una sezione politica con schede dedicate.",body:"Articolo dimostrativo."
}
];
const $=s=>document.querySelector(s);
const escapeHTML=s=>String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;
","<":"&lt;
",">":"&gt;
",'"':"&quot;","'":"&#39;
"}[c]));
const app=$("#app");
const state={articles:demoArticles,tickets:[],role:"editore",tenant:"wolfpress-demo",message:""};
async function request(path,options={}){const response=await fetch(API+path,{...options,headers:{"Content-Type":"application/json",...(options.headers||{})}});const data=await response.json();if(!response.ok)throw new Error(data.error||"Errore di servizio");return data}
async function bootstrap(){try{state.articles=await request("/articles")}catch{}try{state.tickets=await request("/tickets?tenant=wolfpress-demo")}catch{}render()}
const link=a=>"#/articolo/"+encodeURIComponent(a.slug);
const card=a=>`<article class="card"><span class="eyebrow">${escapeHTML(a.category)}</span><h3>${escapeHTML(a.title)}</h3><p>${escapeHTML(a.summary)}</p><small>${escapeHTML(a.author)} · ${escapeHTML(a.date)}</small><a href="$ {
  link(a)
}
">Leggi articolo</a></article>`;
function home(){const [first,...rest]=state.articles;return `<section class="hero"><span class="eyebrow">Prototipo editoriale · WolfPress</span><h1>${escapeHTML(first?.title||"Benvenuti su WolfPress")}</h1><p class="lead">${escapeHTML(first?.summary||"Una piattaforma per il giornalismo digitale indipendente.")}</p><a class="btn" href="$ {
  first?link(first):"#/sezioni"
}
">Leggi la notizia</a></section><h2>Ultime notizie</h2><div class="grid">${rest.map(card).join("")}</div><section class="panel" style="margin-top:24px"><h2>Cerca nel giornale</h2><form id="searchForm"><label for="q">Parole chiave</label><input id="q" name="q" type="search" required><button type="submit" style="margin-top:12px">Cerca</button></form></section>`}
function listing(cat,term){const a=state.articles.filter(x=>(!cat||x.category.toLowerCase()===cat.toLowerCase())&&(!term||[x.title,x.summary,x.category,x.body].join(" ").toLocaleLowerCase("it").includes(term.toLocaleLowerCase("it"))));return `<h1>${escapeHTML(term?"Risultati per: "+term:cat||"Tutte le sezioni")}</h1><p>${a.length} articoli dimostrativi trovati.</p><div class="grid">${a.map(card).join("")||'<p>Nessun risultato. Prova una ricerca differente.</p>'}</div>`}
function article(slug){const a=state.articles.find(x=>x.slug===slug);if(!a)return '<h1>Articolo non trovato</h1><a href="#/">Torna alla homepage</a>';return `<article class="article panel"><span class="eyebrow">${escapeHTML(a.category)}</span><h1>${escapeHTML(a.title)}</h1><p class="lead">${escapeHTML(a.summary)}</p><p class="muted">${escapeHTML(a.author)} · <time datetime="$ {
  escapeHTML(a.date)
}
">${escapeHTML(a.date)}</time></p><hr><p>${escapeHTML(a.body)}</p><div class="actions"><button id="share" class="secondary">Condividi</button><button id="print">Stampa</button><a href="#/sezioni">Altri articoli</a></div></article>`}
function politics(){return `<h1>Politica</h1><p class="lead">Pagine per partiti, candidati ed eletti, organizzabili per livello territoriale. I profili sono solo esempi.</p><div class="grid"><section class="card"><h2>Partiti</h2><p>Sezioni regionali e nazionali e relativi contenuti.</p><a href="#/profilo/partito">Scheda dimostrativa</a></section><section class="card"><h2>Candidati</h2><p>Comune, Regione, Camera, Senato, Parlamento europeo.</p><a href="#/profilo/candidato">Scheda dimostrativa</a></section><section class="card"><h2>Eletti</h2><p>Profilo, mandato e articoli associati.</p><a href="#/profilo/eletto">Scheda dimostrativa</a></section></div>`}
function profile(type){return `<article class="panel"><span class="eyebrow">Scheda dimostrativa</span><h1>Profilo ${escapeHTML(type)}</h1><p>Questa pagina è predisposta per anagrafica, territorio, carica, biografia, eventi e articoli. Non rappresenta persone o organizzazioni reali.</p><a href="#/politica">Torna a Politica</a></article>`}
const roleDescriptions={editore:"Gestione testata, utenti giornalisti, programmazione eventi e revisione articoli.",giornalista:"Proposte editoriali, articoli e portfolio dei tre articoli più letti.",tecnico:"Monitoraggio giornali ospitati, clienti, ticket e supporto tecnico.",cliente:"Richieste di assistenza, storico ticket e contatti dedicati."};
function management(){return `<h1>Pannelli WolfPress</h1><p class="notice">Modalità dimostrativa: il selettore dei ruoli NON autentica utenti. Non inserire dati personali o aziendali reali.</p><label for="role">Visualizza il pannello</label><select id="role">${Object.keys(roleDescriptions).map(r=>`<option value="$ {
  r
}
" ${r===state.role?"selected":""}>${r[0].toUpperCase()+r.slice(1)}</option>`).join("")}</select><section class="panel" style="margin-top:20px"><h2>${state.role[0].toUpperCase()+state.role.slice(1)}</h2><p>${roleDescriptions[state.role]}</p><div class="stats"><div class="stat"><strong>${state.articles.length}</strong>Articoli demo</div><div class="stat"><strong>${state.tickets.length}</strong>Ticket demo</div><div class="stat"><strong>1</strong>Testata demo</div></div>${state.role==="tecnico"||state.role==="cliente"?ticketPanel():editorPanel()}</section>`}
function editorPanel(){return `<div class="grid two"><section><h3>Flusso editoriale</h3><ol><li>Bozza</li><li>Revisione</li><li>Approvazione</li><li>Pubblicazione</li><li>Rettifica e archivio</li></ol><p class="muted">Workflow informativo: il salvataggio e i permessi editoriali non sono ancora implementati.</p></section><section><h3>Strumenti</h3><p>Portfolio, eventi, chat interna e videochat: interfacce previste, integrazioni da realizzare.</p><a href="#/sezioni">Sfoglia articoli</a></section></div>`}
function ticketPanel(){return `<h3>Assistenza clienti</h3><div class="table-scroll"><table><thead><tr><th>ID</th><th>Oggetto</th><th>Stato</th></tr></thead><tbody>${state.tickets.map(t=>`<tr><td>${escapeHTML(t.id)}</td><td>${escapeHTML(t.subject)}</td><td>${escapeHTML(t.status)}</td></tr>`).join("")||'<tr><td colspan="3">Nessun ticket.</td></tr>'}</tbody></table></div><form id="ticketForm"><label for="subject">Nuovo ticket dimostrativo</label><input id="subject" name="subject" maxlength="120" required placeholder="Descrivi brevemente il problema"><button type="submit" style="margin-top:12px">Apri ticket</button></form>`}
function staticPage(route){const pages={"chi-siamo":["Chi siamo","WolfPress è un modello white-label: il fornitore gestisce la tecnologia, ogni editore gestisce autonomamente la propria testata."],"video":["Video","Area predisposta per contenuti multimediali. Nessun video giornalistico è stato caricato."],"redazione":["Redazione","Ogni testata avrà propri autori, revisori, approvazioni, storico delle modifiche e regole editoriali."],"privacy":["Privacy demo","Questa interfaccia non incorpora tracker. L'API dimostrativa usa dati in memoria. Prima dell'uso reale servono titolare, informativa e valutazione dei servizi di hosting."],"accessibilita":["Accessibilità","Navigazione da tastiera, struttura semantica e opzioni di movimento ridotto. È ancora necessaria una verifica completa WCAG 2.2 AA con tecnologie assistive."]};const [title,body]=pages[route]||["Pagina non trovata","Verifica il collegamento."];return `<section class="panel"><h1>${title}</h1><p>${body}</p><a href="#/">Torna alla homepage</a></section>`}
function render(){const path=decodeURIComponent(location.hash.slice(2)||"");const parts=path.split("/");let html;if(!path)html=home();else if(parts[0]==="articolo")html=article(parts.slice(1).join("/"));else if(parts[0]==="sezioni")html=listing();else if(parts[0]==="categoria")html=listing(parts[1]);else if(parts[0]==="ricerca")html=listing(null,new URLSearchParams(location.hash.split("?")[1]||"").get("q")||"");else if(parts[0]==="politica")html=politics();else if(parts[0]==="profilo")html=profile(parts[1]);else if(parts[0]==="gestione")html=management();else html=staticPage(parts[0]);app.innerHTML=html;document.title="WolfPress — "+(app.querySelector("h1")?.textContent||"Giornale digitale");window.scrollTo({top:0,behavior:"instant"});const nav=$("#nav");nav.classList.remove("open");$("#menuToggle").setAttribute("aria-expanded","false")}
document.addEventListener("submit",async e=>{if(e.target.id==="searchForm"){e.preventDefault();location.hash="#/ricerca?q="+encodeURIComponent(new FormData(e.target).get("q"));return}if(e.target.id==="ticketForm"){e.preventDefault();const subject=String(new FormData(e.target).get("subject")||"").trim();if(!subject)return;try{const t=await request("/tickets",{method:"POST",body:JSON.stringify({subject,tenant:state.tenant})});state.tickets.unshift(t)}catch{state.tickets.unshift({id:"demo-"+(state.tickets.length+1),subject,status:"demo locale"});alert("API non disponibile: ticket creato solo per questa sessione.")}render()}});
document.addEventListener("change",e=>{if(e.target.id==="role"){state.role=e.target.value;render()}});
document.addEventListener("click",async e=>{if(e.target.id==="share"){const url=location.href;try{if(navigator.share)await navigator.share({title:document.title,url});else if(navigator.clipboard){await navigator.clipboard.writeText(url);alert("Link copiato")}else prompt("Copia link",url)}catch{}}if(e.target.id==="print")window.print();if(e.target.id==="menuToggle"){const nav=$("#nav");const open=nav.classList.toggle("open");e.target.setAttribute("aria-expanded",String(open))}});
document.addEventListener("keydown",e=>{if(e.key==="Escape"){$("#nav").classList.remove("open");$("#menuToggle").setAttribute("aria-expanded","false")}});
window.addEventListener("hashchange",render);bootstrap();
