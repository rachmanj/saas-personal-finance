import { useState } from 'react';
import { Button, Tag, App } from 'antd';
import { BellOutlined } from '@ant-design/icons';
import { apiPost } from '../../utils/api';

export default function NotificationPermission() {
    const { message } = App.useApp();
    const [status, setStatus] = useState(() => {
        if (!('Notification' in window)) {
            return 'unsupported';
        }
        return Notification.permission;
    });
    const [loading, setLoading] = useState(false);

    const handleEnable = async () => {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            message.error('Push notifications are not supported in this browser');
            return;
        }

        setLoading(true);
        try {
            const permission = await Notification.requestPermission();
            setStatus(permission);

            if (permission !== 'granted') {
                message.warning('Notification permission denied');
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: null,
            });

            const json = subscription.toJSON();
            await apiPost('/api/bill-reminders/subscribe', {
                endpoint: json.endpoint,
                key: json.keys?.p256dh,
                token: json.keys?.auth,
            });

            message.success('Push notifications enabled');
        } catch (err) {
            message.error(err.message || 'Failed to enable notifications');
        } finally {
            setLoading(false);
        }
    };

    if (status === 'unsupported') {
        return <Tag color="default">Push notifications not supported</Tag>;
    }

    if (status === 'granted') {
        return <Tag color="green" icon={<BellOutlined />}>Notifications enabled</Tag>;
    }

    if (status === 'denied') {
        return <Tag color="red">Notifications blocked — enable in browser settings</Tag>;
    }

    return (
        <Button
            type="primary"
            icon={<BellOutlined />}
            loading={loading}
            onClick={handleEnable}
        >
            Enable Bill Reminders
        </Button>
    );
}
