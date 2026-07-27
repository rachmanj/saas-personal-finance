import { Button, Space, Select, App } from 'antd';
import { DeleteOutlined, TagsOutlined, CheckOutlined } from '@ant-design/icons';
import { apiPost } from '../../utils/api';

/**
 * @param {{
 *   selectedRowKeys: number[],
 *   onDone: () => void,
 *   categories?: {value: number, label: string}[],
 * }} props
 */
export default function BulkActions({ selectedRowKeys, onDone, categories = [] }) {
    const { message, modal } = App.useApp();
    const disabled = selectedRowKeys.length === 0;

    const handleBulkDelete = () => {
        modal.confirm({
            title: `Delete ${selectedRowKeys.length} transaction(s)?`,
            content: 'This action cannot be undone.',
            okText: 'Delete',
            okType: 'danger',
            cancelText: 'Cancel',
            onOk: async () => {
                try {
                    await apiPost('/api/transactions/bulk', {
                        action: 'delete',
                        ids: selectedRowKeys,
                    });
                    message.success(`Deleted ${selectedRowKeys.length} transaction(s)`);
                    onDone();
                } catch (err) {
                    message.error(err.message || 'Bulk delete failed');
                }
            },
        });
    };

    const handleBulkCategorize = (categoryId) => {
        modal.confirm({
            title: `Categorize ${selectedRowKeys.length} transaction(s)?`,
            content: 'Apply the selected category to all checked transactions.',
            onOk: async () => {
                try {
                    await apiPost('/api/transactions/bulk', {
                        action: 'categorize',
                        ids: selectedRowKeys,
                        payload: { category_id: categoryId },
                    });
                    message.success(`Categorized ${selectedRowKeys.length} transaction(s)`);
                    onDone();
                } catch (err) {
                    message.error(err.message || 'Bulk categorize failed');
                }
            },
        });
    };

    const handleBulkReconcile = () => {
        modal.confirm({
            title: `Reconcile ${selectedRowKeys.length} transaction(s)?`,
            content: 'Mark all checked transactions as reconciled.',
            onOk: async () => {
                try {
                    await apiPost('/api/transactions/bulk', {
                        action: 'update',
                        ids: selectedRowKeys,
                        payload: { is_reconciled: true },
                    });
                    message.success(`Reconciled ${selectedRowKeys.length} transaction(s)`);
                    onDone();
                } catch (err) {
                    message.error(err.message || 'Bulk reconcile failed');
                }
            },
        });
    };

    return (
        <Space style={{ marginBottom: 16 }}>
            <Select
                placeholder="Categorize..."
                style={{ width: 200 }}
                disabled={disabled}
                options={categories}
                onChange={(val) => handleBulkCategorize(val)}
                allowClear={false}
                value={undefined}
            />
            <Button
                icon={<CheckOutlined />}
                disabled={disabled}
                onClick={handleBulkReconcile}
            >
                Reconcile
            </Button>
            <Button
                danger
                icon={<DeleteOutlined />}
                disabled={disabled}
                onClick={handleBulkDelete}
            >
                Delete ({selectedRowKeys.length})
            </Button>
        </Space>
    );
}
