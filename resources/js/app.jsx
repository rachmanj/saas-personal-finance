import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { ConfigProvider, theme } from 'antd';
import { ThemeProvider, useTheme } from './Contexts/ThemeContext';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ThemeProvider>
                <ThemedApp App={App} props={props} />
            </ThemeProvider>,
        );
    },
});

function ThemedApp({ App, props }) {
    const { theme: currentTheme } = useTheme();

    return (
        <ConfigProvider
            theme={{
                algorithm:
                    currentTheme === 'dark'
                        ? theme.darkAlgorithm
                        : theme.defaultAlgorithm,
            }}
        >
            <App {...props} />
        </ConfigProvider>
    );
}
