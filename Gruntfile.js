module.exports = function(grunt) {
    grunt.initConfig({
        shell: {
            rename: {
                command:
                    'cp pagantis.zip pagantis-$(git rev-parse --abbrev-ref HEAD).zip \n'
            },
            composerProd: {
                command: 'composer install --no-dev'
            },
            composerDev: {
                command: 'composer install'
            },
            runTestOscommerce: {
                command:
                    'docker-compose down\n' +
                    'docker-compose up -d selenium\n' +
                    'docker-compose up -d prestashop17-test\n' +
                    'echo "Creating the prestashop17-test"\n' +
                    'sleep 100\n' +
                    'date\n' +
                    'docker-compose logs prestashop17-test\n' +
                    'set -e\n' +
                    'vendor/bin/phpunit --group prestashop17basic\n' +
                    'vendor/bin/phpunit --group prestashop17install\n' +
                    'vendor/bin/phpunit --group prestashop17register\n' +
                    'vendor/bin/phpunit --group prestashop17buy\n' +
                    'vendor/bin/phpunit --group prestashop17advanced\n' +
                    'vendor/bin/phpunit --group prestashop17validate\n' +
                    'vendor/bin/phpunit --group prestashop17controller\n'
            }
        },
        compress: {
            main: {
                options: {
                    archive: 'pagantis.zip'
                },
                files: [
                    {src: ['ext/**'], dest: 'pagantis/', filter: 'isFile'},
                    {src: ['includes/**'], dest: 'pagantis/', filter: 'isFile'}
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
