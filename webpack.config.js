module.exports = {
	entry: './src/default.js',
	output : {
		filename : './js/guests.bundle.js'
	},
	module: {
		loaders: [{
			test: /\.js?$/,
			exclude: /node_modules/,
			loader: 'babel-loader',
		}, {
			test: /\.less?$/,
			loader: 'style-loader!css-loader!less-loader'
		}]
	}
}
