const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const entries = {
    'debug-suite': './src/index.tsx'
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
        ...defaults.plugins,
        new webpack.DefinePlugin({
            process: {}
        }),
        new MiniCssExtractPlugin({
            filename: '../css/[name].css'
        })
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
