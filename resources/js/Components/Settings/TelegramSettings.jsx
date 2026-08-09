import { SendOutlined, LinkOutlined, DisconnectOutlined, CheckCircleOutlined } from '@ant-design/icons';
import { router } from '@inertiajs/react';
import { App, Button, Card, Descriptions, Space, Switch, Tag, Typography } from 'antd';
import { useState } from 'react';

const { Text, Paragraph } = Typography;

/**
 * @param {{
 *   telegram: {
 *     linked: boolean;
 *     username?: string;
 *     first_name?: string;
 *     is_active?: boolean;
 *     settings?: Record<string, boolean>;
 *     linked_at?: string;
 *   } | null;
 * }} props
 */
export default function TelegramSettings({ telegram }) {
    const { message } = App.useApp();
    const [settings, setSettings] = useState(telegram?.settings || {});
    const [saving, setSaving] = useState(false);
    const [unlinking, setUnlinking] = useState(false);

    const isLinked = telegram?.linked === true;

    const handleToggle = async (key, checked) => {
        const newSettings = { ...settings, [key]: checked };
        setSettings(newSettings);
        setSaving(true);

        try {
            const response = await fetch('/api/telegram/settings', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(newSettings),
            });

            if (response.ok) {
                message.success('Notification preferences saved.');
            } else {
                setSettings(settings); // revert
                message.error('Failed to save preferences.');
            }
        } catch {
            setSettings(settings); // revert
            message.error('Network error. Please try again.');
        } finally {
            setSaving(false);
        }
    };

    const handleUnlink = async () => {
        setUnlinking(true);
        try {
            const response = await fetch('/api/telegram/unlink', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            if (response.ok) {
                message.success('Telegram account unlinked.');
                router.reload();
            } else {
                message.error('Failed to unlink Telegram account.');
            }
        } catch {
            message.error('Network error. Please try again.');
        } finally {
            setUnlinking(false);
        }
    };

    if (!isLinked) {
        return (
            <Card
                title={
                    <Space>
                        <SendOutlined />
                        <span>Hubungkan Telegram</span>
                    </Space>
                }
                bordered
            >
                <Paragraph type="secondary">
                    Hubungkan akun Telegram Anda untuk menerima notifikasi dan mengelola
                    keuangan Anda langsung dari chat.
                </Paragraph>

                <Card type="inner" style={{ marginTop: 16, background: 'var(--ant-color-fill-tertiary)' }}>
                    <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                        <Text strong>Cara menghubungkan:</Text>
                        <ol style={{ paddingLeft: 20, margin: 0 }}>
                            <li>Mulai chat dengan bot Telegram kami</li>
                            <li>
                                Kirim perintah <Text code>/link</Text> dengan token Anda
                            </li>
                            <li>Akun Anda akan terhubung secara otomatis</li>
                        </ol>
                        <Button
                            type="primary"
                            icon={<LinkOutlined />}
                            onClick={async () => {
                                try {
                                    const response = await fetch('/api/telegram/generate-link-token', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                        },
                                    });
                                    const data = await response.json();
                                    const token = data?.data?.token;
                                    if (token) {
                                        message.info(
                                            `Send this command to the bot: /link ${token}`,
                                            10,
                                        );
                                    }
                                } catch {
                                    message.error('Failed to generate link token.');
                                }
                            }}
                        >
                            Buat Token Tautan
                        </Button>
                    </Space>
                </Card>
            </Card>
        );
    }

    return (
        <Card
            title={
                <Space>
                    <SendOutlined />
                    <span>Koneksi Telegram</span>
                    <Tag color="green" icon={<CheckCircleOutlined />}>
                        Terhubung
                    </Tag>
                </Space>
            }
            bordered
        >
            <Descriptions column={1} size="small" style={{ marginBottom: 24 }}>
                <Descriptions.Item label="Username">
                    {telegram.username ? `@${telegram.username}` : '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Nama Depan">
                    {telegram.first_name || '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Terhubung Sejak">
                    {telegram.linked_at
                        ? new Date(telegram.linked_at).toLocaleDateString('id-ID', {
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric',
                          })
                        : '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Status">
                    {telegram.is_active ? (
                        <Tag color="green">Aktif</Tag>
                    ) : (
                        <Tag color="default">Nonaktif</Tag>
                    )}
                </Descriptions.Item>
            </Descriptions>

            <Card
                type="inner"
                title="Preferensi Notifikasi"
                style={{ marginBottom: 16 }}
            >
                <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div>
                            <Text strong>Ringkasan Harian</Text>
                            <br />
                            <Text type="secondary">Terima ringkasan harian keuangan Anda</Text>
                        </div>
                        <Switch
                            checked={settings.daily_summary ?? true}
                            onChange={(checked) => handleToggle('daily_summary', checked)}
                            loading={saving}
                        />
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div>
                            <Text strong>Peringatan Anggaran</Text>
                            <br />
                            <Text type="secondary">Dapatkan notifikasi saat mendekati batas anggaran</Text>
                        </div>
                        <Switch
                            checked={settings.budget_alerts ?? true}
                            onChange={(checked) => handleToggle('budget_alerts', checked)}
                            loading={saving}
                        />
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div>
                            <Text strong>Pengingat Tagihan</Text>
                            <br />
                            <Text type="secondary">Pengingat sebelum tagihan jatuh tempo</Text>
                        </div>
                        <Switch
                            checked={settings.bill_reminders ?? true}
                            onChange={(checked) => handleToggle('bill_reminders', checked)}
                            loading={saving}
                        />
                    </div>
                </Space>
            </Card>

            <Button
                danger
                icon={<DisconnectOutlined />}
                onClick={handleUnlink}
                loading={unlinking}
            >
                Putuskan Tautan Telegram
            </Button>
        </Card>
    );
}
