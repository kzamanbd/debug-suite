const fs = require('fs-extra');
const replace = require('replace-in-file');

const pluginFiles = ['includes/**/*', 'src/**/*', 'debug-suite.php', 'tests/**/*', 'uninstall.php'];

const { version } = JSON.parse(fs.readFileSync('package.json'));

replace.replaceInFile({
    files: pluginFiles,
    from: [/PLUGIN_SINCE/g],
    to: version
});
