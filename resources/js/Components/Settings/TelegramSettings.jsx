import { SendOutlined, LinkOutlined, DisconnectOutlined, CheckCircleOutlined, CopyOutlined } from '@ant-design/icons';
import { router, usePage } from '@inertiajs/react';
import { App, Button, Card, Descriptions, Space, Switch, Tag, Typography, Alert } from 'antd';
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
    const { props } = usePage();
    const [saving, setSaving] = useState(false);
    const [unlinking, setUnlinking] = useState(false);

    const isLinked = telegram?.linked === true;
    const linkToken = props.flash?.link_token ?? null;

    const handleToggle = (key, checked) => {
        setSaving(true);
        router.put('/settings/telegram/settings', { [key]: checked }, {
            onFinish: () => setSaving(false),
            onError: () => {
                setSaving(false);
                message.error('Gagal menyimpan preferensi.');
            },
        });
    };

    const handleUnlink = () => {
        setUnlinking(true);
        router.delete('/settings/telegram/unlink', {
            onFinish: () => setUnlinking(false),
            onError: () => {
                setUnlinking(false);
                message.error('Gagal memutus tautan Telegram.');
            },
        });
    };

    const copyToken = () => {
        navigator.clipboard?.writeText(linkToken);
        message.success('Token disalin ke clipboard.');
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
                            <li>Klik <Text strong>Buat Token Tautan</Text> di bawah</li>
                            <li>
                                Kirim perintah <Text code>/link [token]</Text> ke bot
                            </li>
                            <li>Akun Anda akan terhubung secara otomatis</li>
                        </ol>

                        {linkToken && (
                            <Alert
                                type="success"
                                showIcon
                                message="Token berhasil dibuat!"
                                description={
                                    <Space direction="vertical" style={{ width: '100%' }}>
                                        <Text>Kirim perintah ini ke bot Telegram:</Text>
                                        <Text code copyable style={{ wordBreak: 'break-all' }}>
                                            /link {linkToken}
                                        </Text>
                                    </Space>
                                }
                            />
                        )}

                        <Button
                            type="primary"
                            icon={<LinkOutlined />}
                            onClick={() => router.post('/settings/telegram/generate-link-token')}
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
                            checked={telegram.settings?.daily_summary ?? true}
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
                            checked={telegram.settings?.budget_alerts ?? true}
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
                            checked={telegram.settings?.bill_reminders ?? true}
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
