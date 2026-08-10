import { Head, usePage, router } from '@inertiajs/react';
import { Button, Form, Input, InputNumber, Select, DatePicker, message } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import dayjs from 'dayjs';

export default function Create() {
    const { props } = usePage();
    const [form] = Form.useForm();
    const categories = props.categories || [];
    const accounts = props.accounts || [];

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            values.transaction_date = values.transaction_date.format('YYYY-MM-DD');
            router.post('/transactions', values);
        } catch (error) {
            if (error.errorFields) {
                message.error('Mohon isi field yang diperlukan');
            }
        }
    };

    return (
        <AuthenticatedLayout title="Buat Transaksi">
            <Head title="Buat Transaksi" />
            <Form
                form={form}
                layout="vertical"
                style={{ maxWidth: 480 }}
                initialValues={{
                    type: 'expense',
                    transaction_date: dayjs(),
                }}
            >
                <Form.Item label="Deskripsi" name="description" rules={[{ required: true, message: 'Deskripsi wajib diisi' }]}>
                    <Input placeholder="contoh: Belanja bulanan" />
                </Form.Item>
                <Form.Item label="Jumlah" name="amount" rules={[{ required: true, message: 'Jumlah wajib diisi' }]}>
                    <InputNumber style={{ width: '100%' }} min={0} placeholder="0" />
                </Form.Item>
                <Form.Item label="Tipe" name="type" rules={[{ required: true, message: 'Tipe wajib dipilih' }]}>
                    <Select>
                        <Select.Option value="income">Pemasukan (Income)</Select.Option>
                        <Select.Option value="expense">Pengeluaran (Expense)</Select.Option>
                        <Select.Option value="transfer">Transfer</Select.Option>
                    </Select>
                </Form.Item>
                <Form.Item label="Rekening" name="account_id" rules={[{ required: true, message: 'Rekening wajib dipilih' }]}>
                    <Select placeholder="Pilih rekening" showSearch optionFilterProp="label">
                        {accounts.map((acc) => (
                            <Select.Option key={acc.id} value={acc.id} label={acc.name}>
                                {acc.name}
                            </Select.Option>
                        ))}
                    </Select>
                </Form.Item>
                <Form.Item label="Kategori" name="category_id" rules={[{ required: true, message: 'Kategori wajib dipilih' }]}>
                    <Select placeholder="Pilih kategori" showSearch optionFilterProp="label">
                        {categories.map((cat) => (
                            <Select.Option key={cat.id} value={cat.id} label={cat.name}>
                                {cat.name}
                            </Select.Option>
                        ))}
                    </Select>
                </Form.Item>
                <Form.Item label="Tanggal" name="transaction_date" rules={[{ required: true, message: 'Tanggal wajib diisi' }]}>
                    <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" />
                </Form.Item>
                <Form.Item label="Toko" name="toko">
                    <Input placeholder="Nama toko / merchant" />
                </Form.Item>
                <Form.Item label="Catatan" name="notes">
                    <Input.TextArea rows={3} placeholder="Catatan tambahan" />
                </Form.Item>
                <Form.Item>
                    <Button type="primary" onClick={handleSubmit} block>
                        Simpan
                    </Button>
                </Form.Item>
            </Form>
        </AuthenticatedLayout>
    );
}
