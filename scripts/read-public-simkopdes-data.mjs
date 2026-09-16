import puppeteer from 'puppeteer';
import fs from 'node:fs/promises';
const browser = await puppeteer.launch({headless:true,pipe:true,executablePath:process.env.CHROME_PATH||undefined,userDataDir:'storage/framework/public-data-browser',timeout:25000});
try {
    const page=await browser.newPage();
    await page.goto('https://simkopdes.go.id/pers/dashboard',{waitUntil:'networkidle2',timeout:60000});
    await page.evaluate(()=> { window.webpackChunk_N_E.push([[Date.now()],{},require=>{window.publicDataClient=require(66293);}]); });
    const endpoints = [
        ['/statistics/national/phase-2','national'],
        ['/statistics/province/phase-2/12','jawa-barat'],
        ['/statistics/national-readiness/province/12?period=2026','jawa-barat-readiness'],
        ['/cooperatives/explore?page=1&page_size=3','explore'],
        ['/cooperatives/explore?page=1&page_size=3&province_id=12','explore-province-id'],
        ['/cooperatives/explore?page=1&page_size=3&province_code=32','explore-province-code'],
    ];
    for(const [endpoint,name] of endpoints){
        const result=await page.evaluate(async endpoint=>{
            try{return {success:true,result:await window.publicDataClient.fetchDataWithFilterNoAuth(endpoint,{})};}
            catch(e){return {success:false,error:e.message};}
        },endpoint);
        if(name.startsWith('explore') && result.success){
            const d=result.result.data;
            const rows=Array.isArray(d)?d:Array.isArray(d?.data)?d.data:[];
            result.schema=rows[0]?Object.keys(rows[0]):[];
            result.result.data=rows.map(row=>Object.fromEntries(Object.entries(row).filter(([key])=>/^(id|cooperative_id|name|cooperative_name|slug|province_id|province_code|province_name|province|district_id|district_code|district_name|district|subdistrict_id|subdistrict_code|subdistrict_name|village_id|village_code|village_name|total_members|total_member|male_members|female_members)$/.test(key))));
        }
        await fs.writeFile('storage/app/api-discovery/public-'+name+'.json',JSON.stringify(result,null,2));
        console.log(name,JSON.stringify(result).slice(0,6500));
    }
} finally {await browser.close();}
