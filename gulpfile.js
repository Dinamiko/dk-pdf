var gulp = require( 'gulp' );
var zip = require( 'gulp-zip' );

gulp.task( 'zip', function() {
	gulp.src( [
		'*.{php,txt,jpg}',
		'assets/**',
		'includes/**',
		'languages/**',
		'templates/**',
		'vendor/**',
		'lib/**',
		], {
			base: '.'
		} )
		.pipe( zip( 'dk-pdf.zip' ) )
		.pipe( gulp.dest( '.' ) );
} );
