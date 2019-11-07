module.exports = function (grunt) {
  'use strict';

  require('jit-grunt')(grunt);
  require('time-grunt')(grunt);

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    less: {
      core: {
        options: {
          outputSourceFiles: true,
          sourceMap: true,
          sourceMapFilename: 'assets/css/style.css.map',
          sourceMapURL: 'style.css.map',
          strictMath: true
        },
        files: {
          'assets/css/style.css': 'less/style.less'
        }
      }
    },
    postcss: {
      options: {
        map: true,
        processors: [
          require('autoprefixer')
        ]
      },
      core: {
        src: 'assets/css/*.css'
      }
    },
    csscomb: {
      options: {
        config: 'less/.csscomb.json'
      },
      core: {
        src: 'assets/css/style.css',
        dest: 'assets/css/style.css'
      }
    },
    csslint: {
      options: {
        csslintrc: 'less/.csslintrc'
      },
      core: {
        src: 'assets/css/style.css'
      }
    },
    cssmin: {
      options: {
        advanced: false,
        keepSpecialComments: '*',
        sourceMap: true
      },
      core: {
        expand: true,
        cwd: 'assets/css',
        src: ['*.css', '!*.min.css'],
        dest: 'assets/css',
        ext: '.min.css'
      }
    },
    eslint: {
      options: {
        configFile: 'js/.eslintrc'
      },
      target: 'js/*.js'
    },
    jscs: {
      options: {
        config: 'js/.jscsrc'
      },
      grunt: {
        src: 'Gruntfile.js'
      },
      core: {
        src: 'js/*.js'
      }
    },
    concat: {
      core: {
        src: [
          'js/main.js'
        ],
        dest: 'assets/js/application.min.js'
      }
    },
    uglify: {
      options: {
        compress: {
          warnings: false
        },
        preserveComments: 'some'
      },
      core: {
        src: '<%= concat.core.dest %>',
        dest: 'assets/js/application.min.js'
      }
    },
    copy: {
      assets: {
        files: [
          {
            expand: true,
            cwd: 'node_modules/bootstrap-switch/dist',
            src: '**',
            dest: 'assets/vendor/bootstrap-switch'
          },
          {
            expand: true,
            cwd: 'node_modules/ion-rangeslider',
            src: ['css/*', 'img/*', 'js/*'],
            dest: 'assets/vendor/ion-rangeslider'
          },
          {
            expand: true,
            cwd: 'node_modules/select2/dist',
            src: '**',
            dest: 'assets/vendor/select2'
          }
        ]
      }
    },
    watch: {
      configFiles: {
        options: {
          reload: true
        },
        files: ['Gruntfile.js', 'package.json']
      },
      js: {
        files: 'js/*.js',
        tasks: 'js'
      },
      less: {
        files: 'less/**/*.less',
        tasks: 'css'
      }
    },
    clean: {
      options: {
        force: true
      },
      assets: [
        'assets/vendor/bootstrap-switch',
        'assets/vendor/select2'
      ],
      css: 'assets/css',
      js: 'assets/js'
    }
  });

  grunt.registerTask('assets', 'copy');
  grunt.registerTask('css', ['less', 'postcss', 'csscomb', 'csslint', 'cssmin']);
  grunt.registerTask('js', ['eslint', 'jscs', 'concat', 'uglify']);
  grunt.registerTask('js:dev', ['concat']);
  grunt.registerTask('build', ['assets', 'css', 'js']);

  grunt.registerTask('default', 'build');
};
