#!/usr/bin/env node

/**
 * Enforces .ai/rules/views.md: "Call REST API endpoints through resources/js/routes.js".
 * Fails if any resources/js file other than routes.js contains a hardcoded '/api/...' path.
 */

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { extname, join, relative } from 'node:path';

const jsDir = new URL('../resources/js', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');
const apiPathPattern = /['"`]\/api\/[^'"`]*['"`]/;
const violations = [];

function walk(dir) {
    for (const entry of readdirSync(dir)) {
        const fullPath = join(dir, entry);
        const stats = statSync(fullPath);

        if (stats.isDirectory()) {
            walk(fullPath);
            continue;
        }

        if (extname(fullPath) !== '.js' || fullPath.endsWith(join('resources', 'js', 'routes.js'))) {
            continue;
        }

        const relativePath = relative(jsDir, fullPath);

        if (relativePath === 'routes.js') {
            continue;
        }

        const lines = readFileSync(fullPath, 'utf8').split('\n');

        lines.forEach((line, index) => {
            if (apiPathPattern.test(line)) {
                violations.push(`  resources/js/${relativePath}:${index + 1}: ${line.trim()}`);
            }
        });
    }
}

walk(jsDir);

if (violations.length > 0) {
    console.error('✖ Hardcoded API path(s) found outside resources/js/routes.js:\n');
    console.error(violations.join('\n'));
    console.error('\nAdd the endpoint to resources/js/routes.js and import `routes` instead (see .ai/rules/views.md).');
    process.exit(1);
}

console.log('✓ No hardcoded API paths found outside resources/js/routes.js.');
