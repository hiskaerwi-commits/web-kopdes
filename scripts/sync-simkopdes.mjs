import puppeteer from 'puppeteer';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const base = 'https://api.simkopdes.go.id/api';
const timestamp = new Date().toISOString();
const regionCode = process.argv.find(arg => arg.startsWith('--region='))?.split('=')[1];
if (regionCode && !/^\d{2}(?:\.\d{2}){0,2}(?:\.\d{4})?$/.test(regionCode)) throw new Error('Invalid region code. Example: --region=32.04');
async function metadata(endpoint) {
    const response = await fetch(base + endpoint, {headers:{Accept:'application/json'},signal:AbortSignal.timeout(20000)});
    if (!response.ok) throw new Error(endpoint + ': HTTP ' + response.status);
    const body = await response.json();
    if (!Array.isArray(body.data)) throw new Error('Unexpected region response: ' + endpoint);
    return body.data;
}
const regions = await metadata('/provinces');
const provinces = regions.filter(row => row.country_id === 68).map(({province_id,code,name,country_id}) => ({province_id,code,name,country_id}));
if (!provinces.length || provinces.some(row => !Number.isInteger(row.province_id) || !/^\d{2}$/.test(row.code) || typeof row.name !== 'string')) throw new Error('Invalid Indonesian province catalog.');
const catalog = new Map(provinces.map(row => [row.province_id,row]));
const requested = [];
const metadataRows = [];
let provinceDetailRequest = null;
if (regionCode) {
    const parts = regionCode.split('.');
    let row = provinces.find(row => row.code === parts[0]);
    if (!row) throw new Error('Unknown province: ' + parts[0]);
    if (parts.length === 1) {
        const districts = await metadata('/districts/by-province-code/' + row.code);
        for (const child of districts) metadataRows.push({code:child.code,name:child.name,parent_code:row.code,level:'regency'});
        provinceDetailRequest = {
            code: row.code, name: row.name,
            endpoint: '/statistics/national-readiness/province/'+row.province_id+'?period='+new Date().getFullYear(),
            districtCodesById: new Map(districts.map(child => [child.district_id, child.code])),
        };
    } else {
        const levels = [
            ['district','/districts/by-province-code/','district_id','regency'],
            ['subdistrict','/sub-districts/by-district-code/','subdistrict_id','district'],
            ['village','/villages/by-sub-district-code/','village_id','village'],
        ];
        for (let i=1; i<parts.length; i++) {
            const [apiLevel,endpoint,idField,localLevel] = levels[i-1];
            const children = await metadata(endpoint + row.code);
            for (const child of children) metadataRows.push({code:child.code,name:child.name,parent_code:row.code,level:localLevel});
            row = children.find(child => child.code === parts.slice(0,i+1).join('.'));
            if (!row) throw new Error('Region not found in source: ' + parts.slice(0,i+1).join('.'));
            if(i===parts.length-1) requested.push({code:row.code,name:row.name,endpoint:'/statistics/national-readiness/'+apiLevel+'/'+row[idField]+'?period='+new Date().getFullYear()});
        }
    }
}
function readinessTotals(row) {
    const required = ['count','accounts_count','npwp_count','nib_count','rat_count','transaction_volume','transaction_value'];
    for (const key of required) if (!Number.isFinite(row[key]) || row[key]<0) throw new Error('Invalid readiness metric '+key);
    const pickSavings = savings => ({
        total_amount: Number.isFinite(savings?.total_amount) ? savings.total_amount : 0,
        total_transaction: Number.isFinite(savings?.total_transaction) ? savings.total_transaction : 0,
        total_members: Number.isFinite(savings?.total_members) ? savings.total_members : 0,
    });
    return {
        cooperatives:row.count,accounts:row.accounts_count,npwp:row.npwp_count,nib:row.nib_count,rat:row.rat_count,
        transaction_volume:row.transaction_volume,transaction_value:row.transaction_value,
        simpanan_pokok:pickSavings(row.savings_summary?.simpanan_pokok),
        simpanan_wajib:pickSavings(row.savings_summary?.simpanan_wajib),
    };
}
let executablePath = process.env.CHROME_PATH;
if (!executablePath && process.platform === 'win32') {
    const chrome = path.join(process.env.PROGRAMFILES || 'C:/Program Files','Google/Chrome/Application/chrome.exe');
    try {await fs.access(chrome); executablePath = chrome;} catch { /* Use Puppeteer browser. */ }
}
const browser = await puppeteer.launch({headless:true,pipe:true,executablePath,userDataDir:path.join(root,'storage/framework/simkopdes-sync-browser'),timeout:30000});
let payload;
let provinceDetail = null;
const scoped = {};
try {
    const page = await browser.newPage();
    await page.goto('https://simkopdes.go.id/pers/dashboard',{waitUntil:'networkidle2',timeout:60000});
    await page.evaluate(() => {
        // Use the data client delivered to anonymous visitors; no credentials are copied.
        window.webpackChunk_N_E.push([[Date.now()],{},require => {
            const id = Object.keys(require.m).find(id => {
                const definition = String(require.m[id]);
                return definition.includes('fetchDataWithFilterNoAuth:function') && definition.includes('fetchDataDecrypt:function');
            });
            if(id) window.simkopdesPublicClient = require(id);
        }]);
        if(!window.simkopdesPublicClient?.fetchDataDecrypt) throw new Error('Public data client changed. Existing snapshot preserved.');
    });
    const read = endpoint => page.evaluate(endpoint => window.simkopdesPublicClient.fetchDataDecrypt(endpoint),endpoint);
    const result = await read('/statistics/national/phase-2');
    payload = result?.data?.national_totals ? result.data : result;
    if (provinceDetailRequest) {
        const data = await read(provinceDetailRequest.endpoint);
        const territory = data?.territorial_data;
        if (!territory?.totals || !Array.isArray(territory.districts)) throw new Error('Province detail schema changed for ' + provinceDetailRequest.code);
        provinceDetail = {
            code: provinceDetailRequest.code, name: provinceDetailRequest.name, fetched_at: timestamp, source: base+provinceDetailRequest.endpoint,
            totals: readinessTotals(territory.totals),
            districts: territory.districts.map(row => ({code: provinceDetailRequest.districtCodesById.get(row.district_id) || null, name: row.district, ...readinessTotals(row)})),
            economic_impact: (data.economic_impact && Array.isArray(data.economic_impact.rows)) ? {
                total_volume: data.economic_impact.total_volume, total_value: data.economic_impact.total_value,
                rows: data.economic_impact.rows.slice(0,10).map(r => ({product:String(r.product),volume:r.volume,value:r.value})),
            } : null,
            rat_summary: data.rat_summary ?? null,
            store_readiness: Array.isArray(data.store_readiness) ? data.store_readiness.map(r => ({label:String(r.label),value:r.value})) : [],
        };
    }
    for(const item of requested) {
        const data = await read(item.endpoint);
        const territory = data?.territorial_data;
        // The source returns one aggregate for the requested scope, never sum child rows.
        const totals = territory?.totals;
        if(!totals || !Number.isFinite(totals.accounts_count)) throw new Error('Regional statistics schema changed for ' + item.code);
        scoped[item.code] = {code:item.code,name:item.name,fetched_at:timestamp,source:base+item.endpoint,metrics:{
            cooperatives:totals.count,accounts:totals.accounts_count,npwp:totals.npwp_count,nib:totals.nib_count,
            transaction_value:totals.transaction_value,rat:totals.rat_count,
        }};
        if(Object.values(scoped[item.code].metrics).some(value => !Number.isFinite(value) || value<0)) throw new Error('Invalid regional totals.');
    }
} finally {await browser.close();}
if(!payload?.national_totals || !Array.isArray(payload.province_distribution)) throw new Error('Statistics schema changed. Existing snapshot preserved.');
function metrics(row,national=false) {
    const fields = {accounts:national?'microsite_accounts':'accounts_count',active_outlets:'active_outlets',cooperatives_with_active_outlets:'cooperatives_with_active_outlets',members:'total_members',members_male:'total_members_male',members_female:'total_members_female',managements:'total_managements',partnerships:'partnerships_count'};
    return Object.fromEntries(Object.entries(fields).map(([name,key]) => {
        if(!Number.isFinite(row[key]) || row[key]<0) throw new Error('Invalid metric '+key);
        return [name,row[key]];
    }));
}
const summaries = payload.province_distribution.filter(row => catalog.has(row.province_id)).map(row => ({
    province_id:row.province_id,code:catalog.get(row.province_id).code,name:catalog.get(row.province_id).name,metrics:metrics(row),
}));
if(new Set(summaries.map(row=>row.province_id)).size !== provinces.length) throw new Error('Incomplete provincial statistics. Existing snapshot preserved.');
const snapshotPath = path.join(root,'storage/app/private/simkopdes/statistics.json');
let previous = {};
try {previous = JSON.parse(await fs.readFile(snapshotPath,'utf8'));} catch { /* First sync. */ }
const snapshot = {schema_version:1,source:base+'/statistics/national/phase-2',fetched_at:timestamp,national:metrics(payload.national_totals,true),provinces:summaries,regions:{...(previous.regions||{}),...scoped},province_details:{...(previous.province_details||{}),...(provinceDetail ? {[provinceDetail.code]:provinceDetail} : {})}};
await fs.mkdir(path.dirname(snapshotPath),{recursive:true});
await fs.writeFile(snapshotPath+'.tmp',JSON.stringify(snapshot,null,2));
await fs.rename(snapshotPath+'.tmp',snapshotPath);
await fs.mkdir(path.join(root,'resources/data'),{recursive:true});
const catalogPath = path.join(root,'resources/data/provinces.json');
await fs.writeFile(catalogPath+'.tmp',JSON.stringify({source:base+'/provinces',fetched_at:timestamp,data:provinces},null,2));
await fs.rename(catalogPath+'.tmp',catalogPath);
await fs.writeFile(path.join(root,'storage/app/private/simkopdes/regions.json'),JSON.stringify(metadataRows,null,2));
console.log('Synced '+provinces.length+' Indonesian provinces and regional statistics'+(regionCode?' + '+regionCode:'')+(provinceDetail?' (province detail: '+provinceDetail.districts.length+' kabupaten/kota)':'')+'. '+timestamp);
