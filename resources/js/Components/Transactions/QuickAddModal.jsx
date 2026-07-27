import { useState, useEffect } from 'react';
import { Modal, Form, Input, InputNumber, Select, DatePicker, App } from 'antd';
import { apiGet, apiPost } from '../../utils/api';
import dayjs from 'dayjs';

const TRANSACTION_TYPES = [
    { value: 'income', label: 'Income' },
    { value: 'expense', label: 'Expense' },
    { value: 'transfer', label: 'Transfer' },
];

const CURRENCIES = [
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
    { value: 'GBP', label: 'GBP' },
    { value: 'IDR', label: 'IDR' },
];

/**
 * @param {{ open: boolean, onClose: () => void, onSuccess?: () => void }} props
 */
export default function QuickAddModal({ open, onClose, onSuccess }) {
    const [form] = Form.useForm();
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [accounts, setAccounts] = useState([]);
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        if (open) {
            apiGet('/api/accounts').then((res) => setAccounts(res.data || [])).catch(() => {});
            apiGet('/api/categories').then((res) => setCategories(res.data || [])).catch(() => {});
        }
    }, [open]);

    const handleSubmit = async (values) => {
        setLoading(true);
        try {
            await apiPost('/api/transactions', {
                ...values,
                transaction_date: values.transaction_date?.format('YYYY-MM-DD') || dayjs().format('YYYY-MM-DD'),
            });
            message.success('Transaction added!');
            form.resetFields();
            onSuccess?.();
        } catch (err) {
            message.error(err.message || 'Failed to add transaction');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title="Quick Add Transaction"
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            confirmLoading={loading}
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                initialValues={{
                    type: 'expense',
                    transaction_date: dayjs(),
                }}
            >
                <Form.Item name="type" label="Type" rules={[{ required: true }]}>
                    <Select options={TRANSACTION_TYPES} />
                </Form.Item>

                <Form.Item name="amount" label="Amount" rules={[{ required: true, message: 'Amount is required' }]}>
                    <InputNumber style={{ width: '100%' }} min={0.01} precision={2} placeholder="0.00" />
                </Form.Item>

                <Form.Item name="description" label="Description" rules={[{ required: true, message: 'Description is required' }]}>
                    <Input placeholder="What was this for?" />
                </Form.Item>

                <Form.Item name="account_id" label="Account" rules={[{ required: true, message: 'Select an account' }]}>
                    <Select
                        showSearch
                        filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                        options={accounts.map((a) => ({ value: a.id, label: a.name }))}
                    />
                </Form.Item>

                <Form.Item name="category_id" label="Category">
                    <Select
                        allowClear
                        showSearch
                        filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                        options={categories.map((c) => ({ value: c.id, label: c.name }))}
                    />
                </Form.Item>

                <Form.Item name="currency" label="Currency" rules={[{ required: true }]}>
                    <Select options={CURRENCIES} />
                </Form.Item>

                <Form.Item name="transaction_date" label="Date">
                    <DatePicker style={{ width: '100%' }} />
                </Form.Item>
            </Form>
        </Modal>
    );
}
