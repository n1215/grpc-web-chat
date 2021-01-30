const path = require("path");
const outputPath = path.resolve(__dirname, 'dist');

module.exports = {
    mode: 'development',
    entry: './src/index.ts',
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: 'ts-loader',
            },
        ],
    },
    resolve: {
        extensions: [
            '.ts', '.js',
        ],
    },
    devServer: {
        contentBase: outputPath
    }
}
