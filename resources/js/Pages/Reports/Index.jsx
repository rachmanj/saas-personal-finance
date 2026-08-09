import { Head } from '@inertiajs/react';
import { App, Card } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import ReportExport from '../../Components/Reports/ReportExport';
import OfflineIndicator from '../../Components/PWA/OfflineIndicator';

export default function Index() {
    return (
        <App>
            <AuthenticatedLayout title="Laporan & Ekspor">
                <Head title="Laporan & Ekspor" />
                <OfflineIndicator />

                <Card title="Ekspor Transaksi">
                    <p style={{ marginBottom: 16 }}>
                        Ekspor data transaksi Anda dalam berbagai format untuk pelaporan, cadangan, atau impor ke aplikasi lain.
                    </p>
                    <ReportExport />
                </Card>
            </AuthenticatedLayout>
        </App>
    );
}
