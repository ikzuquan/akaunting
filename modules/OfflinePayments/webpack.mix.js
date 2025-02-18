const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

//mix.js('resources/assets/js/views/**/*.js', 'public/js')

mix.options({
        terser: {
            extractComments: false,
        }
    })
    .js('Resources/assets/js/offline-payments.js', 'Resources/assets/js/offline-payments.min.js')
    .sass('./../../resources/assets/sass/argon.scss', './../../public/css')
    .vue();
