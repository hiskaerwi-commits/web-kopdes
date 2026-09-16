import { execFileSync, spawn } from 'node:child_process';
import { readdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const candidates = ['php'];
if (process.platform === 'win32' && process.env.LOCALAPPDATA) {
    const packages = path.join(process.env.LOCALAPPDATA, 'Microsoft', 'WinGet', 'Packages');
    try {
        for (const name of readdirSync(packages).filter(name => name.startsWith('PHP.PHP.'))) {
            candidates.push(path.join(packages, name, 'php.exe'));
        }
    } catch { /* Fall back to PHP available in PATH. */ }
}
const php = candidates.find(candidate => {
    try { return Number(execFileSync(candidate, ['-r', 'echo PHP_VERSION_ID;'], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] })) >= 80300; }
    catch { return false; }
});
if (!php) throw new Error('PHP 8.3+ diperlukan. Install PHP atau tambahkan ke PATH.');
const tasks = { serve: ['serve', '--host=127.0.0.1', '--port=8001'], admin: ['make:filament-user'], test: ['test'] };
const action = process.argv[2] || 'serve';
if (!tasks[action]) throw new Error('Pilihan perintah: serve, admin, test.');
const child = spawn(php, ['artisan', ...tasks[action]], { cwd: root, stdio: 'inherit' });
child.on('error', error => { console.error(error.message); process.exitCode = 1; });
child.on('exit', code => { process.exitCode = code ?? 1; });
