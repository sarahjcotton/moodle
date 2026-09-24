// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Shared internal utilities for the swizzle library.
 *
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import fs from 'fs';
import path from 'path';
import {createRequire} from 'module';
import {fileURLToPath} from 'url';

const _require = createRequire(fileURLToPath(import.meta.url));

/** Filename of a plugin's swizzle declaration file. */
export const PLUGIN_MANIFEST_FILENAME = 'swizzle.json';

/**
 * Parse a @moodle/lms specifier into its component and module parts.
 *
 * @param {string} specifier e.g. @moodle/lms/local_swizzledemo/local_swizzledemo_button
 * @returns {{subpath: string, component: string, module: string}|null} null when the specifier is invalid.
 */
export function parseSpecifier(specifier) {
    const subpath = specifier.replace(/^@moodle\/lms\//, '');
    const slashIdx = subpath.indexOf('/');
    if (slashIdx === -1) {
        return null;
    }
    return {subpath, component: subpath.slice(0, slashIdx), module: subpath.slice(slashIdx + 1)};
}

/**
 * Load Moodle component paths from .grunt/components.js.
 *
 * fetchComponentData() uses process.cwd() to locate lib/components.json, so we
 * temporarily chdir to rootDir to ensure it finds the right files regardless of
 * where the CLI was invoked from.
 *
 * @param {string} rootDir
 * @returns {Record<string, string>}
 */
export function loadComponents(rootDir) {
    const {fetchComponentData} = _require(path.join(rootDir, '.grunt', 'components.js'));
    const savedCwd = process.cwd();
    try {
        process.chdir(rootDir);
        return fetchComponentData().components;
    } finally {
        process.chdir(savedCwd);
    }
}

/**
 * Describe how a source file exports its component.
 *
 * @param {string} filePath   Absolute path to the .ts/.tsx source file.
 * @param {string} moduleName Module part of the specifier, e.g. views/ActivityIcon.
 * @returns {{kind: 'default'}|{kind: 'named', name: string}|null} null when no component export is found.
 */
export function detectComponentExport(filePath, moduleName) {
    const source = fs.readFileSync(filePath, 'utf8');

    if (/export\s+default\b/.test(source)) {
        return {kind: 'default'};
    }

    const basename = moduleName.split('/').pop();
    // A basename comes from a filename and may carry regex metacharacters.
    const quoted = basename.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const declared = String.raw`export\s+(?:async\s+)?(?:function|const|let|class)\s+`;
    const named = new RegExp(`(?:${declared}|export\\s*\\{[^}]*\\b)${quoted}\\b`);

    // Only an export named after the module counts. A file may export several
    // components, and picking one of them would be a guess.
    return named.test(source) ? {kind: 'named', name: basename} : null;
}

/**
 * Convert a module path into a valid PascalCase JavaScript identifier.
 *
 * @param {string} moduleName Module part of the specifier.
 * @returns {string}
 */
export function toIdentifier(moduleName) {
    // Only the final segment: views/ActivityIcon names its wrapper ActivityIcon.
    const identifier = moduleName.split('/').pop()
        .replace(/(?:^|[_-]+)([a-z0-9])/g, (_, character) => character.toUpperCase())
        .replace(/[^A-Za-z0-9_$]/g, '');

    // A leading digit is legal in a module name but not in an identifier.
    return /^[0-9]/.test(identifier) ? `Component${identifier}` : identifier;
}
