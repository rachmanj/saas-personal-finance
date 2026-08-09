import { Head, usePage } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TelegramSettings from '../../Components/Settings/TelegramSettings';

/**
 * @param {{ telegram: import('../../Components/Settings/TelegramSettings').telegram }} props
 */
function TelegramContent({ telegram }) {
    return (
        <AuthenticatedLayout title="Settings">
            <Head title="Telegram" />
            <TelegramSettings telegram={telegram} />
        </AuthenticatedLayout>
    );
}

export default function Telegram(props) {
    return (
        <App>
            <TelegramContent {...props} />
        </App>
    );
}
