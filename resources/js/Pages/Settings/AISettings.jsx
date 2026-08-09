import { useCallback, useEffect, useState } from 'react';
import { ProTable } from '@ant-design/pro-table';
import {
    App,
    Button,
    Card,
    Col,
    Form,
    Input,
    InputNumber,
    Modal,
    Row,
    Select,
    Space,
    Statistic,
    Tag,
} from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined, ReloadOutlined } from '@ant-design/icons';
import { apiDelete, apiGet, apiPost, apiPut } from '../../utils/api';
import ConfidenceBadge from '../../Components/Shared/ConfidenceBadge';
import EmptyState from '../../Components/Shared/EmptyState';

const sourceColors = {
    manual: 'blue',
    ai_trained: 'purple',
};

export default function AISettings() {
    const { message } = App.useApp();
    const [rules, setRules] = useState([]);
    const [categories, setCategories] = useState([]);
    const [accuracy, setAccuracy] = useState(null);
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRule, setEditingRule] = useState(null);
    const [saving, setSaving] = useState(false);
    const [form] = Form.useForm();

    const loadData = useCallback(async () => {
        setLoading(true);
        try {
            const [rulesRes, categoriesRes, accuracyRes] = await Promise.all([
                apiGet('/api/ai/categorization-rules'),
                apiGet('/api/categories'),
                apiGet('/api/ai/categorization-accuracy'),
            ]);
            setRules(rulesRes.data || []);
            setCategories(categoriesRes.data || []);
            setAccuracy(accuracyRes.data || null);
        } catch (err) {
            message.error(err.message || 'Gagal memuat pengaturan AI');
        } finally {
            setLoading(false);
        }
    }, [message]);

    useEffect(() => {
        loadData();
    }, [loadData]);

    const openCreate = () => {
        setEditingRule(null);
        form.resetFields();
        form.setFieldsValue({ confidence: 1 });
        setModalOpen(true);
    };

    const openEdit = (rule) => {
        setEditingRule(rule);
        form.setFieldsValue({
            pattern: rule.pattern,
            category_id: rule.category_id,
            confidence: rule.confidence,
        });
        setModalOpen(true);
    };

    const handleSave = async () => {
        try {
            const values = await form.validateFields();
            setSaving(true);

            if (editingRule) {
                await apiPut(`/api/ai/categorization-rules/${editingRule.id}`, values);
                message.success('Aturan diperbarui');
            } else {
                await apiPost('/api/ai/categorization-rules', values);
                message.success('Aturan dibuat');
            }

            setModalOpen(false);
            loadData();
        } catch (err) {
            if (err.errors) {
                message.error('Perbaiki error validasi');
            } else if (err.message) {
                message.error(err.message);
            }
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = (rule) => {
        Modal.confirm({
            title: 'Hapus aturan kategorisasi?',
            content: `Remove the rule matching "${rule.pattern}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/ai/categorization-rules/${rule.id}`);
                    message.success('Aturan dihapus');
                    loadData();
                } catch (err) {
                    message.error(err.message || 'Gagal menghapus aturan');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Pola',
            dataIndex: 'pattern',
            key: 'pattern',
            ellipsis: true,
        },
        {
            title: 'Kategori',
            dataIndex: ['category', 'name'],
            key: 'category',
            render: (_, record) => record.category?.name || '—',
        },
        {
            title: 'Keyakinan',
            dataIndex: 'confidence',
            key: 'confidence',
            width: 140,
            render: (value) => <ConfidenceBadge confidence={value} />,
        },
        {
            title: 'Sumber',
            dataIndex: 'source',
            key: 'source',
            width: 120,
            render: (source) => (
                <Tag color={sourceColors[source] || 'default'}>
                    {(source || 'unknown').replace('_', ' ')}
                </Tag>
            ),
        },
        {
            title: 'Aksi',
            key: 'actions',
            width: 100,
            render: (_, record) => (
                <Space>
                    <Button
                        type="text"
                        size="small"
                        icon={<EditOutlined />}
                        aria-label={`Edit rule ${record.pattern}`}
                        onClick={() => openEdit(record)}
                    />
                    <Button
                        type="text"
                        size="small"
                        danger
                        icon={<DeleteOutlined />}
                        aria-label={`Delete rule ${record.pattern}`}
                        onClick={() => handleDelete(record)}
                    />
                </Space>
            ),
        },
    ];

    const accuracyPercent = accuracy?.accuracy_rate != null
        ? Math.round(accuracy.accuracy_rate * 100)
        : 0;

    return (
        <div>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} sm={8}>
                    <Card loading={loading}>
                        <Statistic
                            title="Tingkat Akurasi"
                            value={accuracyPercent}
                            suffix="%"
                            valueStyle={{ color: accuracyPercent >= 80 ? '#52c41a' : accuracyPercent >= 50 ? '#faad14' : '#ff4d4f' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={8}>
                    <Card loading={loading}>
                        <Statistic title="Total Prediksi" value={accuracy?.total_predictions ?? 0} />
                    </Card>
                </Col>
                <Col xs={24} sm={8}>
                    <Card loading={loading}>
                        <Statistic title="Prediksi Benar" value={accuracy?.correct_predictions ?? 0} />
                    </Card>
                </Col>
            </Row>

            <ProTable
                columns={columns}
                dataSource={rules}
                rowKey="id"
                loading={loading}
                search={false}
                options={false}
                pagination={{ pageSize: 10 }}
                locale={{
                    emptyText: (
                        <EmptyState
                            description="Belum ada aturan kategorisasi"
                            action={
                                <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                                    Add Rule
                                </Button>
                            }
                        />
                    ),
                }}
                toolBarRender={() => [
                    <Button key="refresh" icon={<ReloadOutlined />} onClick={loadData} aria-label="Refresh rules">
                        Refresh
                    </Button>,
                    <Button key="create" type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                        Add Rule
                    </Button>,
                ]}
            />

            <Modal
                title={editingRule ? 'Edit Aturan Kategorisasi' : 'Aturan Kategorisasi Baru'}
                open={modalOpen}
                onOk={handleSave}
                onCancel={() => setModalOpen(false)}
                confirmLoading={saving}
                destroyOnClose
            >
                <Form form={form} layout="vertical">
                    <Form.Item
                        name="pattern"
                        label="Pola"
                        rules={[{ required: true, message: 'Masukkan kata kunci atau pola' }]}
                    >
                        <Input placeholder="contoh: netflix, starbucks" />
                    </Form.Item>
                    <Form.Item
                        name="category_id"
                        label="Kategori"
                        rules={[{ required: true, message: 'Pilih kategori' }]}
                    >
                        <Select
                            showSearch
                            filterOption={(input, option) =>
                                (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                            }
                            options={categories.map((c) => ({ value: c.id, label: c.name }))}
                            placeholder="Pilih kategori"
                        />
                    </Form.Item>
                    <Form.Item name="confidence" label="Keyakinan" rules={[{ required: true }]}>
                        <InputNumber min={0} max={1} step={0.05} style={{ width: '100%' }} />
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
