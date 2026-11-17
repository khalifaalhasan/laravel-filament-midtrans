import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';

// Vite keeps a separate "direct" module for the <link rel="stylesheet"> version of app.css, and Tailwind (v3, PostCSS)
// changes in Blade files did not invalidate it, so new classes were missing until a restart. Invalidate every variant.
const refreshCssOnBladeChange = {
    name: 'refresh-css-on-blade-change',
    configureServer(server) {
        const css = path.resolve('resources/css/app.css');
        const onChange = (file) => {
            if (! file.endsWith('.blade.php')) return;
            server.moduleGraph.getModulesByFile(css)?.forEach((mod) => server.moduleGraph.invalidateModule(mod));
        };
        server.watcher.on('add', onChange);
        server.watcher.on('change', onChange);
    },
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        refreshCssOnBladeChange,
    ],
    server: {
        // Fixed port so this project never attaches to another project's Vite on the default 5173.
        port: 5180,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
