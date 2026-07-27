import { useState, useCallback, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { App } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TransactionTable from '../../Components/Transactions/TransactionTable';
import TransactionForm from '../../Components/Transactions/TransactionForm';
import TransactionDetail from '../../Components/Transactions/TransactionDetail';
import QuickAddFAB from '../../Components/Transactions/QuickAddFAB';
import BulkActions from '../../Components/Transactions/BulkActions';
import { apiGet } from '../../utils/api';

export default function Index() {
    const [formOpen, setFormOpen] = useState(false);
    const [detailOpen, setDetailOpen] = useState(false);
    const [editingTransaction, setEditingTransaction] = useState(null);
    const [viewingTransaction, setViewingTransaction] = useState(null);
    const [selectedRowKeys, setSelectedRowKeys] = useState([]);
    const [categories, setCategories] = useState([]);
    const [refreshKey, setRefreshKey] = useState(0);

    useEffect(() => {
        apiGet('/api/categories').then((res) => {
            setCategories((res.data || []).map((c) => ({ value: c.id, label: c.name })));
        }).catch(() => {});
    }, []);

    const handleAdd = useCallback(() => {
        setEditingTransaction(null);
        setFormOpen(true);
    }, []);

    const handleEdit = useCallback((txn) => {
        setEditingTransaction(txn);
        setFormOpen(true);
    }, []);

    const handleView = useCallback((txn) => {
        setViewingTransaction(txn);
        setDetailOpen(true);
    }, []);

    const handleFormSuccess = useCallback(() => {
        setFormOpen(false);
        setEditingTransaction(null);
        setRefreshKey((k) => k + 1);
    }, []);

    const handleBulkDone = useCallback(() => {
        setSelectedRowKeys([]);
        setRefreshKey((k) => k + 1);
    }, []);

    const handleTableRefresh = useCallback(() => {
        setRefreshKey((k) => k + 1);
    }, []);

    return (
        <App>
            <AuthenticatedLayout title="Transactions">
                <Head title="Transactions" />

                <BulkActions
                    selectedRowKeys={selectedRowKeys}
                    onDone={handleBulkDone}
                    categories={categories}
                />

                <TransactionTable
                    key={refreshKey}
                    onEdit={handleEdit}
                    onView={handleView}
                    onAdd={handleAdd}
                    selectedRowKeys={selectedRowKeys}
                    onSelectChange={setSelectedRowKeys}
                />

                <TransactionForm
                    open={formOpen}
                    onClose={() => {
                        setFormOpen(false);
                        setEditingTransaction(null);
                    }}
                    onSuccess={handleFormSuccess}
                    transaction={editingTransaction}
                />

                <TransactionDetail
                    open={detailOpen}
                    onClose={() => {
                        setDetailOpen(false);
                        setViewingTransaction(null);
                    }}
                    transaction={viewingTransaction}
                    onEdit={(txn) => {
                        setDetailOpen(false);
                        handleEdit(txn);
                    }}
                />

                <QuickAddFAB onSuccess={handleTableRefresh} />
            </AuthenticatedLayout>
        </App>
    );
}
