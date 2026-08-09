import { useState, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { App, Row, Col, Card } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import RecurringList from '../../Components/Recurring/RecurringList';
import RecurringForm from '../../Components/Recurring/RecurringForm';
import UpcomingPreview from '../../Components/Recurring/UpcomingPreview';

export default function Index() {
    const { props } = usePage();
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedRecurring, setSelectedRecurring] = useState(null);
    const [refreshKey, setRefreshKey] = useState(0);

    const handleAdd = useCallback(() => {
        setSelectedRecurring(null);
        setModalOpen(true);
    }, []);

    const handleEdit = useCallback((item) => {
        setSelectedRecurring(item);
        setModalOpen(true);
    }, []);

    const handleFormSuccess = useCallback(() => {
        setModalOpen(false);
        setSelectedRecurring(null);
        setRefreshKey((k) => k + 1);
    }, []);

    return (
        <App>
            <AuthenticatedLayout title="Transaksi Berulang">
                <Head title="Transaksi Berulang" />

                <Row gutter={[16, 16]}>
                    <Col xs={24} lg={16}>
                        <RecurringList
                            recurring={props.recurring}
                            refreshKey={refreshKey}
                            onEdit={handleEdit}
                            onAdd={handleAdd}
                        />
                    </Col>
                    <Col xs={24} lg={8}>
                        <Card title="Mendatang (30 hari)">
                            <UpcomingPreview upcoming={props.upcoming} key={refreshKey} />
                        </Card>
                    </Col>
                </Row>

                <RecurringForm
                    open={modalOpen}
                    onClose={() => {
                        setModalOpen(false);
                        setSelectedRecurring(null);
                    }}
                    onSuccess={handleFormSuccess}
                    recurring={selectedRecurring}
                />
            </AuthenticatedLayout>
        </App>
    );
}
