#!/usr/bin/env node

/**
 * Enforces .ai/rules/http-client.md: "Never hardcode page paths".
 * Fails if any resources/js file other than routes.js assigns a literal
 * path string to window.location.href instead of using routes.pages.*.
 */

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { extname, join, relative } from 'node:path';

const jsDir = new URL('../resources/js', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');
const hardcodedHrefPattern = /window\.location\.href\s*=\s*['"`][^'"`]*['"`]/;
const violations = [];

function walk(dir) {
    for (const entry of readdirSync(dir)) {
        const fullPath = join(dir, entry);
        const stats = statSync(fullPath);

        if (stats.isDirectory()) {
            walk(fullPath);
            continue;
        }

        if (extname(fullPath) !== '.js') {
            continue;
        }

        const relativePath = relative(jsDir, fullPath);

        if (relativePath === 'routes.js') {
            continue;
        }

        const lines = readFileSync(fullPath, 'utf8').split('\n');

        lines.forEach((line, index) => {
            if (hardcodedHrefPattern.test(line)) {
                violations.push(`  resources/js/${relativePath}:${index + 1}: ${line.trim()}`);
            }
        });
    }
}

walk(jsDir);

if (violations.length > 0) {
    console.error('✖ Hardcoded page path(s) found outside resources/js/routes.js:\n');
    console.error(violations.join('\n'));
    console.error('\nAdd the path to the `pages` object in resources/js/routes.js and use `routes.pages.<name>` instead (see .ai/rules/http-client.md).');
    process.exit(1);
}

console.log('✓ No hardcoded page paths found outside resources/js/routes.js.');
