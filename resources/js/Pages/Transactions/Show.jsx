import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { App, Spin, Result, Button } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TransactionDetail from '../../Components/Transactions/TransactionDetail';
import TransactionForm from '../../Components/Transactions/TransactionForm';
import { apiGet } from '../../utils/api';

export default function Show({ transaction: initialTransaction }) {
    const { url } = usePage();
    const transactionId = url.split('/').pop();
    const [transaction, setTransaction] = useState(initialTransaction || null);
    const [loading, setLoading] = useState(!initialTransaction);
    const [formOpen, setFormOpen] = useState(false);

    useEffect(() => {
        if (!initialTransaction && transactionId) {
            setLoading(true);
            apiGet(`/api/transactions/${transactionId}`)
                .then((res) => setTransaction(res.data))
                .catch(() => setTransaction(null))
                .finally(() => setLoading(false));
        }
    }, [transactionId]);

    const handleFormSuccess = () => {
        setFormOpen(false);
        if (transactionId) {
            apiGet(`/api/transactions/${transactionId}`)
                .then((res) => setTransaction(res.data))
                .catch(() => {});
        }
    };

    if (loading) {
        return (
            <AuthenticatedLayout title="Transaction">
                <div style={{ textAlign: 'center', padding: 48 }}>
                    <Spin size="large" />
                </div>
            </AuthenticatedLayout>
        );
    }

    if (!transaction) {
        return (
            <AuthenticatedLayout title="Transaction">
                <Result
                    status="404"
                    title="Transaction not found"
                    subTitle="The transaction you're looking for doesn't exist or you don't have access."
                    extra={<Button href="/transactions" type="primary" icon={<ArrowLeftOutlined />}>Back to Transactions</Button>}
                />
            </AuthenticatedLayout>
        );
    }

    return (
        <App>
            <AuthenticatedLayout title="Transaction Detail">
                <Head title={`Transaction - ${transaction.description || 'Detail'}`} />

                <TransactionDetail
                    open={true}
                    onClose={() => window.history.back()}
                    transaction={transaction}
                    onEdit={() => setFormOpen(true)}
                />

                <TransactionForm
                    open={formOpen}
                    onClose={() => setFormOpen(false)}
                    onSuccess={handleFormSuccess}
                    transaction={transaction}
                />
            </AuthenticatedLayout>
        </App>
    );
}
