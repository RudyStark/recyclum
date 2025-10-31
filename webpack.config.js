const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    .addEntry('app', './assets/app.js')

    .enableStimulusBridge('./assets/controllers.json')

    // Active React preset
    .enableReactPreset()

    // Configure complètement Babel
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // Force la configuration du loader Babel
    .configureBabel((babelConfig) => {}, {
        includeNodeModules: ['@symfony/ux-react'],
    })

    .enableSassLoader()

    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
;

// Force la configuration Webpack pour babel-loader
const config = Encore.getWebpackConfig();

// Trouve et modifie la règle babel-loader
config.module.rules.forEach((rule) => {
    if (rule.use) {
        rule.use.forEach((loader) => {
            if (loader.loader && loader.loader.includes('babel-loader')) {
                loader.options = loader.options || {};
                loader.options.sourceType = 'module';
                loader.options.presets = [
                    ['@babel/preset-env', { useBuiltIns: 'usage', corejs: 3 }],
                    ['@babel/preset-react', { runtime: 'automatic' }]
                ];
            }
        });
    }
});

module.exports = config;
