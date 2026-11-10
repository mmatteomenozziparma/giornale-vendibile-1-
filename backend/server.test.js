import test from "node:test";
import assert from "node:assert/strict";
import http from "node:http";
import {handler} from "./server.js";
test("health, articles, ticket validation and ticket creation",async()=>{
const s=http.createServer(handler);await new Promise(resolve=>s.listen(0,"127.0.0.1",resolve));
const base="http://127.0.0.1:"+s.address().port;
try{
const health=await fetch(base+"/api/health");assert.equal(health.status,200);assert.equal((await health.json()).status,"ok");
const articles=await (await fetch(base+"/api/articles")).json();assert.equal(articles.length,6);
const invalid=await fetch(base+"/api/tickets",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({subject:"a",tenant:"wolfpress-demo"})});assert.equal(invalid.status,400);
const valid=await fetch(base+"/api/tickets",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({subject:"Problema demo",tenant:"wolfpress-demo"})});assert.equal(valid.status,201);
const tickets=await (await fetch(base+"/api/tickets?tenant=wolfpress-demo")).json();assert.equal(tickets.length,1);
const html=await fetch(base+"/");assert.equal(html.status,200);assert.match(await html.text(),/WolfPress/);
}finally{await new Promise(resolve=>s.close(resolve))}
});