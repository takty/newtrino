import { watch as nodeWatch } from 'node:fs';
import { copyFile, cp, mkdir, readdir, readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';

export async function* walk(dir, suffix = '') {
	const es = await readdir(dir, { withFileTypes: true });

	for (const e of es) {
		const srcPath = path.join(dir, e.name);
		if (e.isDirectory()) {
			yield* walk(srcPath, suffix);
		} else if (e.isFile() && srcPath.endsWith(suffix)) {
			yield srcPath;
		}
	}
}

export async function filesEqual(a, b) {
	try {
		const [ba, bb] = await Promise.all([readFile(a), readFile(b)]);
		return ba.equals(bb);
	} catch {
		return false;
	}
}

export async function copyFileIfChanged(srcPath, dstPath) {
	if (await filesEqual(srcPath, dstPath)) {
		return false;
	}
	await mkdir(path.dirname(dstPath), { recursive: true });
	await copyFile(srcPath, dstPath);
	console.log(`copied: ${srcPath} -> ${dstPath}`);
	return true;
}

export async function copyFilesFiltered(srcDir, dstDir, filter = () => true) {
	let copied = 0;

	for await (const srcPath of walk(srcDir)) {
		const relPath = path.relative(srcDir, srcPath);
		if (!filter(srcPath, relPath)) {
			continue;
		}
		const dstPath = path.join(dstDir, relPath);
		if (await copyFileIfChanged(srcPath, dstPath)) {
			copied++;
		}
	}
	if (copied === 0) {
		console.log('no changes');
	}
	return copied;
}

export async function copyFiles(srcDir, dstDir, suffix = '') {
	return copyFilesFiltered(srcDir, dstDir, srcPath => srcPath.endsWith(suffix));
}

export async function copyDirectory(srcDir, dstDir) {
	await cp(srcDir, dstDir, { recursive: true });
}

export async function writeFileIfChanged(filePath, content) {
	try {
		const cur = await readFile(filePath, 'utf8');
		if (cur === content) {
			return false;
		}
	} catch (e) {
		if (e.code !== 'ENOENT') {
			throw e;
		}
	}
	await mkdir(path.dirname(filePath), { recursive: true });
	await writeFile(filePath, content, 'utf8');
	return true;
}

export async function processFiles(srcDir, dstDir, filter, transform) {
	let written = 0;

	for await (const srcPath of walk(srcDir)) {
		const relPath = path.relative(srcDir, srcPath);

		if (!filter(srcPath, relPath)) {
			continue;
		}
		const src     = await readFile(srcPath, 'utf8');
		const res     = await transform(src, srcPath);
		const dstPath = path.join(dstDir, relPath);

		if (await writeFileIfChanged(dstPath, res)) {
			console.log(`written: ${srcPath} -> ${dstPath}`);
			written++;
		}
	}
	if (written === 0) {
		console.log('no changes');
	}
	return written;
}

export function resolvePackageDir(name) {
	const req = createRequire(import.meta.url);
	let pkgPath;
	try {
		pkgPath = req.resolve(`${name}/package.json`);
	} catch {
		pkgPath = req.resolve(name);
	}
	return path.dirname(pkgPath);
}

export function preventOverlap(fn) {
	let running    = false;
	let pending    = false;
	let latestArgs = [];
	let latestThis = null;

	return async function (...args) {
		latestArgs = args;
		latestThis = this;
		pending    = true;

		if (running) return;
		running = true;

		try {
			while (pending) {
				pending = false;
				await fn.apply(latestThis, latestArgs);
			}
		} finally {
			running = false;
		}
	};
}

export function watch(srcDir, suffix, fn) {
	const fnSafely = preventOverlap(fn);

	nodeWatch(srcDir, { recursive: true }, (_eventType, filename) => {
		if (!filename || filename.endsWith(suffix)) {
			fnSafely();
		}
	});
}

export async function buildJavaScriptFile(srcPath, dstPath, { preprocess = async source => source, terserOptions = {} } = {}) {
	const { minify } = await import('terser');

	const source    = await readFile(srcPath, 'utf8');
	const processed = await preprocess(source, srcPath);
	const mapPath   = `${dstPath}.map`;

	const result = await minify(processed, {
		sourceMap: {
			filename: path.basename(dstPath),
			url     : path.basename(mapPath),
		},
		...terserOptions,
	});
	if (!result.code) {
		throw new Error(`Terser did not generate code: ${srcPath}`);
	}
	const results = [await writeFileIfChanged(dstPath, result.code)];
	if (result.map) {
		results.push(await writeFileIfChanged(mapPath, result.map));
	}
	if (results.some(Boolean)) {
		console.log(`written: ${srcPath} -> ${dstPath}`);
		return true;
	}
	return false;
}

export async function buildJavaScriptFiles(srcDir, dstDir, filter = () => true, options = {}) {
	let written = 0;

	for await (const srcPath of walk(srcDir, '.js')) {
		const relPath = path.relative(srcDir, srcPath);

		if (!filter(srcPath, relPath)) {
			continue;
		}
		const parsed  = path.parse(relPath);
		const dstPath = path.join(dstDir, parsed.dir, `${parsed.name}.min.js`);

		if (await buildJavaScriptFile(srcPath, dstPath, options)) {
			written++;
		}
	}
	if (written === 0) {
		console.log('no JavaScript changes');
	}
	return written;
}

export async function buildSassFile(srcPath, dstPath, { sassOptions = {}, autoprefixerOptions = { remove: false } } = {}) {
	const [sass, { default: postcss }, { default: autoprefixer }] = await Promise.all([
		import('sass'),
		import('postcss'),
		import('autoprefixer'),
	]);
	const compiled = await sass.compileAsync(srcPath, {
		style    : 'compressed',
		sourceMap: true,
		...sassOptions,
	});
	const result = await postcss([autoprefixer(autoprefixerOptions)]).process(compiled.css, {
		from: srcPath,
		to  : dstPath,
		map : {
			inline    : false,
			annotation: `${path.basename(dstPath)}.map`,
			prev      : compiled.sourceMap,
		},
	});
	const results = [await writeFileIfChanged(dstPath, result.css)];
	if (result.map) {
		results.push(await writeFileIfChanged(`${dstPath}.map`, result.map.toString()));
	}
	if (results.some(Boolean)) {
		console.log(`written: ${srcPath} -> ${dstPath}`);
		return true;
	}
	console.log('no changes');
	return false;
}
