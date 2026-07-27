import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { ConfigProvider, theme } from 'antd';
import { ThemeProvider, useTheme } from './Contexts/ThemeContext';
import { registerSW } from './utils/swRegistration';
import InstallPrompt from './Components/PWA/InstallPrompt';
import ErrorBoundary from './Components/Shared/ErrorBoundary';

registerSW();

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
            <ErrorBoundary>
                <App {...props} />
                <InstallPrompt />
            </ErrorBoundary>
        </ConfigProvider>
    );
}
