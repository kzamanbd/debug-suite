const fs = require('fs-extra');
const { exec } = require('child_process');

const pluginFiles = [
    '.wordpress-org', // This is a hidden file, so it should be copied as is.
    'assets/',
    'includes/',
    'languages/',
    'vendor/',
    'CHANGELOG.md',
    'readme.txt',
    'debug-suite.php',
    'uninstall.php',
    'composer.json',
];

const removeFiles = ['assets/src', 'composer.lock'];

const { version } = JSON.parse(fs.readFileSync('package.json'));

exec(
    'rm -rf *',
    {
        cwd: 'build',
    },
    (error) => {
        if (error) {
            console.log(
                '\x1b[33m%s\x1b[0m',
                `⚠️ Could not find the build directory.`
            );
            console.log(
                '\x1b[32m%s\x1b[0m',
                `🗂 Creating the build directory ...`
            );
            // Making build folder.
            fs.mkdirp('build');
        }

        const dest = 'build/debug-suite'; // Temporary folder name after coping all the files here.
        fs.mkdirp(dest);

        console.log(`🗜 Started making the zip ...`);
        try {
            console.log(`⚙️ Copying plugin files ...`);

            // Coping all the files into build folder.
            pluginFiles.forEach((file) => {
                fs.copySync(file, `${dest}/${file}`);
            });
            console.log(`📂 Finished copying files.`);
        } catch (err) {
            console.error('\x1b[31m%s\x1b[0m', '❌ Could not copy plugin files.', err);
            return;
        }

        exec(
            'composer install --optimize-autoloader --no-dev',
            {
                cwd: dest
            },
            (error) => {
                if (error) {
                    console.log(
                        '\x1b[31m%s\x1b[0m',
                        `❌ Could not install composer in ${dest} directory.`
                    );
                    console.log('\x1b[41m\x1b[30m%s\x1b[0m', error);

                    return;
                }

                console.log(
                    `⚡️ Installed composer packages in ${dest} directory.`
                );

                // Removing files that is not needed in the production now.
                removeFiles.forEach((file) => {
                    fs.removeSync(`${dest}/${file}`);
                });

                // Output zip file name.
                const zipFile = `debug-suite-v${version}.zip`;

                console.log(`📦 Making the zip file ${zipFile} ...`);

                // Making the zip file here.
                exec(
                    `zip ${zipFile} debug-suite -rq`,
                    {
                        cwd: 'build'
                    },
                    (error) => {
                        if (error) {
                            console.log(
                                '\x1b[31m%s\x1b[0m',
                                `❌ Could not make ${zipFile}.`
                            );
                            console.log('\x1b[41m\x1b[30m%s\x1b[0m', error);

                            return;
                        }

                        fs.removeSync(dest);
                        console.log(
                            '\x1b[32m%s\x1b[0m',
                            `✅  ${zipFile} is ready. 🎉`
                        );
                    }
                );
            }
        );
    }
);