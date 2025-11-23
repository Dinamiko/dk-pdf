/**
 * AJAX utility using native fetch API
 */

/**
 * Make an AJAX request using fetch
 *
 * @param {string} action - WordPress AJAX action
 * @param {Object} data - Data to send with the request
 * @param {Object} options - Additional options
 * @returns {Promise} - Promise that resolves with the response data
 */
export async function ajaxRequest(action, data = {}, options = {}) {
	const { nonce = window.dkpdf_ajax?.nonce, method = 'POST' } = options;

	const formData = new FormData();
	formData.append('action', action);
	formData.append('nonce', nonce);

	Object.keys(data).forEach(key => {
		if (data[key] instanceof File) {
			formData.append(key, data[key]);
		} else {
			formData.append(key, data[key]);
		}
	});

	const fetchOptions = {
		method,
		body: formData,
		credentials: 'same-origin',
	};

	if (options.processData === false) {
		delete fetchOptions.body;
		fetchOptions.body = data;
	}

	try {
		const response = await fetch(window.dkpdf_ajax?.ajax_url || '/wp-admin/admin-ajax.php', fetchOptions);

		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}

		const result = await response.json();
		return result;
	} catch (error) {
		console.error('AJAX request failed:', error);
		throw error;
	}
}
