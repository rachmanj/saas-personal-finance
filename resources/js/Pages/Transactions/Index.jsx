import { useState, useCallback, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { App, Button, Space } from 'antd';
import { RobotOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import TransactionTable from '../../Components/Transactions/TransactionTable';
import TransactionForm from '../../Components/Transactions/TransactionForm';
import TransactionDetail from '../../Components/Transactions/TransactionDetail';
import QuickAddFAB from '../../Components/Transactions/QuickAddFAB';
import BulkActions from '../../Components/Transactions/BulkActions';
import { apiGet, apiPost } from '../../utils/api';

export default function Index() {
    const [formOpen, setFormOpen] = useState(false);
    const [detailOpen, setDetailOpen] = useState(false);
    const [editingTransaction, setEditingTransaction] = useState(null);
    const [viewingTransaction, setViewingTransaction] = useState(null);
    const [selectedRowKeys, setSelectedRowKeys] = useState([]);
    const [categories, setCategories] = useState([]);
    const [refreshKey, setRefreshKey] = useState(0);
    const [batchLoading, setBatchLoading] = useState(false);

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
            <TransactionsContent
                formOpen={formOpen}
                setFormOpen={setFormOpen}
                detailOpen={detailOpen}
                setDetailOpen={setDetailOpen}
                editingTransaction={editingTransaction}
                setEditingTransaction={setEditingTransaction}
                viewingTransaction={viewingTransaction}
                setViewingTransaction={setViewingTransaction}
                selectedRowKeys={selectedRowKeys}
                setSelectedRowKeys={setSelectedRowKeys}
                categories={categories}
                refreshKey={refreshKey}
                batchLoading={batchLoading}
                setBatchLoading={setBatchLoading}
                handleAdd={handleAdd}
                handleEdit={handleEdit}
                handleView={handleView}
                handleFormSuccess={handleFormSuccess}
                handleBulkDone={handleBulkDone}
                handleTableRefresh={handleTableRefresh}
            />
        </App>
    );
}

function TransactionsContent({
    formOpen,
    setFormOpen,
    detailOpen,
    setDetailOpen,
    editingTransaction,
    setEditingTransaction,
    viewingTransaction,
    setViewingTransaction,
    selectedRowKeys,
    setSelectedRowKeys,
    categories,
    refreshKey,
    batchLoading,
    setBatchLoading,
    handleAdd,
    handleEdit,
    handleView,
    handleFormSuccess,
    handleBulkDone,
    handleTableRefresh,
}) {
    const { message } = App.useApp();

    const handleBatchCategorize = async () => {
        setBatchLoading(true);
        try {
            const res = await apiPost('/api/ai/categorize/batch', {});
            const count = res.data?.dispatched ?? 0;
            message.success(res.message || `Queued ${count} uncategorized transaction${count === 1 ? '' : 's'}`);
            handleTableRefresh();
        } catch (err) {
            message.error(err.message || 'Failed to queue batch categorization');
        } finally {
            setBatchLoading(false);
        }
    };

    return (
        <AuthenticatedLayout title="Transaksi">
        <Head title="Transaksi" />

            <Space style={{ marginBottom: 16 }} wrap>
                <Button
                    icon={<RobotOutlined />}
                    loading={batchLoading}
                    onClick={handleBatchCategorize}
                    aria-label="Kategorikan batch transaksi yang belum dikategorikan"
                >
                    Batch categorize uncategorized
                </Button>
            </Space>

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
    );
}
