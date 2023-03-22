const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const isProduction = process.env.NODE_ENV === 'production';

const updatedConfig = { ...defaultConfig };

if (!isProduction) {
	updatedConfig.devServer = {
		devMiddleware: {
			writeToDisk: true,
		},
		allowedHosts: 'all',
		host: 'localhost',
		port: 8887,
		proxy: {
			'/assets/dist': {
				pathRewrite: {
					'^/assets/dist': '',
				},
			},
		},
	};
}

module.exports = updatedConfig;