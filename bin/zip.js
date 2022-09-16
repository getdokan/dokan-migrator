const fs = require('fs-extra');
const path = require('path');
const { exec } = require('child_process');
const util = require('util');
// const replace = require('replace-in-file');
const chalk = require('chalk');
const _ = require('lodash');

const asyncExec = util.promisify(exec);

const pluginFiles = [
    'assets/',
    'includes/',
    'languages/',
    'templates/',
    'changelog.txt',
    'dokan-migrator.php',
];

const removeFiles = [ 'src', 'composer.json', 'composer.lock' ];

const allowedVendorFiles = {};

const { version } = JSON.parse(fs.readFileSync('package.json'));

fs.removeSync('dist/*.zip');

exec(
    'rm -rf versions && rm *.zip',
    {
        cwd: 'dist',
    },
    () => {
        const planDir = `dist`;
        const dest = `cp-${version}`;
        const composerfile = `composer.json`;

        fs.removeSync(planDir);

        const fileList = [...pluginFiles];

        fs.mkdirp(dest);

        fileList.forEach((file) => {
            fs.copySync(file, `${dest}/${file}`);
        });

        // copy composer.json file
        try {
            if (fs.pathExistsSync(composerfile)) {
                fs.copySync(composerfile, `${dest}/composer.json`);
            } else {
                fs.copySync(`composer.json`, `${dest}/composer.json`);
            }
        } catch (err) {
            console.error(err);
        }

        console.log(`Finished copying files.`);

        asyncExec(
            'composer install --optimize-autoloader --no-dev',
            {
                cwd: dest,
            },
            () => {
                console.log(
                    `Installed composer packages in ${dest} directory.`
                );

                removeFiles.forEach((file) => {
                    fs.removeSync(`${dest}/${file}`);
                });

                Object.keys(allowedVendorFiles).forEach((composerPackage) => {
                    const packagePath = path.resolve(
                        `${dest}/vendor/${composerPackage}`
                    );

                    if (!fs.existsSync(packagePath)) {
                        return;
                    }

                    const list = fs.readdirSync(packagePath);
                    const deletables = _.difference(
                        list,
                        allowedVendorFiles[composerPackage]
                    );

                    deletables.forEach((deletable) => {
                        fs.removeSync(path.resolve(packagePath, deletable));
                    });
                });

                // replace({
                //     files: `${dest}/dokan-pro.php`,
                //     from: `private $plan = 'dokan-pro';`,
                //     to: `private $plan = 'dokan-${plan}';`,
                // });

                const zipFile = `dokan-migrator-${version}.zip`;

                console.log(`Making zip file ${zipFile}...`);

                asyncExec(
                    `zip ../${zipFile} dokan-pro -rq`,
                    {
                        cwd: planDir,
                    },
                    () => {
                        fs.removeSync(planDir);
                        console.log(chalk.green(`${zipFile} is ready.`));
                    }
                ).catch((error) => {
                    console.log(chalk.red(`Could not make ${zipFile}.`));
                    console.log(error);
                });
            }
        ).catch((error) => {
            console.log(
                chalk.red(`Could not install composer in ${dest} directory.`)
            );
            console.log(error);
        });
    }
);