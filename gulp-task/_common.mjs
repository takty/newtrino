/**
 * Common functions for gulp process
 *
 * @author Takuto Yanagida
 * @version 2022-08-19
 */

import { createRequire } from 'module';
import path from 'path';

export function pkgDir(name) {
	const require = createRequire(import.meta.url);
	let r = null;
	try {
		r = require.resolve(name + '/package.json');
	} catch (e) {
		r = require.resolve(name);
	}
	return path.dirname(r);
}

export async function verStr(devPostfix = ' [dev]') {
	const require       = createRequire(import.meta.url);
	const getBranchName = require('current-git-branch');

	const bn  = getBranchName();
	const pkg = require('../package.json');

	return 'v' + pkg['version'] + ((bn === 'develop') ? devPostfix : '');
}
