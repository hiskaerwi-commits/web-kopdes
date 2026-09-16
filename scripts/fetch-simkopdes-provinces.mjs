import fs from 'node:fs/promises';
const url = 'https://api.simkopdes.go.id/api/provinces';
const response = await fetch(url, { signal: AbortSignal.timeout(20000), headers: {Accept:'application/json'} });
if (!response.ok) throw new Error(`Provinces HTTP ${response.status}`);
const body = await response.json();
await fs.mkdir('storage/app/api-discovery', {recursive: true});
await fs.writeFile('storage/app/api-discovery/provinces-response.json', JSON.stringify(body, null, 2));
console.log(JSON.stringify(body, null, 2));
