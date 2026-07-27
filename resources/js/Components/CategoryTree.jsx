import { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { Table, Button, Space, Tag, message } from 'antd';
import { ArrowUpOutlined, ArrowDownOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { apiDelete, apiPut } from '../utils/api';

/**
 * @param {{ categories: Array, onRefresh: () => void }} props
 */
export default function CategoryTree({ categories, onRefresh }) {
    const treeData = useMemo(() => {
        const roots = categories.filter((c) => !c.parent_id);
        return roots.map((parent) => ({
            ...parent,
            children: categories.filter((c) => c.parent_id === parent.id),
        }));
    }, [categories]);

    const handleReorder = async (category, direction) => {
        const siblings = categories
            .filter((c) => c.parent_id === category.parent_id)
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

        const index = siblings.findIndex((c) => c.id === category.id);
        const swapIndex = direction === 'up' ? index - 1 : index + 1;

        if (swapIndex < 0 || swapIndex >= siblings.length) {
            return;
        }

        const reordered = [...siblings];
        [reordered[index], reordered[swapIndex]] = [reordered[swapIndex], reordered[index]];

        try {
            await apiPut('/api/categories/reorder', {
                ordered_ids: reordered.map((c) => c.id),
            });
            message.success('Category reordered');
            onRefresh();
        } catch {
            message.error('Failed to reorder');
        }
    };

    const handleDelete = (record) => {
        apiDelete(`/api/categories/${record.id}`)
            .then(() => {
                message.success('Category deleted');
                onRefresh();
            })
            .catch(() => message.error('Failed to delete category'));
    };

    const columns = [
        { title: 'Name', dataIndex: 'name', key: 'name' },
        {
            title: 'Type',
            dataIndex: 'type',
            key: 'type',
            render: (type) => <Tag color={type === 'income' ? 'green' : 'red'}>{type}</Tag>,
        },
        {
            title: 'System',
            dataIndex: 'is_system',
            key: 'is_system',
            render: (val) => (val ? 'Yes' : 'No'),
        },
        { title: 'Sort Order', dataIndex: 'sort_order', key: 'sort_order' },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button size="small" icon={<ArrowUpOutlined />} onClick={() => handleReorder(record, 'up')} />
                    <Button size="small" icon={<ArrowDownOutlined />} onClick={() => handleReorder(record, 'down')} />
                    <Link href={`/categories/${record.id}/edit`}>
                        <Button size="small" icon={<EditOutlined />} />
                    </Link>
                    {!record.is_system && (
                        <Button size="small" icon={<DeleteOutlined />} danger onClick={() => handleDelete(record)} />
                    )}
                </Space>
            ),
        },
    ];

    return (
        <Table
            columns={columns}
            dataSource={treeData}
            rowKey="id"
            pagination={false}
            expandable={{
                defaultExpandAllRows: true,
                childrenColumnName: 'children',
            }}
        />
    );
}
