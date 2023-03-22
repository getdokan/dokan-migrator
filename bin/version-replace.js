const fs = require( 'fs-extra' );
const replace = require( 'replace-in-file' );

const pluginFiles = [
    'includes/**/*',
    'templates/*',
    'src/*',
    'dokan-migrator.php',
];

const { version } = JSON.parse( fs.readFileSync( 'package.json' ) );

console.log( `🔄 Replacing plugin migrator versions ( DOKAN_MIG_SINCE ➡️ ${ version } )....` );

replace( {
    files: pluginFiles,
    from: /DOKAN_MIG_SINCE/g,
    to: version,
} );

console.log( `✅ All version replaced successfully. 🎉` );