// Builds the sources with the given Vite major versions
// and copies each manifest to ../fixtures/manifest.vite<version>.json
//
// Usage: node fixtures.mjs 2 3 4 5 6 7 8
import {execSync} from 'node:child_process';
import {copyFileSync, existsSync} from 'node:fs';
import {dirname} from 'node:path';
import {fileURLToPath} from 'node:url';

process.chdir(dirname(fileURLToPath(import.meta.url)));

const versions = process.argv.slice(2);
if (versions.length === 0) {
  console.error('Usage: node fixtures.mjs <vite-major-version> [...]');
  process.exit(1);
}

for (const v of versions) {
  const outDir = `dist/vite${v}`;
  const version = execSync(`npx --yes vite@${v} --version`).toString().trim();
  execSync(`npx --yes vite@${v} build --config vite.config.mjs --outDir ${outDir}`, {stdio: 'inherit'});
  // Vite 5 and later write `.vite/manifest.json`, Vite 2 - 4 write `manifest.json`
  const manifest = [`${outDir}/.vite/manifest.json`, `${outDir}/manifest.json`].find((f) => existsSync(f));
  const fixture = `../fixtures/manifest.vite${v}.json`;
  copyFileSync(manifest, fixture);
  console.log(`${version}: ${manifest} -> ${fixture}`);
}
