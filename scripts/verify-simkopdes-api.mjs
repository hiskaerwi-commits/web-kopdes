import fs from 'node:fs/promises';
const base = 'https://api.simkopdes.go.id/api';
const directory = 'storage/app/api-discovery';
const results = [];
async function read(endpoint, name) {
    const response = await fetch(base + endpoint, {headers: {Accept:'application/json'}, signal:AbortSignal.timeout(20000)});
    const text = await response.text();
    let body; try { body = JSON.parse(text); } catch { body = null; }
    const data = body?.data;
    const result = {endpoint, status:response.status, message:body?.message, type:typeof data, keys:body ? Object.keys(body):[], data_keys: data && typeof data==='object' ? Object.keys(data).slice(0,20):[], count:Array.isArray(data)?data.length:null, sample:Array.isArray(data)?data.slice(0,2): typeof data==='string'?data.slice(0,60):data};
    results.push(result);
    await fs.writeFile(directory+'/'+name+'.json',JSON.stringify(body,null,2));
    return data;
}
const districts = await read('/districts/by-province-code/32','districts-jawa-barat');
if (Array.isArray(districts) && districts[0]?.code) {
    const subs = await read('/sub-districts/by-district-code/'+districts[0].code,'subdistricts-sample');
    if (Array.isArray(subs) && subs[0]?.code) await read('/villages/by-sub-district-code/'+subs[0].code,'villages-sample');
}
await read('/statistics/national/phase-2','national-phase2');
await read('/statistics/province/phase-2/12','province-phase2');
await read('/statistics/national-readiness/province/12?period=2026','province-readiness');
await read('/cooperatives/explore?page=1&page_size=3','explore');
await fs.writeFile(directory+'/verified.json',JSON.stringify(results,null,2));
console.log(JSON.stringify(results,null,2));
