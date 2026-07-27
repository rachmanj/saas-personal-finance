import { useState, useCallback } from 'react';
import ProTable from '@ant-design/pro-table';
import { Button, Space, Tag, Tooltip } from 'antd';
import {
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    EyeOutlined,
    SwapOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
} from '@ant-design/icons';
import { apiGet, apiDelete } from '../../utils/api';
import dayjs from 'dayjs';

const typeColorMap = {
    income: 'green',
    expense: 'red',
    transfer: 'blue',
};

const typeIconMap = {
    income: <ArrowUpOutlined style={{ color: '#52c41a' }} />,
    expense: <ArrowDownOutlined style={{ color: '#ff4d4f' }} />,
    transfer: <SwapOutlined style={{ color: '#1677ff' }} />,
};

/**
 * @param {{
 *   onEdit?: (txn: object) => void,
 *   onView?: (txn: object) => void,
 *   onAdd?: () => void,
 *   selectedRowKeys?: number[],
 *   onSelectChange?: (keys: number[]) => void,
 * }} props
 */
export default function TransactionTable({ onEdit, onView, onAdd, selectedRowKeys, onSelectChange }) {
    const [actionRef, setActionRef] = useState(null);

    const request = useCallback(async (params, sort, filter) => {
        const query = new URLSearchParams();

        query.set('current', params.current || 1);
        query.set('pageSize', params.pageSize || 20);

        if (sort && sort.transaction_date) {
            query.set('sorter', 'transaction_date');
            query.set('sortOrder', sort.transaction_date === 'ascend' ? 'ascend' : 'descend');
        } else if (sort && sort.amount) {
            query.set('sorter', 'amount');
            query.set('sortOrder', sort.amount === 'ascend' ? 'ascend' : 'descend');
        }

        if (filter) {
            if (filter.account_id) {
                if (Array.isArray(filter.account_id)) {
                    filter.account_id.forEach((id) => query.append('account_id[]', id));
                } else {
                    query.set('account_id', filter.account_id);
                }
            }
            if (filter.category_id) {
                if (Array.isArray(filter.category_id)) {
                    filter.category_id.forEach((id) => query.append('category_id[]', id));
                } else {
                    query.set('category_id', filter.category_id);
                }
            }
            if (filter.type) {
                query.set('type', filter.type);
            }
            if (filter.dateRange && filter.dateRange.length === 2) {
                query.set('date_from', filter.dateRange[0]);
                query.set('date_to', filter.dateRange[1]);
            }
            if (filter.search) {
                query.set('search', filter.search);
            }
        }

        const res = await apiGet(`/api/transactions?${query.toString()}`);

        return {
            data: res.data || [],
            success: true,
            total: res.meta?.total || 0,
        };
    }, []);

    const handleDelete = useCallback(async (id) => {
        await apiDelete(`/api/transactions/${id}`);
        actionRef?.reload();
    }, [actionRef]);

    const columns = [
        {
            title: 'Date',
            dataIndex: 'transaction_date',
            key: 'transaction_date',
            width: 120,
            sorter: true,
            render: (_, record) => dayjs(record.transaction_date).format('MMM D, YYYY'),
        },
        {
            title: 'Type',
            dataIndex: 'type',
            key: 'type',
            width: 100,
            valueType: 'select',
            valueEnum: {
                income: { text: 'Income' },
                expense: { text: 'Expense' },
                transfer: { text: 'Transfer' },
            },
            render: (_, record) => (
                <Tag color={typeColorMap[record.type] || 'default'}>
                    {typeIconMap[record.type]}
                    {' '}{record.type.charAt(0).toUpperCase() + record.type.slice(1)}
                </Tag>
            ),
        },
        {
            title: 'Description',
            dataIndex: 'description',
            key: 'description',
            ellipsis: true,
        },
        {
            title: 'Amount',
            dataIndex: 'amount',
            key: 'amount',
            width: 150,
            sorter: true,
            render: (_, record) => {
                const color = record.type === 'income' ? '#52c41a' : record.type === 'expense' ? '#ff4d4f' : '#1677ff';
                const sign = record.type === 'income' ? '+' : record.type === 'expense' ? '-' : '';
                return (
                    <span style={{ color, fontWeight: 600 }}>
                        {sign}{Number(record.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                        {' '}{record.currency}
                    </span>
                );
            },
        },
        {
            title: 'Account',
            dataIndex: 'account',
            key: 'account',
            width: 150,
            ellipsis: true,
            render: (_, record) => record.account?.name || '-',
        },
        {
            title: 'Category',
            dataIndex: 'category',
            key: 'category',
            width: 150,
            ellipsis: true,
            render: (_, record) => record.category?.name || '-',
        },
        {
            title: 'Tags',
            dataIndex: 'tags',
            key: 'tags',
            width: 200,
            render: (_, record) => {
                if (!record.tags || record.tags.length === 0) return '-';
                return (
                    <Space size={4} wrap>
                        {record.tags.map((tag) => (
                            <Tag key={tag.id} color={tag.color || 'default'}>{tag.name}</Tag>
                        ))}
                    </Space>
                );
            },
        },
        {
            title: 'Actions',
            key: 'actions',
            width: 120,
            fixed: 'right',
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="View">
                        <Button type="text" size="small" icon={<EyeOutlined />} onClick={() => onView?.(record)} />
                    </Tooltip>
                    <Tooltip title="Edit">
                        <Button type="text" size="small" icon={<EditOutlined />} onClick={() => onEdit?.(record)} />
                    </Tooltip>
                    <Tooltip title="Delete">
                        <Button type="text" size="small" danger icon={<DeleteOutlined />} onClick={() => handleDelete(record.id)} />
                    </Tooltip>
                </Space>
            ),
        },
    ];

    return (
        <ProTable
            columns={columns}
            request={request}
            actionRef={(ref) => setActionRef(ref)}
            rowKey="id"
            search={{
                labelWidth: 'auto',
                defaultCollapsed: false,
            }}
            pagination={{
                defaultPageSize: 20,
                showSizeChanger: true,
            }}
            dateFormatter="string"
            headerTitle="Transactions"
            toolBarRender={() => [
                <Button key="add" type="primary" icon={<PlusOutlined />} onClick={onAdd}>
                    Add Transaction
                </Button>,
            ]}
            rowSelection={
                onSelectChange
                    ? {
                          selectedRowKeys,
                          onChange: onSelectChange,
                      }
                    : undefined
            }
            options={{
                reload: true,
                density: true,
                fullScreen: true,
            }}
        />
    );
}
