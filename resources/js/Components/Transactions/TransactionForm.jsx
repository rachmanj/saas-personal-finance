import { useState, useEffect, useCallback, useRef } from 'react';
import { Modal, Form, Input, InputNumber, Select, DatePicker, Switch, App, Tabs, Space } from 'antd';
import { apiGet, apiPost, apiPut } from '../../utils/api';
import { fetchCategorySuggestion } from '../../utils/categorySuggestion';
import ConfidenceBadge from '../Shared/ConfidenceBadge';
import SplitTransaction from './SplitTransaction';
import MerchantAutocomplete from './MerchantAutocomplete';
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
 * Full transaction form in a Modal. Supports create and edit.
 *
 * @param {{
 *   open: boolean,
 *   onClose: () => void,
 *   onSuccess?: () => void,
 *   transaction?: object | null,
 * }} props
 */
export default function TransactionForm({ open, onClose, onSuccess, transaction = null }) {
    const [form] = Form.useForm();
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [accounts, setAccounts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [tags, setTags] = useState([]);
    const [transactionType, setTransactionType] = useState('expense');
    const [transactionAmount, setTransactionAmount] = useState(0);
    const [categoryConfidence, setCategoryConfidence] = useState(null);
    const [metadataLoading, setMetadataLoading] = useState(false);
    const suggestionRequestId = useRef(0);

    const isEdit = !!transaction;
    const description = Form.useWatch('description', form);

    useEffect(() => {
        if (open) {
            setMetadataLoading(true);
            Promise.all([
                apiGet('/api/accounts'),
                apiGet('/api/categories'),
                apiGet('/api/tags'),
            ]).then(([accRes, catRes, tagRes]) => {
                setAccounts(accRes.data || []);
                setCategories(catRes.data || []);
                setTags(tagRes.data || []);
            }).catch(() => {}).finally(() => setMetadataLoading(false));

            if (transaction) {
                form.setFieldsValue({
                    ...transaction,
                    transaction_date: transaction.transaction_date ? dayjs(transaction.transaction_date) : dayjs(),
                    tag_ids: transaction.tags?.map((t) => t.id) || [],
                    splits: transaction.splits || [],
                });
                setTransactionType(transaction.type || 'expense');
                setTransactionAmount(Number(transaction.amount) || 0);
                setCategoryConfidence(null);
            } else {
                form.resetFields();
                setTransactionType('expense');
                setTransactionAmount(0);
                setCategoryConfidence(null);
            }
        }
    }, [open, transaction, form]);

    const updateCategorySuggestion = useCallback(async (description) => {
        if (!description || description.length < 2) {
            setCategoryConfidence(null);
            return;
        }

        suggestionRequestId.current += 1;
        const requestId = suggestionRequestId.current;

        try {
            const suggestion = await fetchCategorySuggestion(description);
            if (requestId !== suggestionRequestId.current) {
                return;
            }

            if (suggestion.categoryId && !form.getFieldValue('category_id')) {
                form.setFieldsValue({ category_id: suggestion.categoryId });
            }
            setCategoryConfidence(suggestion.confidence);
        } catch {
            if (requestId === suggestionRequestId.current) {
                setCategoryConfidence(null);
            }
        }
    }, [form]);

    useEffect(() => {
        if (!open || !description) {
            return;
        }

        const timer = setTimeout(() => {
            updateCategorySuggestion(description);
        }, 400);

        return () => clearTimeout(timer);
    }, [description, open, updateCategorySuggestion]);

    const handleSubmit = async (values) => {
        setLoading(true);
        try {
            const payload = {
                ...values,
                transaction_date: values.transaction_date?.format('YYYY-MM-DD') || dayjs().format('YYYY-MM-DD'),
            };

            if (payload.type !== 'transfer') {
                delete payload.to_account_id;
            }

            if (isEdit) {
                await apiPut(`/api/transactions/${transaction.id}`, payload);
                message.success('Transaction updated!');
            } else {
                await apiPost('/api/transactions', payload);
                message.success('Transaction created!');
            }

            form.resetFields();
            onSuccess?.();
        } catch (err) {
            message.error(err.message || 'Failed to save transaction');
        } finally {
            setLoading(false);
        }
    };

    const handleTypeChange = (value) => {
        setTransactionType(value);
    };

    const handleAmountChange = (value) => {
        setTransactionAmount(value || 0);
    };

    const handleMerchantSelect = (merchant) => {
        form.setFieldsValue({ description: merchant });
    };

    return (
        <Modal
            title={isEdit ? 'Edit Transaction' : 'New Transaction'}
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            confirmLoading={loading}
            destroyOnClose
            width={640}
            loading={metadataLoading}
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                initialValues={{
                    type: 'expense',
                    currency: 'USD',
                    transaction_date: dayjs(),
                    source: 'manual',
                    is_reconciled: false,
                    splits: [],
                    tag_ids: [],
                }}
            >
                <Form.Item name="type" label="Type" rules={[{ required: true }]}>
                    <Select options={TRANSACTION_TYPES} onChange={handleTypeChange} />
                </Form.Item>

                <Form.Item name="amount" label="Amount" rules={[{ required: true, message: 'Amount is required' }]}>
                    <InputNumber
                        style={{ width: '100%' }}
                        min={0.01}
                        precision={2}
                        placeholder="0.00"
                        onChange={handleAmountChange}
                    />
                </Form.Item>

                <Form.Item name="currency" label="Currency" rules={[{ required: true }]}>
                    <Select options={CURRENCIES} />
                </Form.Item>

                <Form.Item name="description" label="Description">
                    <MerchantAutocomplete onSelect={handleMerchantSelect} />
                </Form.Item>

                <Form.Item name="notes" label="Notes">
                    <Input.TextArea rows={2} />
                </Form.Item>

                <Form.Item name="account_id" label="Account" rules={[{ required: true, message: 'Select an account' }]}>
                    <Select
                        showSearch
                        filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                        options={accounts.map((a) => ({ value: a.id, label: a.name }))}
                    />
                </Form.Item>

                {transactionType === 'transfer' && (
                    <Form.Item
                        name="to_account_id"
                        label="To Account"
                        rules={[{ required: true, message: 'Select destination account' }]}
                    >
                        <Select
                            showSearch
                            filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                            options={accounts.map((a) => ({ value: a.id, label: a.name }))}
                        />
                    </Form.Item>
                )}

                <Form.Item
                    name="category_id"
                    label={
                        <Space>
                            Category
                            <ConfidenceBadge confidence={categoryConfidence} />
                        </Space>
                    }
                >
                    <Select
                        allowClear
                        showSearch
                        filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                        options={categories.map((c) => ({ value: c.id, label: c.name }))}
                    />
                </Form.Item>

                <Form.Item name="tag_ids" label="Tags">
                    <Select
                        mode="multiple"
                        allowClear
                        filterOption={(input, option) => (option?.label ?? '').toLowerCase().includes(input.toLowerCase())}
                        options={tags.map((t) => ({ value: t.id, label: t.name }))}
                        placeholder="Select tags"
                    />
                </Form.Item>

                <Form.Item name="transaction_date" label="Date" rules={[{ required: true }]}>
                    <DatePicker style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item name="is_reconciled" label="Reconciled" valuePropName="checked">
                    <Switch />
                </Form.Item>

                <Tabs
                    items={[
                        {
                            key: 'splits',
                            label: 'Splits',
                            children: <SplitTransaction transactionAmount={transactionAmount} />,
                        },
                    ]}
                />
            </Form>
        </Modal>
    );
}
