import { useEffect, useMemo, useState } from 'react';
import { Table, Checkbox } from 'antd';

const DUPLICATE_ROW_STYLE = { backgroundColor: 'rgba(250, 173, 20, 0.15)' };

/**
 * @param {{
 *   rows: object[],
 *   fileType: 'csv' | 'ofx',
 *   columnMapping?: { date?: string, description?: string, amount?: string, category?: string },
 *   onSelectionChange: (selectedIndices: number[]) => void,
 * }} props
 */
export default function ImportPreview({ rows, fileType, columnMapping = {}, onSelectionChange }) {
    const [selectedIndices, setSelectedIndices] = useState(() => rows.map((_, i) => i));

    useEffect(() => {
        setSelectedIndices(rows.map((_, i) => i));
    }, [rows]);

    useEffect(() => {
        onSelectionChange(selectedIndices);
    }, [selectedIndices, onSelectionChange]);

    const allSelected = selectedIndices.length === rows.length && rows.length > 0;
    const indeterminate = selectedIndices.length > 0 && selectedIndices.length < rows.length;

    const toggleAll = (checked) => {
        setSelectedIndices(checked ? rows.map((_, i) => i) : []);
    };

    const toggleRow = (index, checked) => {
        setSelectedIndices((prev) =>
            checked ? [...prev, index].sort((a, b) => a - b) : prev.filter((i) => i !== index)
        );
    };

    const isDuplicate = (row) => row.is_duplicate === true || row.duplicate === true;

    const csvColumns = useMemo(() => {
        const cols = [
            {
                title: (
                    <Checkbox
                        checked={allSelected}
                        indeterminate={indeterminate}
                        onChange={(e) => toggleAll(e.target.checked)}
                    />
                ),
                key: 'select',
                width: 50,
                render: (_, __, index) => (
                    <Checkbox
                        checked={selectedIndices.includes(index)}
                        onChange={(e) => toggleRow(index, e.target.checked)}
                    />
                ),
            },
        ];

        if (columnMapping.date) {
            cols.push({
                title: 'Tanggal',
                key: 'date',
                render: (_, record) => record[columnMapping.date],
            });
        }
        if (columnMapping.description) {
            cols.push({
                title: 'Deskripsi',
                key: 'description',
                render: (_, record) => record[columnMapping.description],
            });
        }
        if (columnMapping.amount) {
            cols.push({
                title: 'Jumlah',
                key: 'amount',
                render: (_, record) => record[columnMapping.amount],
            });
        }
        if (columnMapping.category) {
            cols.push({
                title: 'Kategori',
                key: 'category',
                render: (_, record) => record[columnMapping.category],
            });
        }

        return cols;
    }, [columnMapping, allSelected, indeterminate, selectedIndices]);

    const ofxColumns = [
        {
            title: (
                <Checkbox
                    checked={allSelected}
                    indeterminate={indeterminate}
                    onChange={(e) => toggleAll(e.target.checked)}
                />
            ),
            key: 'select',
            width: 50,
            render: (_, __, index) => (
                <Checkbox
                    checked={selectedIndices.includes(index)}
                    onChange={(e) => toggleRow(index, e.target.checked)}
                />
            ),
        },
        { title: 'Tanggal', dataIndex: 'date', key: 'date' },
        { title: 'Deskripsi', dataIndex: 'description', key: 'description' },
        {
            title: 'Jumlah',
            dataIndex: 'amount',
            key: 'amount',
            render: (amount) => Number(amount).toFixed(2),
        },
        { title: 'Tipe', dataIndex: 'type', key: 'type' },
    ];

    const columns = fileType === 'ofx' ? ofxColumns : csvColumns;

    return (
        <Table
            columns={columns}
            dataSource={rows.map((row, index) => ({ ...row, key: index }))}
            pagination={{ pageSize: 10, showSizeChanger: false }}
            size="small"
            onRow={(record) => ({
                style: isDuplicate(record) ? DUPLICATE_ROW_STYLE : undefined,
            })}
        />
    );
}
