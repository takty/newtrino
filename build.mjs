import { execFile } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { promisify } from 'node:util';

import {
	buildJavaScriptFile,
	buildJavaScriptFiles,
	buildSassFile,
	copyFileIfChanged,
	copyFilesFiltered,
	preventOverlap,
	resolvePackageDir,
	watch,
	writeFileIfChanged,
} from './tasks.mjs';

const execFileAsync = promisify(execFile);

const SRC_DIR    = './src';
const DIST_DIR   = './dist';
const SAMPLE_DIR = './sample/nt';

const ADMIN_SRC_DIR  = path.join(SRC_DIR, 'admin');
const ADMIN_DIST_DIR = path.join(DIST_DIR, 'admin');

const CORE_SRC_DIR  = path.join(SRC_DIR, 'core');
const CORE_DIST_DIR = path.join(DIST_DIR, 'core');

const UNUSED_TINYMCE_PLUGINS = new Set([
	'autoresize',
	'autosave',
	'bbcode',
	'codesample',
	'colorpicker',
	'contextmenu',
	'emoticons',
	'fullpage',
	'fullscreen',
	'help',
	'imagetools',
	'importcss',
	'legacyoutput',
	'pagebreak',
	'preview',
	'save',
	'spellchecker',
	'tabfocus',
	'template',
	'textcolor',
	'toc',
	'wordcount',
]);

async function getVersion() {
	const packageJson = JSON.parse(await readFile('./package.json', 'utf8'));

	let branch = '';
	try {
		const { stdout } = await execFileAsync('git', ['branch', '--show-current']);
		branch = stdout.trim();
	} catch {
		// Use the package version without a development suffix outside a Git repository.
	}

	return `v${packageJson.version}${branch === 'develop' ? ' [dev]' : ''}`;
}

async function copyAdminLibraries() {
	const nacssResetDir = resolvePackageDir('nacss-reset');
	const jsshaDir      = resolvePackageDir('jssha');
	const luxonDir      = resolvePackageDir('luxon');
	const flatpickrDir  = resolvePackageDir('flatpickr');
	const tinymceDir    = resolvePackageDir('tinymce');
	const tinymceI18nDir = path.resolve(tinymceDir, '..', 'tinymce-i18n');

	await Promise.all([
		copyFileIfChanged(
			path.join(nacssResetDir, 'dist/css/reset.min.css'),
			path.join(ADMIN_DIST_DIR, 'css/reset.min.css'),
		),
		copyFileIfChanged(
			path.join(nacssResetDir, 'dist/css/reset.min.css.map'),
			path.join(ADMIN_DIST_DIR, 'css/reset.min.css.map'),
		),
		copyFileIfChanged(
			path.join(jsshaDir, 'dist/sha256.js'),
			path.join(ADMIN_DIST_DIR, 'js/jssha/sha256.js'),
		),
		copyFileIfChanged(
			path.join(luxonDir, 'build/global/luxon.min.js'),
			path.join(ADMIN_DIST_DIR, 'js/luxon/luxon.min.js'),
		),
		copyFileIfChanged(
			path.join(luxonDir, 'build/global/luxon.min.js.map'),
			path.join(ADMIN_DIST_DIR, 'js/luxon/luxon.min.js.map'),
		),
		copyFileIfChanged(
			path.join(flatpickrDir, 'dist/flatpickr.min.js'),
			path.join(ADMIN_DIST_DIR, 'js/flatpickr/flatpickr.min.js'),
		),
		copyFileIfChanged(
			path.join(flatpickrDir, 'dist/flatpickr.min.css'),
			path.join(ADMIN_DIST_DIR, 'css/flatpickr/flatpickr.min.css'),
		),
		copyFileIfChanged(
			path.join(flatpickrDir, 'dist/l10n/ja.js'),
			path.join(ADMIN_DIST_DIR, 'js/flatpickr/ja.js'),
		),
		copyFileIfChanged(
			path.join(tinymceDir, 'tinymce.min.js'),
			path.join(ADMIN_DIST_DIR, 'js/tinymce/tinymce.min.js'),
		),
		copyFileIfChanged(
			path.join(tinymceI18nDir, 'langs5/ja.js'),
			path.join(ADMIN_DIST_DIR, 'js/tinymce/langs/ja.js'),
		),
		copyFilesFiltered(
			path.join(tinymceDir, 'skins'),
			path.join(ADMIN_DIST_DIR, 'js/tinymce/skins'),
		),
		copyFilesFiltered(
			path.join(tinymceDir, 'icons'),
			path.join(ADMIN_DIST_DIR, 'js/tinymce/icons'),
		),
		copyFilesFiltered(
			path.join(tinymceDir, 'themes/silver'),
			path.join(ADMIN_DIST_DIR, 'js/tinymce/themes/silver'),
		),
		copyFilesFiltered(
			path.join(tinymceDir, 'plugins'),
			path.join(ADMIN_DIST_DIR, 'js/tinymce/plugins'),
			(_srcPath, relPath) => {
				const [plugin] = relPath.split(path.sep);
				return !UNUSED_TINYMCE_PLUGINS.has(plugin);
			},
		),
	]);
}

