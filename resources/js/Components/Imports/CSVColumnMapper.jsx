import { useEffect, useState } from 'react';
import { Table, Select, Alert } from 'antd';

const FIELD_OPTIONS = [
    { value: '', label: 'Pilih Field...' },
    { value: 'date', label: 'Tanggal' },
    { value: 'description', label: 'Deskripsi' },
    { value: 'amount', label: 'Jumlah' },
    { value: 'category', label: 'Kategori' },
];

const REQUIRED_FIELDS = ['date', 'description', 'amount'];

/**
 * @param {{ headers: string[], onMapping: (mapping: object) => void, onValidChange?: (valid: boolean) => void }} props
 */
export default function CSVColumnMapper({ headers, onMapping, onValidChange }) {
    const [headerMapping, setHeaderMapping] = useState({});

    useEffect(() => {
        const mapping = { date: null, description: null, amount: null, category: null };
        Object.entries(headerMapping).forEach(([header, field]) => {
            if (field) {
                mapping[field] = header;
            }
        });
        onMapping(mapping);

        const isValid = REQUIRED_FIELDS.every((field) => mapping[field]);
        onValidChange?.(isValid);
    }, [headerMapping, onMapping, onValidChange]);

    const getUsedFields = (currentHeader) => {
        return Object.entries(headerMapping)
            .filter(([header, field]) => header !== currentHeader && field)
            .map(([, field]) => field);
    };

    const handleFieldChange = (header, field) => {
        setHeaderMapping((prev) => ({
            ...prev,
            [header]: field || undefined,
        }));
    };

    const isValid = REQUIRED_FIELDS.every((field) =>
        Object.values(headerMapping).includes(field)
    );

    const columns = [
        {
            title: 'Kolom CSV',
            dataIndex: 'header',
            key: 'header',
        },
        {
            title: 'Field Tujuan',
            key: 'field',
            render: (_, record) => {
                const usedFields = getUsedFields(record.header);
                const options = FIELD_OPTIONS.map((opt) => ({
                    ...opt,
                    disabled: opt.value && usedFields.includes(opt.value),
                }));

                return (
                    <Select
                        style={{ width: '100%' }}
                        value={headerMapping[record.header] || ''}
                        onChange={(value) => handleFieldChange(record.header, value)}
                        options={options}
                    />
                );
            },
        },
    ];

    const dataSource = headers.map((header) => ({ key: header, header }));

    return (
        <div>
            {!isValid && (
                <Alert
                    type="info"
                    showIcon
                    message="Petakan minimal kolom Tanggal, Deskripsi, dan Jumlah untuk melanjutkan."
                    style={{ marginBottom: 16 }}
                />
            )}
            <Table
                columns={columns}
                dataSource={dataSource}
                pagination={false}
                size="middle"
            />
        </div>
    );
}
