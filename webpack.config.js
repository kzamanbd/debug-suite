const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const rtlcss = require(
    require.resolve('rtlcss', {
        paths: [path.dirname(require.resolve('@wordpress/scripts/config/webpack.config'))]
    })
);

const entries = {
    'debug-suite': './src/index.tsx',
    'debug-console': './src/console.tsx',
    'email-log': './src/pages/email-log/index.tsx',
    'api-logger': './src/pages/api-log/index.tsx'
};

// RTL CSS plugin using modern webpack 5 processAssets hook
// (webpack-rtl-plugin uses the deprecated emit hook which fails in dev mode)
const RTLPlugin = {
    apply(compiler) {
        compiler.hooks.compilation.tap('RtlCssPlugin', (compilation) => {
            compilation.hooks.processAssets.tapAsync(
                {
                    name: 'RtlCssPlugin',
                    stage: webpack.Compilation.PROCESS_ASSETS_STAGE_OPTIMIZE
                },
                (assets, callback) => {
                    Array.from(compilation.chunks).forEach((chunk) => {
                        Array.from(chunk.files)
                            .filter((file) => file.endsWith('.css') && !file.endsWith('-rtl.css'))
                            .forEach((file) => {
                                const src = compilation.assets[file].source();
                                const rtlSrc = rtlcss.process(src);
                                const rtlFile = file.replace(/\.css$/, '-rtl.css');
                                compilation.assets[rtlFile] = new webpack.sources.RawSource(rtlSrc);
                                chunk.files.add(rtlFile);
                            });
                    });
                    callback();
                }
            );
        });
    }
};

module.exports = {
    ...defaults,
    entry: entries,
    output: {
        ...defaults.output,
        path: path.resolve(__dirname, 'assets/js'),
        filename: '[name].js'
    },
    resolve: {
        ...defaults.resolve,
        extensions: ['.tsx', '.ts', '.js', '.jsx'],
        alias: {
            '@': path.resolve(__dirname, 'src')
        }
    },
    module: {
        ...defaults.module,
        rules: [
            // Filter out any default CSS rules
            ...defaults.module.rules.filter((rule) => {
                if (rule.test && rule.test.toString) {
                    const testStr = rule.test.toString();
                    return !(testStr.includes('css') || testStr.includes('scss') || testStr.includes('sass'));
                }
                return true;
            }),
            // Add our own CSS rule only
            {
                test: /\.css$/i,
                include: path.resolve(__dirname, 'src'),
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            importLoaders: 1,
                            sourceMap: false
                        }
                    },
                    {
                        loader: 'postcss-loader',
                        options: {
                            sourceMap: false
                        }
                    }
                ]
            }
        ]
    },
    plugins: [
        // Filter out default MiniCssExtractPlugin and RtlCssPlugin to avoid duplicates
        ...defaults.plugins.filter(
            (plugin) => !(plugin instanceof MiniCssExtractPlugin) && plugin.constructor.name !== 'RtlCssPlugin'
        ),
        new webpack.DefinePlugin({
            process: {}
        }),
        new MiniCssExtractPlugin({
            filename: '../css/[name].css'
        }),
        RTLPlugin
    ],
    externals: {
        react: 'React',
        'react-dom': 'ReactDOM'
    },
    cache: {
        type: 'filesystem',
        allowCollectingMemory: true,
        buildDependencies: {
            config: [__filename]
        }
    },
    watchOptions: {
        ignored: ['**/assets/**'] // Ignore the generated build files to avoid unnecessary rebuilds
    },
    optimization: {
        ...defaults.optimization,
        moduleIds: 'deterministic',
        chunkIds: 'deterministic',
        emitOnErrors: false
    },
    // Increase the size limits
    performance: {
        maxAssetSize: 1000000, // 1000KB
        maxEntrypointSize: 1000000, // 1000KB
        hints: 'warning'
    }
};
