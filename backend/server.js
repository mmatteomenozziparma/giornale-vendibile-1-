// WolfPress demonstration API. In-memory data only; NOT safe for deployment.
// Important: no authentication/authorization, durable storage or tenant security.
// Bind to loopback only; do not expose publicly.
import http from "node:http";
import {
  randomUUID
}
from "node:crypto";
import {
  fileURLToPath
}
from "node:url";
import path from "node:path";
import fs from "node:fs/promises";
const port=Number(process.env.PORT||3000);
const host="127.0.0.1";
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),"../frontend");
const tickets=[];
const articles=[
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
const types= {
  "/":"text/html; charset=utf-8",".html":"text/html; charset=utf-8",".css":"text/css; charset=utf-8",".js":"text/javascript; charset=utf-8"
};
function json(res,status,obj) {
  res.writeHead(status, {
    "Content-Type":"application/json; charset=utf-8","Cache-Control":"no-store","X-Content-Type-Options":"nosniff"
  });
  res.end(JSON.stringify(obj))
}
function cors(res) {
  res.setHeader("Access-Control-Allow-Origin","http://localhost:3000");
}
async function readBody(req) {
  let body="";
  for await(const chunk of req) {
    body+=chunk;
    if(body.length>8192)throw new Error("Payload troppo grande")
  }
  return JSON.parse(body)
}
export function handler(req,res) {
  return (async()=> {
    res.setHeader("X-Content-Type-Options","nosniff");
    res.setHeader("Referrer-Policy","no-referrer");
    res.setHeader("X-Frame-Options","DENY");
    res.setHeader("Content-Security-Policy","default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; connect-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'");
    cors(res);
    const u=new URL(req.url,"http://localhost:3000");
    if(u.pathname==="/api/health"&&req.method==="GET")return json(res,200, {
      status:"ok",mode:"demo"
    });
    if(u.pathname==="/api/articles"&&req.method==="GET")return json(res,200,articles);
    if(u.pathname==="/api/tickets"&&req.method==="GET") {
      const tenant=u.searchParams.get("tenant");
      if(!tenant||tenant!=="wolfpress-demo")return json(res,400, {
        error:"Tenant dimostrativo non valido"
      });
      return json(res,200,tickets.filter(t=>t.tenant===tenant))
    }
    if(u.pathname==="/api/tickets"&&req.method==="POST") {
      let b;
      try {
        b=await readBody(req)
      }
      catch {
        return json(res,400, {
          error:"JSON non valido o troppo grande"
        })
      }
      if(b?.tenant!=="wolfpress-demo"||typeof b?.subject!=="string"||b.subject.trim().length<3||b.subject.length>120)return json(res,400, {
        error:"Oggetto o tenant non valido"
      });
      const t= {
        id:randomUUID(),tenant:"wolfpress-demo",subject:b.subject.trim(),status:"aperto",createdAt:new Date().toISOString()
      };
      tickets.unshift(t);
      return json(res,201,t)
    }
    if(u.pathname.startsWith("/api/"))return json(res,404, {
      error:"API non trovata"
    });
    if(req.method!=="GET"&&req.method!=="HEAD")return json(res,405, {
      error:"Metodo non consentito"
    });
    const requested=u.pathname==="/"?"/index.html":u.pathname;
    const full=path.resolve(root,"."+requested);
    if(full!==root&&!full.startsWith(root+path.sep))return json(res,403, {
      error:"Percorso vietato"
    });
    let data;
    try {
      data=await fs.readFile(full)
    }
    catch {
      return json(res,404, {
        error:"File non trovato"
      })
    }
    res.writeHead(200, {
      "Content-Type":types[path.extname(full)]||"application/octet-stream","Cache-Control":"no-store"
    });
    res.end(req.method==="HEAD"?undefined:data);
  })().catch(e=>json(res,500, {
    error:"Errore interno del server demo"
  }))
}
if(process.argv[1]&&path.resolve(process.argv[1])===fileURLToPath(import.meta.url)) {
  http.createServer(handler).listen(port,host,()=>console.log("WolfPress demo: http://"+host+":"+port))
}
