/**
 * Frontend JavaScript
 * Ready for future vanilla JS features
 */

class DKPDFFrontend {
	constructor() {
		this.init();
	}

	init() {
		// Future frontend functionality will go here
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		new DKPDFFrontend();
	});
} else {
	new DKPDFFrontend();
}
