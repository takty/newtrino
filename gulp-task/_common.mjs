/**
 * Common functions for gulp process
 *
 * @author Takuto Yanagida
 * @version 2022-10-17
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

export function getPkgJson() {
	const req = createRequire(import.meta.url);
	return req('../package.json');
}

export function getBranchName() {
	const req = createRequire(import.meta.url);
	const cgb = req('current-git-branch');
	return cgb();
}

export async function verStr(devPostfix = ' [dev]') {
	const isDev = getBranchName() === 'develop';
	const pkg   = getPkgJson();

	return 'v' + pkg['version'] + (isDev ? devPostfix : '');
}

export async function fullVerStr() {
	const isDev = getBranchName() === 'develop';
	const pkg   = getPkgJson();

	const { DateTime } = await import('luxon');

	const VER       = pkg['version'];
	const VER_MAJOR = VER.split('.')[0];
	const VER_PF    = (isDev ? '[dev]' : '') + DateTime.local().toFormat('yyMMdd');
	const VER_FULL  = `${VER}-${VER_PF}`;

	return [VER, VER_MAJOR, VER_FULL];
}
