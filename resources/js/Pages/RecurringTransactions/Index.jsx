import { useState, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import { App, Row, Col, Card } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import RecurringList from '../../Components/Recurring/RecurringList';
import RecurringForm from '../../Components/Recurring/RecurringForm';
import UpcomingPreview from '../../Components/Recurring/UpcomingPreview';

export default function Index() {
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
            <AuthenticatedLayout title="Recurring Transactions">
                <Head title="Recurring Transactions" />

                <Row gutter={[16, 16]}>
                    <Col xs={24} lg={16}>
                        <RecurringList
                            refreshKey={refreshKey}
                            onEdit={handleEdit}
                            onAdd={handleAdd}
                        />
                    </Col>
                    <Col xs={24} lg={8}>
                        <Card title="Upcoming (30 days)">
                            <UpcomingPreview key={refreshKey} />
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
