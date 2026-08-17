import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { ConfigProvider, theme } from 'antd';
import idID from 'antd/locale/id_ID';
import { ThemeProvider, useTheme } from './Contexts/ThemeContext';
import { registerSW } from './utils/swRegistration';
import InstallPrompt from './Components/PWA/InstallPrompt';
import ErrorBoundary from './Components/Shared/ErrorBoundary';

registerSW();

// Material Design 3 tokens — seed teal + tonal shape + Roboto Flex
const m3Tokens = {
    // M3 seed color (finance teal)
    colorPrimary: '#00897B',
    colorInfo: '#00897B',
    colorSuccess: '#00897B',
    colorWarning: '#FFB300',
    colorError: '#BA1A1A',
    // M3 shape scale
    borderRadius: 12,
    borderRadiusLG: 16,
    borderRadiusSM: 8,
    // Typography
    fontFamily: "'Roboto Flex', 'Roboto', system-ui, sans-serif",
    // Motion
    motionDurationMid: '0.3s',
    motionDurationSlow: '0.4s',
};

const m3Components = {
    Button: { borderRadius: 20, controlHeight: 40 },
    Card: { borderRadiusLG: 16 },
    Input: { borderRadius: 12 },
    Select: { borderRadius: 12 },
    Modal: { borderRadiusLG: 20 },
    Tag: { borderRadiusSM: 999 },
};

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

    useEffect(() => {
        document.documentElement.setAttribute('data-theme', currentTheme);
    }, [currentTheme]);

    return (
        <ConfigProvider
            locale={idID}
            theme={{
                algorithm:
                    currentTheme === 'dark'
                        ? theme.darkAlgorithm
                        : theme.defaultAlgorithm,
                token: m3Tokens,
                components: m3Components,
            }}
        >
            <ErrorBoundary>
                <App {...props} />
                <InstallPrompt />
            </ErrorBoundary>
        </ConfigProvider>
    );
}
