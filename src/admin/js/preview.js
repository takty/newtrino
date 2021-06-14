/**
 *
 * Preview (JS)
 *
 * @author Takuto Yanagida
 * @version 2021-06-14
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const as = document.getElementsByTagName('a');
	for (const a of as) {
		a.setAttribute('target', 'new');
		a.setAttribute('rel', 'noreferrer noopener');
	}
});
