import { useState, useCallback, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { App, Row, Col, Card, List, Tag } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import ReminderList from '../../Components/Reminders/ReminderList';
import ReminderForm from '../../Components/Reminders/ReminderForm';
import NotificationPermission from '../../Components/Reminders/NotificationPermission';
import OfflineIndicator from '../../Components/PWA/OfflineIndicator';
import { apiGet } from '../../utils/api';
import dayjs from 'dayjs';

export default function Index() {
    const { props: pageProps } = usePage();
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedReminder, setSelectedReminder] = useState(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const [dueSoon, setDueSoon] = useState(pageProps.dueSoon || []);

    const loadDueSoon = useCallback(async () => {
        try {
            const res = await apiGet('/api/bill-reminders/due-soon');
            setDueSoon(res.data || []);
        } catch {
            setDueSoon([]);
        }
    }, []);

    useEffect(() => {
        if (!pageProps.dueSoon) loadDueSoon();
    }, [refreshKey]);

    const handleAdd = useCallback(() => {
        setSelectedReminder(null);
        setModalOpen(true);
    }, []);

    const handleEdit = useCallback((item) => {
        setSelectedReminder(item);
        setModalOpen(true);
    }, []);

    const handleFormSuccess = useCallback(() => {
        setModalOpen(false);
        setSelectedReminder(null);
        setRefreshKey((k) => k + 1);
    }, []);

    return (
        <App>
            <AuthenticatedLayout title="Pengingat Tagihan">
                <Head title="Pengingat Tagihan" />
                <OfflineIndicator />

                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                    <Col>
                        <NotificationPermission />
                    </Col>
                </Row>

                <Row gutter={[16, 16]}>
                    <Col xs={24} lg={16}>
                        <ReminderList
                            reminders={pageProps.reminders}
                            refreshKey={refreshKey}
                            onEdit={handleEdit}
                            onAdd={handleAdd}
                        />
                    </Col>
                    <Col xs={24} lg={8}>
                        <Card title="Jatuh Tempo (7 hari)">
                            <List
                                dataSource={dueSoon}
                                locale={{ emptyText: 'Tidak ada tagihan jatuh tempo' }}
                                renderItem={(item) => (
                                    <List.Item>
                                        <List.Item.Meta
                                            title={item.name}
                                            description={dayjs(item.due_date).format('MMM D, YYYY')}
                                        />
                                        <Tag color="orange">
                                            {item.currency} {Number(item.amount).toFixed(2)}
                                        </Tag>
                                    </List.Item>
                                )}
                            />
                        </Card>
                    </Col>
                </Row>

                <ReminderForm
                    open={modalOpen}
                    onClose={() => {
                        setModalOpen(false);
                        setSelectedReminder(null);
                    }}
                    onSuccess={handleFormSuccess}
                    reminder={selectedReminder}
                />
            </AuthenticatedLayout>
        </App>
    );
}
