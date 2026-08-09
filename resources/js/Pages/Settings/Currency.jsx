import { Head, usePage, useForm } from '@inertiajs/react';
import { App, Button, Card, Form, Select, message } from 'antd';
import { DollarOutlined, SaveOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

const CURRENCIES = [
    { value: 'IDR', label: 'IDR - Rupiah Indonesia (Rp)' },
    { value: 'USD', label: 'USD - Dolar Amerika Serikat ($)' },
    { value: 'SGD', label: 'SGD - Dolar Singapura (S$)' },
    { value: 'MYR', label: 'MYR - Ringgit Malaysia (RM)' },
    { value: 'EUR', label: 'EUR - Euro (€)' },
    { value: 'GBP', label: 'GBP - Poundsterling (£)' },
    { value: 'AUD', label: 'AUD - Dolar Australia (A$)' },
    { value: 'JPY', label: 'JPY - Yen Jepang (¥)' },
    { value: 'CNY', label: 'CNY - Yuan Tiongkok (¥)' },
    { value: 'THB', label: 'THB - Baht Thailand (฿)' },
];

function CurrencyContent() {
    const { props } = usePage();
    const currentCurrency = props.currentCurrency || 'IDR';

    const { data, setData, put, processing } = useForm({
        currency: currentCurrency,
    });

    const handleSubmit = () => {
        put('/settings/currency', {
            onSuccess: () => {
                message.success('Mata uang berhasil diperbarui');
            },
        });
    };

    return (
        <AuthenticatedLayout title="Pengaturan Mata Uang">
            <Head title="Mata Uang" />

            <Card
                title={
                    <span>
                        <DollarOutlined style={{ marginRight: 8 }} />
                        Pengaturan Mata Uang Default
                    </span>
                }
                bordered
                style={{ maxWidth: 600 }}
            >
                <Form layout="vertical" onFinish={handleSubmit}>
                    <Form.Item
                        label="Mata Uang Utama"
                        help="Mata uang yang digunakan untuk menampilkan semua nilai keuangan."
                    >
                        <Select
                            value={data.currency}
                            onChange={(value) => setData('currency', value)}
                            options={CURRENCIES}
                            showSearch
                            filterOption={(input, option) =>
                                (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                            }
                        />
                    </Form.Item>

                    <Form.Item>
                        <Button
                            type="primary"
                            htmlType="submit"
                            icon={<SaveOutlined />}
                            loading={processing}
                        >
                            Simpan Pengaturan
                        </Button>
                    </Form.Item>
                </Form>
            </Card>
        </AuthenticatedLayout>
    );
}

export default function Currency(props) {
    return (
        <App>
            <CurrencyContent {...props} />
        </App>
    );
}
