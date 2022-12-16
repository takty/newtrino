/**
 * Preview
 *
 * @author Takuto Yanagida
 * @version 2022-12-16
 */

document.addEventListener('DOMContentLoaded', () => {
	const as = document.getElementsByTagName('a');
	for (const a of as) {
		a.setAttribute('target', 'new');
		a.setAttribute('rel', 'noreferrer noopener');
	}
});
