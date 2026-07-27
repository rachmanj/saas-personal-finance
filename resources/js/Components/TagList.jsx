import { useEffect, useState } from 'react';
import { ProTable } from '@ant-design/pro-table';
import { Button, Modal, Form, message, Space, Tag as AntTag } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { apiDelete, apiGet, apiPost, apiPut } from '../utils/api';
import TagForm from './TagForm';

export default function TagList() {
    const [tags, setTags] = useState([]);
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingTag, setEditingTag] = useState(null);
    const [form] = Form.useForm();

    const loadTags = async () => {
        setLoading(true);
        try {
            const response = await apiGet('/api/tags');
            setTags(response.data || []);
        } catch {
            message.error('Failed to load tags');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadTags();
    }, []);

    const openCreate = () => {
        setEditingTag(null);
        form.resetFields();
        setModalOpen(true);
    };

    const openEdit = (tag) => {
        setEditingTag(tag);
        form.setFieldsValue(tag);
        setModalOpen(true);
    };

    const handleSave = async () => {
        try {
            const values = await form.validateFields();
            if (editingTag) {
                await apiPut(`/api/tags/${editingTag.id}`, values);
                message.success('Tag updated');
            } else {
                await apiPost('/api/tags', values);
                message.success('Tag created');
            }
            setModalOpen(false);
            loadTags();
        } catch (error) {
            if (error.errors) {
                message.error('Validation failed');
            }
        }
    };

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Delete tag?',
            content: `Are you sure you want to delete "${record.name}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/tags/${record.id}`);
                    message.success('Tag deleted');
                    loadTags();
                } catch {
                    message.error('Failed to delete tag');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Name',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <AntTag color={record.color || 'default'}>{name}</AntTag>
            ),
        },
        { title: 'Color', dataIndex: 'color', key: 'color' },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button icon={<EditOutlined />} size="small" onClick={() => openEdit(record)} />
                    <Button icon={<DeleteOutlined />} size="small" danger onClick={() => handleDelete(record)} />
                </Space>
            ),
        },
    ];

    return (
        <>
            <ProTable
                columns={columns}
                dataSource={tags}
                rowKey="id"
                loading={loading}
                search={false}
                options={false}
                pagination={{ pageSize: 10 }}
                toolBarRender={() => [
                    <Button key="create" type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                        Create Tag
                    </Button>,
                ]}
            />

            <Modal
                title={editingTag ? 'Edit Tag' : 'Create Tag'}
                open={modalOpen}
                onOk={handleSave}
                onCancel={() => setModalOpen(false)}
                destroyOnClose
            >
                <TagForm form={form} />
            </Modal>
        </>
    );
}
