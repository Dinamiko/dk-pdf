/**
 * DOM utility functions
 */

/**
 * Fade in an element
 *
 * @param {HTMLElement} element - Element to fade in
 * @param {number} duration - Duration in milliseconds
 */
export function fadeIn(element, duration = 200) {
	element.style.display = 'flex';
	element.style.opacity = '0';

	let start = null;
	const animate = (timestamp) => {
		if (!start) start = timestamp;
		const progress = timestamp - start;
		const opacity = Math.min(progress / duration, 1);

		element.style.opacity = opacity;

		if (progress < duration) {
			requestAnimationFrame(animate);
		}
	};

	requestAnimationFrame(animate);
}

/**
 * Fade out an element
 *
 * @param {HTMLElement} element - Element to fade out
 * @param {number} duration - Duration in milliseconds
 */
export function fadeOut(element, duration = 200) {
	element.style.opacity = '1';

	let start = null;
	const animate = (timestamp) => {
		if (!start) start = timestamp;
		const progress = timestamp - start;
		const opacity = Math.max(1 - (progress / duration), 0);

		element.style.opacity = opacity;

		if (progress < duration) {
			requestAnimationFrame(animate);
		} else {
			element.style.display = 'none';
		}
	};

	requestAnimationFrame(animate);
}

/**
 * Show an element
 *
 * @param {HTMLElement} element - Element to show
 */
export function show(element) {
	element.style.display = '';
}

/**
 * Hide an element
 *
 * @param {HTMLElement} element - Element to hide
 */
export function hide(element) {
	element.style.display = 'none';
}

/**
 * Check if element is visible
 *
 * @param {HTMLElement} element - Element to check
 * @returns {boolean}
 */
export function isVisible(element) {
	return element.offsetParent !== null;
}

/**
 * Empty an element's content
 *
 * @param {HTMLElement} element - Element to empty
 */
export function empty(element) {
	while (element.firstChild) {
		element.removeChild(element.firstChild);
	}
}

/**
 * Get or set element value
 *
 * @param {HTMLElement} element - Element
 * @param {string|undefined} value - Value to set (if undefined, returns current value)
 * @returns {string|void}
 */
export function val(element, value) {
	if (value === undefined) {
		return element.value;
	}
	element.value = value;
}
