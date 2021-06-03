/**
 *
 * Common (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2021-06-03
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const setVariableVh = () => {
		const vh = window.innerHeight * 0.01;
		document.documentElement.style.setProperty('--vh', `${vh}px`);
	}
	window.addEventListener('resize', setVariableVh);
	setVariableVh();
});
