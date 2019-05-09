module.exports = function(grunt) {
    grunt.initConfig({
        shell: {
            rename: {
                command:
                    'cp pagantis.zip pagantis-$(git rev-parse --abbrev-ref HEAD).zip \n'
            },
            composerProd: {
                command: 'composer install --no-dev  --ignore-plaftorm-reqs'
            },
            composerDev: {
                command: 'composer install --ignore-plaftorm-reqs'
            },
        },
        compress: {
            main: {
                options: {
                    archive: 'pagantis.zip'
                },
                files: [
                    { expand: true, cwd: 'catalog/', src: ['**'], dest: '/', filter: 'isFile'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.registerTask('default', [
        'shell:composerProd',
        'compress',
        'shell:rename'
    ]);

    //manually run the selenium test: "grunt shell:testPrestashop16"
};