async function copyAdminSource() {
	await copyFileIfChanged(
		path.join(SRC_DIR, 'login.php'),
		path.join(DIST_DIR, 'login.php'),
	);

	await copyFilesFiltered(ADMIN_SRC_DIR, ADMIN_DIST_DIR, (_srcPath, relPath) => {
		const parts = relPath.split(path.sep);

		if (parts[0] === 'sass') {
			return false;
		}
		if (parts[0] !== 'js') {
			return true;
		}
		return parts[1] === 'tinymce' && parts[2] === 'langs';
	});
}

async function copyAdminCss() {
	await copyFilesFiltered(
		path.join(ADMIN_SRC_DIR, 'sass'),
		path.join(ADMIN_DIST_DIR, 'css'),
		(_srcPath, relPath) => {
			if (relPath.includes(path.sep)) {
				return false;
			}

			const ext = path.extname(relPath);
			return ['.css', '.svg', '.png', '.woff2'].includes(ext);
		},
	);
}

async function buildAdminJavaScript() {
	await buildJavaScriptFiles(
		path.join(ADMIN_SRC_DIR, 'js'),
		path.join(ADMIN_DIST_DIR, 'js'),
		(_srcPath, relPath) => {
			const parts = relPath.split(path.sep);
			const name  = path.basename(relPath);

			if (name.startsWith('_')) {
				return false;
			}
			return !(parts[0] === 'tinymce' && parts[1] === 'langs');
		},
	);
}

async function buildAdminSass() {
	const srcPath = path.join(ADMIN_SRC_DIR, 'sass/style.scss');
	const dstPath = path.join(ADMIN_DIST_DIR, 'css/style.min.css');

	await buildSassFile(srcPath, dstPath);

	const version = await getVersion();
	const css     = await readFile(dstPath, 'utf8');
	const result  = css.replaceAll('%VERSION%', version);

	if (await writeFileIfChanged(dstPath, result)) {
		console.log(`written: ${dstPath}`);
	}
}

async function buildAdmin() {
	await copyAdminLibraries();
	await copyAdminSource();
	await copyAdminCss();
	await buildAdminJavaScript();
	await buildAdminSass();
}

async function copyCoreLibraries() {
	await copyFilesFiltered(
		'./vendor/mustache/mustache/src/Mustache',
		path.join(CORE_DIST_DIR, 'lib/Mustache'),
	);
}

async function copyCoreSource() {
	await copyFileIfChanged(
		path.join(SRC_DIR, 'index.php'),
		path.join(DIST_DIR, 'index.php'),
	);

	await copyFilesFiltered(CORE_SRC_DIR, CORE_DIST_DIR);
}

async function buildCoreJavaScript() {
	await buildJavaScriptFile(
		path.join(SRC_DIR, 'index.js'),
		path.join(DIST_DIR, 'index.min.js'),
	);
}

async function buildCore() {
	await copyCoreLibraries();
	await copyCoreSource();
	await buildCoreJavaScript();
}

async function copySampleNewtrino() {
	await copyFilesFiltered(DIST_DIR, SAMPLE_DIR);
}

async function copySampleData() {
	await copyFilesFiltered(
		path.join(SRC_DIR, 'data'),
		path.join(SAMPLE_DIR, 'data'),
		(_srcPath, relPath) => !(path.dirname(relPath) === '.' && path.extname(relPath) === '.js'),
	);
}

async function buildSampleJavaScript() {
	const srcDir = path.join(SRC_DIR, 'data');
	const dstDir = path.join(SAMPLE_DIR, 'data');

	await buildJavaScriptFiles(
		srcDir,
		dstDir,
		(_srcPath, relPath) => path.dirname(relPath) === '.',
	);
}

async function buildSample() {
	await copySampleNewtrino();
	await copySampleData();
	await buildSampleJavaScript();
}

async function build() {
	await Promise.all([
		buildAdmin(),
		buildCore(),
	]);
}

function startWatch() {
	const rebuildAdminSource     = preventOverlap(copyAdminSource);
	const rebuildAdminCss        = preventOverlap(copyAdminCss);
	const rebuildAdminJavaScript = preventOverlap(buildAdminJavaScript);
	const rebuildAdminSass       = preventOverlap(buildAdminSass);
	const rebuildCoreSource      = preventOverlap(copyCoreSource);
	const rebuildCoreJavaScript  = preventOverlap(buildCoreJavaScript);
	const rebuildSample          = preventOverlap(buildSample);

	watch(ADMIN_SRC_DIR, '', async () => {
		await rebuildAdminSource();
		await rebuildAdminCss();
		await rebuildAdminJavaScript();
		await rebuildAdminSass();
		await rebuildSample();
	});

	watch(CORE_SRC_DIR, '', async () => {
		await rebuildCoreSource();
		await rebuildSample();
	});

	watch(SRC_DIR, 'index.php', async () => {
		await rebuildCoreSource();
		await rebuildSample();
	});

	watch(SRC_DIR, 'index.js', async () => {
		await rebuildCoreJavaScript();
		await rebuildSample();
	});

	watch(path.join(SRC_DIR, 'data'), '', rebuildSample);
}

async function start() {
	await build();
	await buildSample();
	startWatch();
}

const command = process.argv[2] ?? 'start';

switch (command) {
	case 'admin':
		await buildAdmin();
		break;

	case 'core':
		await buildCore();
		break;

	case 'sample':
		await buildSample();
		break;

	case 'build':
		await build();
		break;

	case 'start':
		await start();
		break;

	default:
		throw new Error(`Unknown command: ${command}`);
}
