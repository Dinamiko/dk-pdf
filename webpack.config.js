const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'admin-font-manager': path.resolve(process.cwd(), 'resources/js/admin', 'font-manager.js'),
		'admin-template-set-manager': path.resolve(process.cwd(), 'resources/js/admin', 'template-set-manager.js'),
		'admin-settings': path.resolve(process.cwd(), 'resources/js/admin', 'settings.js'),
		'admin-ace': path.resolve(process.cwd(), 'resources/js/admin', 'ace-editor.js'),
		'frontend': path.resolve(process.cwd(), 'resources/js/frontend', 'index.js'),
		'admin-style': path.resolve(process.cwd(), 'resources/css/admin', 'admin.scss'),
		'frontend-style': path.resolve(process.cwd(), 'resources/css/frontend', 'frontend.scss'),
	},
	output: {
		filename: '[name].js',
		path: path.resolve(process.cwd(), 'build'),
	},
};
