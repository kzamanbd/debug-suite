const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const webpack = require('webpack');

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
    plugins: [
        ...defaults.plugins,
        new webpack.DefinePlugin({
            'process': {}
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
