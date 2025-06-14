const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

const entries = {
    'debug-suite-admin': './admin/index.tsx'
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
            admin: path.resolve('./admin/')
        }
    },
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
    }
};
