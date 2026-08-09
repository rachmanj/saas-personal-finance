import { useEffect, useState } from 'react';
import { ProTable } from '@ant-design/pro-table';
import { Button, Modal, Form, message, Space, Tag as AntTag } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { apiDelete, apiGet, apiPost, apiPut } from '../utils/api';
import TagForm from './TagForm';

export default function TagList({ tags: tagsProp }) {
    const [tags, setTags] = useState(tagsProp || []);
    const [loading, setLoading] = useState(!tagsProp);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingTag, setEditingTag] = useState(null);
    const [form] = Form.useForm();

    const loadTags = async () => {
        setLoading(true);
        try {
            const response = await apiGet('/api/tags');
            setTags(response.data || []);
        } catch {
            message.error('Gagal memuat tag');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (!tagsProp) loadTags();
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
                message.success('Tag diperbarui');
            } else {
                await apiPost('/api/tags', values);
                message.success('Tag dibuat');
            }
            setModalOpen(false);
            loadTags();
        } catch (error) {
            if (error.errors) {
                message.error('Validasi gagal');
            }
        }
    };

    const handleDelete = (record) => {
        Modal.confirm({
            title: 'Hapus tag?',
            content: `Are you sure you want to delete "${record.name}"?`,
            okType: 'danger',
            onOk: async () => {
                try {
                    await apiDelete(`/api/tags/${record.id}`);
                    message.success('Tag dihapus');
                    loadTags();
                } catch {
                    message.error('Gagal menghapus tag');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Nama',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <AntTag color={record.color || 'default'}>{name}</AntTag>
            ),
        },
        { title: 'Warna', dataIndex: 'color', key: 'color' },
        {
            title: 'Aksi',
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
                title={editingTag ? 'Edit Tag' : 'Buat Tag'}
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
